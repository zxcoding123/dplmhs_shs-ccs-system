<?php
/**
 * Senior high school academic structure, shared by every page.
 *
 * Section codes follow STRAND-GRADE+NAME, e.g. "STEM-11A" (name "A") or "HUMSS-12Rizal".
 * students.course stores the strand ("STEM"), students.year_level the grade ("Grade 11"),
 * and subjects.year_level the section suffix ("11A").
 *
 * Strands and grades are fixed below; sections live in the `sections` table and are
 * managed from Admin > Sections (admin/section_management.php).
 */

if (!function_exists('shs_strands')) {

    /** Strand code => full name */
    function shs_strands(): array
    {
        return [
            'STEM'  => 'Science, Technology, Engineering and Mathematics',
            'ABM'   => 'Accountancy, Business and Management',
            'HUMSS' => 'Humanities and Social Sciences',
            'GAS'   => 'General Academic Strand',
            'TVL'   => 'Technical-Vocational-Livelihood',
        ];
    }

    /** Grade numbers offered */
    function shs_grades(): array
    {
        return ['11', '12'];
    }

    /** "11" => "Grade 11" */
    function shs_grade_label(string $grade): string
    {
        return 'Grade ' . $grade;
    }

    /** ["Grade 11", "Grade 12"] */
    function shs_grade_levels(): array
    {
        return array_map('shs_grade_label', shs_grades());
    }

    /** "Grade 11" => "11" (null if not a known grade level) */
    function shs_grade_number(string $gradeLevel): ?string
    {
        $grade = preg_replace('/\D/', '', $gradeLevel);
        return in_array($grade, shs_grades(), true) ? $grade : null;
    }

    /** "STEM" => "STEM — Science, Technology, Engineering and Mathematics" */
    function shs_strand_label(string $code): string
    {
        $name = shs_strands()[$code] ?? null;
        return $name ? $code . ' — ' . $name : $code;
    }

    /** Section names may only use letters and digits, so the code stays parseable. */
    function shs_valid_section_name(string $name): bool
    {
        return (bool) preg_match('/^[A-Za-z0-9]{1,20}$/', $name);
    }

    /** "STEM", "11", "A" => "STEM-11A" */
    function shs_section_code(string $strand, string $grade, string $name): string
    {
        return $strand . '-' . $grade . $name;
    }

    /**
     * All rows from the sections table, ordered by strand, grade, name.
     * Cached per request; returns [] if the table is missing.
     */
    function shs_section_rows(bool $refresh = false): array
    {
        static $rows = null;
        if ($rows !== null && !$refresh) {
            return $rows;
        }
        $rows = [];
        if (!class_exists('Database')) {
            require_once __DIR__ . '/../database/connection.php';
        }
        try {
            $order = "'" . implode("','", array_keys(shs_strands())) . "'";
            $rows = Database::getConnection()
                ->query("SELECT * FROM sections ORDER BY FIELD(strand, $order), grade, LENGTH(name), name")
                ->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log('Could not load sections: ' . $e->getMessage());
        }
        return $rows;
    }

    /** Section codes grouped by strand: ["STEM" => ["STEM-11A", "STEM-11B", ...], ...] */
    function shs_sections(): array
    {
        $out = [];
        foreach (shs_section_rows() as $row) {
            $out[$row['strand']][] = $row['code'];
        }
        return $out;
    }

    /** Distinct grade + name suffixes, e.g. ["11A", "11B", "12A"] — stored in subjects.year_level */
    function shs_section_suffixes(): array
    {
        $out = [];
        foreach (shs_section_rows() as $row) {
            $out[$row['grade'] . $row['name']] = true;
        }
        $out = array_keys($out);
        usort($out, fn($a, $b) => [substr($a, 0, 2), strlen($a), $a] <=> [substr($b, 0, 2), strlen($b), $b]);
        return array_map('strval', $out);
    }

    /** "11A" => "Grade 11 – A" */
    function shs_suffix_label(string $suffix): string
    {
        return shs_grade_label(substr($suffix, 0, 2)) . ' – ' . substr($suffix, 2);
    }

    /**
     * "STEM-11A" => ['strand' => 'STEM', 'grade' => '11', 'letter' => 'A',
     *                'grade_level' => 'Grade 11', 'suffix' => '11A']
     * Returns null when the code is not in the STRAND-GRADE+NAME format.
     */
    function shs_parse_section(string $code): ?array
    {
        if (!preg_match('/^([A-Z]+)-(\d{2})([A-Za-z0-9]+)$/', trim($code), $m)) {
            return null;
        }
        if (!isset(shs_strands()[$m[1]]) || !in_array($m[2], shs_grades(), true)) {
            return null;
        }
        return [
            'strand'      => $m[1],
            'grade'       => $m[2],
            'letter'      => $m[3],
            'grade_level' => shs_grade_label($m[2]),
            'suffix'      => $m[2] . $m[3],
        ];
    }
}
