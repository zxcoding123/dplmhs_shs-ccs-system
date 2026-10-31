<?php
/**
 * DepEd senior high school grading (DepEd Order No. 8, s. 2015) and grade locking.
 *
 * The gradebook keeps its two terms in student_grades: midterm_grade is the
 * 1st quarter, final_grade the 2nd quarter and overall_grade the semester's
 * final grade (the average of the two quarters). Grades are whole numbers from
 * 60 to 100; 75 passes. Text grades: N/A (not graded yet), DRP (dropped), and the
 * older INC / AW / UW, which are still shown if stored.
 *
 *   require_once __DIR__ . '/../includes/grading.php';
 *   deped_quarter_grade(0.8325);         // "89" — weighted score (0–1) to quarterly grade
 *   deped_final_grade('89', '91');       // "90"
 *   grades_review($pdo, $classId, 'accept', 'registrar', $name);
 *
 * Locking: once a class's grades are accepted (status 'accepted') the teacher can't
 * change them. An admin or registrar can return a submission or unlock accepted
 * grades; every step is written to grade_reviews (database/deped_grading.sql).
 */

if (!function_exists('deped_transmute')) {

    define('DEPED_PASSING_GRADE', 75);
    define('DEPED_TEXT_GRADES', ['N/A', 'DRP', 'INC', 'AW', 'UW']);

    /**
     * DepEd transmutation table. Initial grade (0–100) to quarterly grade (60–100):
     * 60 and up moves in steps of 1.6 from 75, below 60 in steps of 4 from 60.
     */
    function deped_transmute(float $initial): int
    {
        $initial = max(0.0, min(100.0, $initial));
        if ($initial >= 100) {
            return 100;
        }
        // Small epsilon so 98.40 lands on 99, not 98, after float rounding
        return $initial >= 60
            ? 75 + (int) floor(($initial - 60) / 1.6 + 1e-9)
            : 60 + (int) floor($initial / 4 + 1e-9);
    }

    /** Weighted score as a fraction (0–1, the gradebook's sum of component weights) to a quarterly grade. */
    function deped_quarter_grade(float $fraction): string
    {
        return (string) deped_transmute($fraction * 100);
    }

    function deped_is_number($grade): bool
    {
        return is_numeric($grade) && (float) $grade >= 60 && (float) $grade <= 100;
    }

    /** Semester final grade: the average of both quarters, rounded. A text grade on either side carries over. */
    function deped_final_grade($q1, $q2): string
    {
        foreach (['DRP', 'AW', 'UW', 'INC'] as $text) {
            if ($q1 === $text || $q2 === $text) {
                return $text;
            }
        }
        if (!deped_is_number($q1) || !deped_is_number($q2)) {
            return 'N/A';
        }
        return (string) (int) round(((float) $q1 + (float) $q2) / 2);
    }

    /** Lecture + laboratory parts of one subject, 60/40, rounded. */
    function deped_combined_grade($lecture, $laboratory): string
    {
        foreach (DEPED_TEXT_GRADES as $text) {
            if ($lecture === $text || $laboratory === $text) {
                return $text;
            }
        }
        if (!deped_is_number($lecture) || !deped_is_number($laboratory)) {
            return 'N/A';
        }
        return (string) (int) round((float) $lecture * 0.6 + (float) $laboratory * 0.4);
    }

    /** Shown in red: failing grades and anything that isn't a grade yet. */
    function deped_flagged($grade): bool
    {
        if (deped_is_number($grade)) {
            return (float) $grade < DEPED_PASSING_GRADE;
        }
        return true;
    }

    /** Whole number for numeric grades ("89"), text grades as they are. */
    function deped_display($grade): string
    {
        if ($grade === null || $grade === '') {
            return 'N/A';
        }
        return is_numeric($grade) ? (string) (int) round((float) $grade) : (string) $grade;
    }

    function deped_remarks($finalGrade): string
    {
        if (deped_is_number($finalGrade)) {
            return (float) $finalGrade >= DEPED_PASSING_GRADE ? 'Passed' : 'Failed';
        }
        return $finalGrade === 'DRP' ? 'Dropped' : '';
    }

    /** General average of a semester's final grades, or null if any subject has no numeric grade yet. */
    function deped_general_average(array $finalGrades): ?float
    {
        if (!$finalGrades) {
            return null;
        }
        foreach ($finalGrades as $grade) {
            if (!deped_is_number($grade)) {
                return null;
            }
        }
        return round(array_sum(array_map('floatval', $finalGrades)) / count($finalGrades), 2);
    }

    /** activities.term values ('midterm' / 'final') as DepEd quarters. */
    function deped_quarter_label(?string $term): string
    {
        return ['midterm' => '1st Quarter', 'final' => '2nd Quarter'][$term] ?? ucfirst((string) $term);
    }

    /** True when the class has at least one activity in that term, so the quarter can be graded. */
    function deped_term_has_work(array $activities, string $term): bool
    {
        foreach ($activities as $activity) {
            if (($activity['term'] ?? '') === $term) {
                return true;
            }
        }
        return false;
    }

    /**
     * Component weights by subject type (DepEd Order No. 8, s. 2015, Table 4).
     * Keys are the rubric titles the gradebook uses for activity types.
     */
    function deped_weight_presets(): array
    {
        return [
            'core' => [
                'label'   => 'Core subject',
                'weights' => ['Written Work' => 25, 'Performance Tasks' => 50, 'Quarterly Assessment' => 25],
            ],
            'academic' => [
                'label'   => 'Academic track: applied or specialized subject',
                'weights' => ['Written Work' => 25, 'Performance Tasks' => 45, 'Quarterly Assessment' => 30],
            ],
            'academic_immersion' => [
                'label'   => 'Academic track: Work Immersion, Research, Business Simulation, Exhibit or Performance',
                'weights' => ['Written Work' => 35, 'Performance Tasks' => 40, 'Quarterly Assessment' => 25],
            ],
            'tvl' => [
                'label'   => 'TVL, Sports or Arts and Design track subject',
                'weights' => ['Written Work' => 20, 'Performance Tasks' => 60, 'Quarterly Assessment' => 20],
            ],
        ];
    }

    /* ------------------------------------------------------------------
     * Grade submission review and locking
     * ------------------------------------------------------------------ */

    /**
     * Where a class's grades stand:
     *  none      nothing saved yet
     *  draft     saved or returned, teacher still editing
     *  submitted waiting for review (for_approval)
     *  locked    accepted, teacher can't edit
     */
    function grades_class_state(PDO $pdo, int $classId): string
    {
        $stmt = $pdo->prepare("SELECT status, COUNT(*) FROM student_grades WHERE class_id = ? GROUP BY status");
        $stmt->execute([$classId]);
        $counts = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        if (!empty($counts['accepted'])) {
            return 'locked';
        }
        if (!empty($counts['for_approval'])) {
            return 'submitted';
        }
        if (!empty($counts['saved']) || !empty($counts['returned']) || !empty($counts['rejected'])) {
            return 'draft';
        }
        return 'none';
    }

    function grades_is_locked(PDO $pdo, int $classId): bool
    {
        return grades_class_state($pdo, $classId) === 'locked';
    }

    function grades_state_label(string $state): array
    {
        return [
            'none'      => ['Not started', ''],
            'draft'     => ['Teacher editing', 'is-info'],
            'submitted' => ['Waiting for review', 'is-warn'],
            'locked'    => ['Accepted · locked', 'is-ok'],
        ][$state] ?? [$state, ''];
    }

    /**
     * Review a class's grades. $action:
     *  accept  submitted -> accepted (locked)
     *  return  submitted -> returned to the teacher (reason required)
     *  unlock  accepted  -> returned to the teacher for corrections (reason required)
     * Returns the number of student grades changed (0 = nothing in the right state).
     * The teacher is notified and the step is logged.
     */
    function grades_review(PDO $pdo, int $classId, string $action, string $byType, string $byName, string $reason = ''): int
    {
        $transitions = [
            'accept' => ['for_approval', 'accepted'],
            'return' => ['for_approval', 'returned'],
            'unlock' => ['accepted', 'returned'],
        ];
        if (!isset($transitions[$action])) {
            throw new InvalidArgumentException('Unknown grade review action.');
        }
        [$from, $to] = $transitions[$action];

        $class = $pdo->prepare("SELECT name, subject, teacher FROM classes WHERE id = ?");
        $class->execute([$classId]);
        $class = $class->fetch(PDO::FETCH_ASSOC);
        if (!$class) {
            return 0;
        }

        $ownTransaction = !$pdo->inTransaction();
        if ($ownTransaction) {
            $pdo->beginTransaction();
        }
        try {
            $stmt = $pdo->prepare("UPDATE student_grades SET status = ? WHERE class_id = ? AND status = ?");
            $stmt->execute([$to, $classId, $from]);
            $changed = $stmt->rowCount();

            if ($changed > 0) {
                $pdo->prepare("INSERT INTO grade_reviews (class_id, action, reason, actor_type, actor_name) VALUES (?, ?, ?, ?, ?)")
                    ->execute([$classId, $action, mb_substr($reason, 0, 500), $byType, $byName]);

                $messages = [
                    'accept' => ['Grades accepted', "Your grades for {$class['subject']} ({$class['name']}) were accepted by $byName and are now final."],
                    'return' => ['Grades returned', "$byName returned your grades for {$class['subject']} ({$class['name']}): $reason"],
                    'unlock' => ['Grades unlocked', "$byName unlocked your grades for {$class['subject']} ({$class['name']}) for corrections: $reason. Submit them again when done."],
                ];
                [$title, $text] = $messages[$action];
                $pdo->prepare("
                    INSERT INTO staff_notifications (user_id, type, title, description, date, link)
                    SELECT id, 'class', ?, ?, NOW(), 'class_management.php' FROM staff_accounts WHERE fullName = ?
                ")->execute([$title, $text, $class['teacher']]);
            }

            if ($ownTransaction) {
                $pdo->commit();
            }
            return $changed;
        } catch (Throwable $e) {
            if ($ownTransaction && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    /** Log a teacher's submission so the review history starts there. */
    function grades_log_submission(PDO $pdo, int $classId, string $teacherName): void
    {
        $pdo->prepare("INSERT INTO grade_reviews (class_id, action, reason, actor_type, actor_name) VALUES (?, 'submit', '', 'staff', ?)")
            ->execute([$classId, $teacherName]);
    }

    /** Review history for a class, newest first. */
    function grades_history(PDO $pdo, int $classId): array
    {
        $stmt = $pdo->prepare("SELECT * FROM grade_reviews WHERE class_id = ? ORDER BY created_at DESC, id DESC");
        $stmt->execute([$classId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
