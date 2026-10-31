<?php
/**
 * Registrar domain: school-year enrollment records and student accounts.
 *
 * Used by the registrar portal (registrar/), the admin's Registrars page and the
 * adviser's "add student" flow, so every way a student enters the system leaves
 * an enrollment record behind.
 *
 *   require_once __DIR__ . '/../includes/registrar.php';
 *   $term = enrollment_current_term($pdo);   // ['school_year' => '2026-2027', 'term' => '1st Semester', ...]
 *   enrollment_save($pdo, $studentRowId, $record, 'registrar', $name);
 *
 * Tables come from database/registrar.sql. A student's "row id" is students.id;
 * students.student_id is the school's own ID number (what chat and attendance use).
 */

require_once __DIR__ . '/academic.php';

if (!function_exists('enrollment_statuses')) {

    function enrollment_statuses(): array
    {
        return [
            'enrolled'        => 'Enrolled',
            'completed'       => 'Completed',
            'dropped'         => 'Dropped',
            'transferred_out' => 'Transferred out',
            'graduated'       => 'Graduated',
        ];
    }

    function enrollment_admission_types(): array
    {
        return [
            'new'        => 'New student',
            'continuing' => 'Continuing',
            'transferee' => 'Transferee',
            'returnee'   => 'Returnee (Balik-Aral)',
        ];
    }

    function enrollment_terms(): array
    {
        return ['1st Semester', '2nd Semester'];
    }

    /** The school year runs June to May: 2026-10-31 => "2026-2027", 2027-02-01 => "2026-2027". */
    function enrollment_school_year_for(DateTimeInterface $date): string
    {
        $year = (int) $date->format('Y');
        $start = (int) $date->format('n') >= 6 ? $year : $year - 1;
        return $start . '-' . ($start + 1);
    }

    /** Recent school years, newest first, for pickers (next year down to five years back). */
    function enrollment_school_years(): array
    {
        $start = (int) substr(enrollment_school_year_for(new DateTime('today')), 0, 4);
        $years = [];
        for ($y = $start + 1; $y >= $start - 5; $y--) {
            $years[] = $y . '-' . ($y + 1);
        }
        return $years;
    }

    function enrollment_valid_school_year(string $sy): bool
    {
        return (bool) preg_match('/^(\d{4})-(\d{4})$/', $sy, $m) && (int) $m[2] === (int) $m[1] + 1;
    }

    /**
     * The term the school is in now. Taken from the admin's current semester
     * (Semester Management) when one is set, otherwise worked out from today's date.
     * Returns school_year, term, semester_id (or null) and a display label.
     */
    function enrollment_current_term(PDO $pdo): array
    {
        static $cache = null;
        if ($cache !== null) {
            return $cache;
        }

        $row = null;
        try {
            $row = $pdo->query("SELECT s.id, s.name, s.start_date FROM current_semester cs
                JOIN semester s ON s.name = cs.semester AND (s.archived = 0 OR s.archived IS NULL)
                ORDER BY s.status = 'active' DESC, s.start_date DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC) ?: null;
        } catch (PDOException $e) {
            error_log('Could not read the current semester: ' . $e->getMessage());
        }

        if ($row) {
            $term = [
                'school_year' => enrollment_school_year_for(new DateTime($row['start_date'])),
                'term'        => $row['name'],
                'semester_id' => (int) $row['id'],
            ];
        } else {
            $today = new DateTime('today');
            $month = (int) $today->format('n');
            $term = [
                'school_year' => enrollment_school_year_for($today),
                'term'        => ($month >= 6 && $month <= 10) ? '1st Semester' : '2nd Semester',
                'semester_id' => null,
            ];
        }
        $term['label'] = 'S.Y. ' . $term['school_year'] . ' · ' . $term['term'];
        return $cache = $term;
    }

    function enrollment_is_current_term(PDO $pdo, string $schoolYear, string $term): bool
    {
        $current = enrollment_current_term($pdo);
        return $current['school_year'] === $schoolYear && $current['term'] === $term;
    }

    /**
     * Check and normalise an enrollment form. Returns [$record, null] or [null, $error].
     * The section (optional) must belong to the chosen strand and grade.
     */
    function enrollment_validate(array $in): array
    {
        $record = [
            'school_year'    => trim((string) ($in['school_year'] ?? '')),
            'term'           => trim((string) ($in['term'] ?? '')),
            'strand'         => trim((string) ($in['strand'] ?? '')),
            'grade'          => trim((string) ($in['grade'] ?? '')),
            'section_code'   => trim((string) ($in['section_code'] ?? '')),
            'admission_type' => (string) ($in['admission_type'] ?? 'continuing'),
            'status'         => (string) ($in['status'] ?? 'enrolled'),
            'remarks'        => mb_substr(trim((string) ($in['remarks'] ?? '')), 0, 255),
        ];

        if (!enrollment_valid_school_year($record['school_year'])) {
            return [null, 'Pick a school year.'];
        }
        if (!in_array($record['term'], enrollment_terms(), true)) {
            return [null, 'Pick a term.'];
        }
        if (!isset(shs_strands()[$record['strand']]) || !in_array($record['grade'], shs_grades(), true)) {
            return [null, 'Pick a strand and grade.'];
        }
        if (!isset(enrollment_admission_types()[$record['admission_type']])) {
            return [null, 'Pick how the student was admitted.'];
        }
        if (!isset(enrollment_statuses()[$record['status']])) {
            return [null, 'Pick an enrollment status.'];
        }
        if ($record['section_code'] !== '') {
            $section = shs_parse_section($record['section_code']);
            $exists = in_array($record['section_code'], array_column(shs_section_rows(), 'code'), true);
            if (!$section || !$exists) {
                return [null, 'That section no longer exists.'];
            }
            if ($section['strand'] !== $record['strand'] || $section['grade'] !== $record['grade']) {
                return [null, $record['section_code'] . ' is not in ' . $record['strand'] . ' ' . shs_grade_label($record['grade']) . '. Pick one of its sections or "No section yet".'];
            }
        }

        $record['grade_level'] = shs_grade_label($record['grade']);
        unset($record['grade']);
        $record['section_code'] = $record['section_code'] !== '' ? $record['section_code'] : null;
        return [$record, null];
    }

    /**
     * Create or update the student's record for that school year + term
     * (one record per term). Records for the current term also update the
     * student's live placement. Call inside the caller's transaction if it has one.
     */
    function enrollment_save(PDO $pdo, int $studentRowId, array $record, string $byType, string $byName): void
    {
        $semesterId = null;
        if (enrollment_is_current_term($pdo, $record['school_year'], $record['term'])) {
            $semesterId = enrollment_current_term($pdo)['semester_id'];
        }

        $pdo->prepare("
            INSERT INTO enrollment_records
                (student_row_id, school_year, term, semester_id, strand, grade_level, section_code,
                 admission_type, status, remarks, recorded_by_type, recorded_by_name)
            VALUES (:sid, :sy, :term, :sem, :strand, :grade, :section, :admission, :status, :remarks, :by_type, :by_name)
            ON DUPLICATE KEY UPDATE
                semester_id = COALESCE(VALUES(semester_id), semester_id),
                strand = VALUES(strand), grade_level = VALUES(grade_level), section_code = VALUES(section_code),
                admission_type = VALUES(admission_type), status = VALUES(status), remarks = VALUES(remarks),
                recorded_by_type = VALUES(recorded_by_type), recorded_by_name = VALUES(recorded_by_name)
        ")->execute([
            'sid'       => $studentRowId,
            'sy'        => $record['school_year'],
            'term'      => $record['term'],
            'sem'       => $semesterId,
            'strand'    => $record['strand'],
            'grade'     => $record['grade_level'],
            'section'   => $record['section_code'],
            'admission' => $record['admission_type'],
            'status'    => $record['status'],
            'remarks'   => $record['remarks'],
            'by_type'   => $byType,
            'by_name'   => $byName,
        ]);

        if (enrollment_is_current_term($pdo, $record['school_year'], $record['term'])) {
            enrollment_sync_placement($pdo, $studentRowId, $record);
        }
    }

    /**
     * Mirror a current-term record onto the rest of the system:
     *  - enrolled: students.course / year_level follow the record, and the student
     *    is listed under every adviser of the section (students_advising);
     *  - dropped / transferred out: the student leaves their advisers' lists;
     *  - completed / graduated: nothing moves.
     */
    function enrollment_sync_placement(PDO $pdo, int $studentRowId, array $record): void
    {
        if ($record['status'] === 'enrolled') {
            $pdo->prepare("UPDATE students SET course = :strand, year_level = :grade WHERE id = :id")
                ->execute(['strand' => $record['strand'], 'grade' => $record['grade_level'], 'id' => $studentRowId]);

            // Drop advisory links to other sections, then add any missing ones for this section
            $pdo->prepare("
                DELETE sa FROM students_advising sa
                JOIN staff_advising st ON st.id = sa.staff_advising_id
                WHERE sa.student_id = :id AND (:section IS NULL OR st.class_advising <> :section2)
            ")->execute(['id' => $studentRowId, 'section' => $record['section_code'], 'section2' => $record['section_code']]);

            if ($record['section_code'] !== null) {
                $pdo->prepare("
                    INSERT INTO students_advising (student_id, staff_advising_id)
                    SELECT :id, st.id FROM staff_advising st
                    WHERE st.class_advising = :section
                      AND NOT EXISTS (SELECT 1 FROM students_advising x WHERE x.student_id = :id2 AND x.staff_advising_id = st.id)
                ")->execute(['id' => $studentRowId, 'id2' => $studentRowId, 'section' => $record['section_code']]);
            }
        } elseif (in_array($record['status'], ['dropped', 'transferred_out'], true)) {
            $pdo->prepare("DELETE FROM students_advising WHERE student_id = ?")->execute([$studentRowId]);
        }
    }

    /* ------------------------------------------------------------------
     * Student accounts
     * ------------------------------------------------------------------ */

    /** "" is allowed (LRN not known yet); otherwise exactly 12 digits. */
    function registrar_valid_lrn(string $lrn): bool
    {
        return $lrn === '' || (bool) preg_match('/^\d{12}$/', $lrn);
    }

    /** A readable temporary password, e.g. "Dplmhs-4821-kq". */
    function registrar_temp_password(): string
    {
        $letters = 'abcdefghjkmnpqrstuvwxyz';
        return 'Dplmhs-' . random_int(1000, 9999) . '-' . $letters[random_int(0, 22)] . $letters[random_int(0, 22)];
    }

    /* ------------------------------------------------------------------
     * Form protection (same approach as the Ideas board)
     * ------------------------------------------------------------------ */

    function registrar_csrf_token(): string
    {
        if (empty($_SESSION['registrar_csrf'])) {
            $_SESSION['registrar_csrf'] = bin2hex(random_bytes(16));
        }
        return $_SESSION['registrar_csrf'];
    }

    function registrar_csrf_valid(): bool
    {
        return !empty($_SESSION['registrar_csrf']) && hash_equals($_SESSION['registrar_csrf'], (string) ($_POST['csrf'] ?? ''));
    }

    /** One-time SweetAlert message for the next page: registrar_flash('Saved', 'The student was updated.'). */
    function registrar_flash(string $title, string $text, string $icon = 'success'): void
    {
        $_SESSION['registrar_flash'] = [$title, $text, $icon];
    }

    function registrar_render_flash(): void
    {
        if (empty($_SESSION['registrar_flash'])) {
            return;
        }
        [$title, $text, $icon] = $_SESSION['registrar_flash'];
        unset($_SESSION['registrar_flash']);
        $flags = JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT;
        echo '<script>Swal.fire(' . json_encode($title, $flags) . ', ' . json_encode($text, $flags) . ', ' . json_encode($icon, $flags) . ');</script>';
    }
}
