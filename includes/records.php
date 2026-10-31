<?php
/**
 * Official student records: grades per school year and term (for SF9 / SF10 and the
 * registrar's student page) and document requests.
 *
 *   require_once __DIR__ . '/../includes/records.php';
 *   $terms = records_student_grades($pdo, $student);   // keyed "2026-2027|1st Semester"
 *
 * Only accepted (locked) grades are official; others are listed as not final.
 * A class only stores its semester's name, so its school year comes from the
 * semester row whose dates contain the day the class was created.
 */

require_once __DIR__ . '/grading.php';
require_once __DIR__ . '/registrar.php';

if (!function_exists('records_class_term')) {

    function records_school_name(): string
    {
        return 'Don Pablo Lorenzo Memorial High School – Stand-Alone Senior High School';
    }

    function records_school_address(): string
    {
        return 'Zamboanga City, Philippines';
    }

    /** ['school_year' => '2026-2027', 'term' => '1st Semester'] for a classes row. */
    function records_class_term(PDO $pdo, array $class): array
    {
        static $semesters = null;
        if ($semesters === null) {
            $semesters = $pdo->query("SELECT name, start_date, end_date FROM semester ORDER BY start_date")->fetchAll(PDO::FETCH_ASSOC);
        }
        $added = substr((string) ($class['datetime_added'] ?? ''), 0, 10) ?: date('Y-m-d');
        $match = null;
        foreach ($semesters as $s) {
            if ($s['name'] !== $class['semester']) {
                continue;
            }
            // Classes are often opened a few weeks before the semester starts
            if ($added >= date('Y-m-d', strtotime($s['start_date'] . ' -60 days')) && $added <= $s['end_date']) {
                $match = $s;
                break;
            }
            if ($s['start_date'] <= $added) {
                $match = $s; // latest earlier semester with that name
            }
        }
        $date = new DateTime($match['start_date'] ?? $added);
        return ['school_year' => enrollment_school_year_for($date), 'term' => enrollment_term_for((string) $class['semester'], $date)];
    }

    /**
     * Every subject the student took, grouped by term, oldest first:
     * "2026-2027|1st Semester" => [
     *     'school_year', 'term',
     *     'subjects' => [['subject', 'q1', 'q2', 'final', 'remarks', 'official' => bool], ...],
     *     'general_average' => float|null (official grades only, all subjects graded),
     *     'complete' => bool (every subject official),
     * ]
     * A subject split into Lecture and Laboratory classes is combined 60/40.
     */
    function records_student_grades(PDO $pdo, array $student): array
    {
        $stmt = $pdo->prepare("
            SELECT c.id, c.subject, c.type, c.semester, c.datetime_added,
                   sg.midterm_grade, sg.final_grade, sg.overall_grade, sg.status
            FROM students_enrollments se
            JOIN classes c ON c.id = se.class_id AND c.status = 'accepted'
            LEFT JOIN student_grades sg ON sg.class_id = c.id AND sg.student_id = se.student_id
            WHERE se.student_id = ?
            ORDER BY c.subject, c.type
        ");
        $stmt->execute([$student['student_id']]);

        $parts = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $t = records_class_term($pdo, $row);
            $key = $t['school_year'] . '|' . $t['term'];
            $parts[$key]['school_year'] = $t['school_year'];
            $parts[$key]['term'] = $t['term'];
            $parts[$key]['subjects'][$row['subject']][stripos((string) $row['type'], 'lab') !== false ? 'lab' : 'lec'] = $row;
        }

        $terms = [];
        foreach ($parts as $key => $term) {
            $subjects = [];
            foreach ($term['subjects'] as $name => $p) {
                $official = true;
                foreach ($p as $part) {
                    $official = $official && ($part['status'] ?? '') === 'accepted';
                }
                $grade = function (string $field) use ($p): string {
                    if (isset($p['lec'], $p['lab'])) {
                        return deped_combined_grade($p['lec'][$field] ?? 'N/A', $p['lab'][$field] ?? 'N/A');
                    }
                    $only = $p['lec'] ?? $p['lab'];
                    return deped_display($only[$field] ?? 'N/A');
                };
                $final = $grade('overall_grade');
                $subjects[] = [
                    'subject'  => $name,
                    'q1'       => $grade('midterm_grade'),
                    'q2'       => $grade('final_grade'),
                    'final'    => $final,
                    'remarks'  => $official ? deped_remarks($final) : '',
                    'official' => $official,
                ];
            }
            $complete = (bool) $subjects && !in_array(false, array_column($subjects, 'official'), true);
            $terms[$key] = [
                'school_year'     => $term['school_year'],
                'term'            => $term['term'],
                'subjects'        => $subjects,
                'general_average' => $complete ? deped_general_average(array_column($subjects, 'final')) : null,
                'complete'        => $complete,
            ];
        }
        uksort($terms, fn($a, $b) => strcmp($a, $b));
        return $terms;
    }

    /* ------------------------------------------------------------------
     * Document requests
     * ------------------------------------------------------------------ */

    function records_document_types(): array
    {
        return [
            'coe'        => 'Certificate of Enrollment',
            'good_moral' => 'Certificate of Good Moral Character',
            'sf9'        => 'Report Card (SF9)',
            'sf10'       => 'Permanent Record (SF10 / Form 137)',
        ];
    }

    function records_request_statuses(): array
    {
        return [
            'pending'    => ['Pending', 'is-warn'],
            'processing' => ['Processing', 'is-info'],
            'ready'      => ['Ready for pick-up', 'is-ok'],
            'released'   => ['Released', ''],
            'declined'   => ['Declined', 'is-alert'],
        ];
    }

    function records_request_purposes(): array
    {
        return ['Scholarship application', 'College application', 'Transfer to another school', 'Employment', 'Personal copy', 'Other'];
    }

    /** Tell the student (student_notifications.user_id is the student ID number). */
    function records_notify_student(PDO $pdo, int $studentNumber, string $title, string $text): void
    {
        $pdo->prepare("INSERT INTO student_notifications (user_id, type, title, description, date, link, status)
            VALUES (?, 'document', ?, ?, NOW(), 'document_requests.php', 'unread')")
            ->execute([$studentNumber, $title, $text]);
    }
}
