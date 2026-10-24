<?php
/**
 * School calendar: national holidays, DepEd school-year dates and school events.
 *
 *   require_once __DIR__ . '/../includes/school-calendar.php';
 *   calendar_ensure($pdo);                       // creates the table, loads official dates once
 *   $events = calendar_rows($pdo, publicOnly: true);
 *
 * Official dates (Philippine holidays and the DepEd calendar) ship as presets, keyed by
 * preset_key. Admins can edit them (is_modified), remove them (is_hidden, so reloading
 * the presets doesn't bring them back silently) and restore or reset them later.
 */

const CALENDAR_SOURCES = [
    'national' => 'National holiday',
    'deped' => 'DepEd calendar',
    'school' => 'School event',
];

/** category => [label, color, classes suspended by default] */
const CALENDAR_CATEGORIES = [
    'regular_holiday' => ['Regular holiday', '#b42338', true],
    'special_holiday' => ['Special non-working day', '#c46a12', true],
    'special_working' => ['Special working day', '#6b7280', false],
    'term' => ['School year / term', '#2f5bb7', false],
    'school_break' => ['School break', '#7c3aed', true],
    'exam' => ['Exams & assessment', '#0f766e', false],
    'school_event' => ['School activity', '#c2a74d', false],
    'suspension' => ['Class suspension', '#1f2430', true],
    'custom' => ['Custom', '#475569', false],
];

/** [label, color] for a row; custom rows carry their own label and colour. */
function calendar_category(array $row): array
{
    $cat = CALENDAR_CATEGORIES[$row['category']] ?? CALENDAR_CATEGORIES['school_event'];
    if ($row['category'] === 'custom') {
        return [($row['custom_label'] ?? '') !== '' ? $row['custom_label'] : $cat[0], calendar_valid_color($row['color'] ?? '') ?: $cat[1]];
    }
    return [$cat[0], $cat[1]];
}

function calendar_valid_color(string $color): string
{
    return preg_match('/^#[0-9a-fA-F]{6}$/', $color) ? strtolower($color) : '';
}

/** Distinct custom types already used, for the legend and the type suggestions. */
function calendar_custom_types(array $rows): array
{
    $types = [];
    foreach ($rows as $r) {
        if ($r['category'] === 'custom' && ($r['custom_label'] ?? '') !== '') {
            $types[mb_strtolower($r['custom_label'])] ??= calendar_category($r);
        }
    }
    return array_values($types);
}

/**
 * Built-in official dates. Sources:
 *  - 2026 holidays: Proclamation No. 1006, s. 2025
 *  - 2027 holidays: Proclamation No. 1427, s. 2026
 *  - SY 2026–2027: DepEd Order No. 009, s. 2026 (three-term calendar)
 * Eid'l Fitr / Eid'l Adha are proclaimed separately each year, so those dates are estimates.
 */
function calendar_presets(): array
{
    $eid = 'Islamic holiday. The date is proclaimed separately each year on the recommendation of the NCMF. This is an estimate; adjust it once the proclamation is out.';
    $ph = fn(string $year, string $proc) => "Declared for $year by $proc.";

    $years = [
        '2026' => ['proc' => 'Proclamation No. 1006, s. 2025', 'days' => [
            ['new-year', '01-01', "New Year's Day", 'regular_holiday'],
            ['chinese-new-year', '02-17', 'Chinese New Year', 'special_holiday'],
            ['edsa', '02-25', 'EDSA People Power Revolution Anniversary', 'special_working'],
            ['eid-fitr', '03-20', "Eid'l Fitr (Feast of Ramadan)", 'regular_holiday', $eid],
            ['maundy-thursday', '04-02', 'Maundy Thursday', 'regular_holiday'],
            ['good-friday', '04-03', 'Good Friday', 'regular_holiday'],
            ['black-saturday', '04-04', 'Black Saturday', 'special_holiday'],
            ['kagitingan', '04-09', 'Araw ng Kagitingan', 'regular_holiday'],
            ['labor-day', '05-01', 'Labor Day', 'regular_holiday'],
            ['eid-adha', '05-27', "Eid'l Adha (Feast of Sacrifice)", 'regular_holiday', $eid],
            ['independence', '06-12', 'Independence Day', 'regular_holiday'],
            ['ninoy', '08-21', 'Ninoy Aquino Day', 'special_holiday'],
            ['heroes', '08-31', 'National Heroes Day', 'regular_holiday'],
            ['all-saints', '11-01', "All Saints' Day", 'special_holiday'],
            ['all-souls', '11-02', "All Souls' Day", 'special_holiday'],
            ['bonifacio', '11-30', 'Bonifacio Day', 'regular_holiday'],
            ['immaculate', '12-08', 'Feast of the Immaculate Conception of Mary', 'special_holiday'],
            ['christmas-eve', '12-24', 'Christmas Eve', 'special_holiday'],
            ['christmas', '12-25', 'Christmas Day', 'regular_holiday'],
            ['rizal', '12-30', 'Rizal Day', 'regular_holiday'],
            ['year-end', '12-31', 'Last Day of the Year', 'special_holiday'],
        ]],
        '2027' => ['proc' => 'Proclamation No. 1427, s. 2026', 'days' => [
            ['new-year', '01-01', "New Year's Day", 'regular_holiday'],
            ['chinese-new-year', '02-06', 'Chinese New Year', 'special_holiday'],
            ['edsa', '02-25', 'EDSA People Power Revolution Anniversary', 'special_working'],
            ['eid-fitr', '03-10', "Eid'l Fitr (Feast of Ramadan)", 'regular_holiday', $eid],
            ['maundy-thursday', '03-25', 'Maundy Thursday', 'regular_holiday'],
            ['good-friday', '03-26', 'Good Friday', 'regular_holiday'],
            ['black-saturday', '03-27', 'Black Saturday', 'special_holiday'],
            ['kagitingan', '04-09', 'Araw ng Kagitingan', 'regular_holiday'],
            ['labor-day', '05-01', 'Labor Day', 'regular_holiday'],
            ['eid-adha', '05-17', "Eid'l Adha (Feast of Sacrifice)", 'regular_holiday', $eid],
            ['independence', '06-12', 'Independence Day', 'regular_holiday'],
            ['ninoy', '08-21', 'Ninoy Aquino Day', 'special_holiday'],
            ['heroes', '08-30', 'National Heroes Day', 'regular_holiday'],
            ['all-saints', '11-01', "All Saints' Day", 'special_holiday'],
            ['all-souls', '11-02', "All Souls' Day", 'special_holiday'],
            ['bonifacio', '11-30', 'Bonifacio Day', 'regular_holiday'],
            ['immaculate', '12-08', 'Feast of the Immaculate Conception of Mary', 'special_holiday'],
            ['christmas-eve', '12-24', 'Christmas Eve', 'special_holiday'],
            ['christmas', '12-25', 'Christmas Day', 'regular_holiday'],
            ['rizal', '12-30', 'Rizal Day', 'regular_holiday'],
            ['year-end', '12-31', 'Last Day of the Year', 'special_holiday'],
        ]],
    ];

    $presets = [];
    foreach ($years as $year => $set) {
        foreach ($set['days'] as $d) {
            $date = "$year-{$d[1]}";
            $presets[] = [
                'preset_key' => "ph-$year-{$d[0]}",
                'title' => $d[2],
                'description' => $d[4] ?? $ph($year, $set['proc']),
                'start_date' => $date,
                'end_date' => $date,
                'category' => $d[3],
                'source' => 'national',
            ];
        }
    }

    $do = 'DepEd Order No. 009, s. 2026 (SY 2026–2027 three-term calendar).';
    $deped = [
        ['opening', '2026-06-08', '2026-06-11', 'Opening of SY 2026–2027 · Term 1 opening activities', 'term'],
        ['t1-classes', '2026-06-15', '2026-06-15', 'Term 1 regular classes begin', 'term', 'Term 1 classes run until September 1, 2026. ' . $do],
        ['t1-end', '2026-09-02', '2026-09-15', 'Term 1 end-of-term activities', 'exam'],
        ['t2-classes', '2026-09-16', '2026-09-16', 'Term 2 classes begin', 'term', 'Term 2 classes run until December 4, 2026. ' . $do],
        ['t2-end', '2026-12-07', '2026-12-18', 'Term 2 end-of-term activities', 'exam'],
        ['christmas-break', '2026-12-19', '2027-01-03', 'Christmas break', 'school_break', 'Falls between the end of Term 2 and the start of Term 3. ' . $do . ' Check the exact dates against the order.'],
        ['t3-classes', '2027-01-04', '2027-01-04', 'Term 3 classes begin', 'term', 'Term 3 classes run until March 23, 2027. ' . $do],
        ['t3-end', '2027-03-24', '2027-04-08', 'Term 3 end-of-term activities', 'exam'],
        ['closing', '2027-04-08', '2027-04-08', 'End of SY 2026–2027', 'term'],
    ];
    foreach ($deped as $d) {
        $presets[] = [
            'preset_key' => "deped-2627-{$d[0]}",
            'title' => $d[3],
            'description' => $d[5] ?? $do,
            'start_date' => $d[1],
            'end_date' => $d[2],
            'category' => $d[4],
            'source' => 'deped',
        ];
    }

    foreach ($presets as &$p) {
        $p['no_classes'] = CALENDAR_CATEGORIES[$p['category']][2] ? 1 : 0;
    }
    return $presets;
}

function calendar_preset_map(): array
{
    return array_column(calendar_presets(), null, 'preset_key');
}

/** Creates the table on first use and loads the official dates the first time. */
function calendar_ensure(PDO $pdo): void
{
    static $done = false;
    if ($done) {
        return;
    }
    $done = true;

    $pdo->exec("CREATE TABLE IF NOT EXISTS `school_calendar` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `title` varchar(150) NOT NULL,
        `description` text DEFAULT NULL,
        `start_date` date NOT NULL,
        `end_date` date NOT NULL,
        `category` varchar(30) NOT NULL,
        `custom_label` varchar(50) DEFAULT NULL,
        `color` char(7) DEFAULT NULL,
        `source` enum('national','deped','school') NOT NULL DEFAULT 'school',
        `no_classes` tinyint(1) NOT NULL DEFAULT 0,
        `is_public` tinyint(1) NOT NULL DEFAULT 1,
        `preset_key` varchar(60) DEFAULT NULL,
        `is_modified` tinyint(1) NOT NULL DEFAULT 0,
        `is_hidden` tinyint(1) NOT NULL DEFAULT 0,
        `created_at` datetime NOT NULL DEFAULT current_timestamp(),
        `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
        PRIMARY KEY (`id`),
        UNIQUE KEY `uniq_calendar_preset` (`preset_key`),
        KEY `idx_calendar_dates` (`start_date`, `end_date`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
    // Tables created before custom types existed
    if (!$pdo->query("SHOW COLUMNS FROM school_calendar LIKE 'custom_label'")->fetch()) {
        $pdo->exec("ALTER TABLE school_calendar
            ADD COLUMN `custom_label` varchar(50) DEFAULT NULL AFTER `category`,
            ADD COLUMN `color` char(7) DEFAULT NULL AFTER `custom_label`");
    }

    if ((int) $pdo->query("SELECT COUNT(*) FROM school_calendar WHERE preset_key IS NOT NULL")->fetchColumn() === 0) {
        calendar_load_presets($pdo);
    }
}

/**
 * Adds official dates that aren't in the table yet. Dates an admin edited are left alone;
 * with $restoreHidden, removed ones come back too. Returns how many rows changed.
 */
function calendar_load_presets(PDO $pdo, bool $restoreHidden = false): int
{
    $insert = $pdo->prepare("INSERT IGNORE INTO school_calendar
        (title, description, start_date, end_date, category, source, no_classes, preset_key)
        VALUES (:title, :description, :start_date, :end_date, :category, :source, :no_classes, :preset_key)");
    $changed = 0;
    foreach (calendar_presets() as $p) {
        $insert->execute($p);
        $changed += $insert->rowCount();
    }
    if ($restoreHidden) {
        $changed += $pdo->exec("UPDATE school_calendar SET is_hidden = 0 WHERE is_hidden = 1 AND preset_key IS NOT NULL");
    }
    return $changed;
}

/** Puts an official date back to its built-in values. */
function calendar_reset_preset(PDO $pdo, int $id): bool
{
    $stmt = $pdo->prepare("SELECT preset_key FROM school_calendar WHERE id = ?");
    $stmt->execute([$id]);
    $preset = calendar_preset_map()[$stmt->fetchColumn() ?: ''] ?? null;
    if (!$preset) {
        return false;
    }
    $update = $pdo->prepare("UPDATE school_calendar SET title = :title, description = :description,
        start_date = :start_date, end_date = :end_date, category = :category, custom_label = NULL, color = NULL, source = :source,
        no_classes = :no_classes, is_public = 1, is_modified = 0, is_hidden = 0 WHERE id = :id");
    return $update->execute(array_diff_key($preset, ['preset_key' => 1]) + ['id' => $id]);
}

function calendar_rows(PDO $pdo, bool $publicOnly = false, bool $includeHidden = false): array
{
    $where = [];
    if (!$includeHidden) {
        $where[] = 'is_hidden = 0';
    }
    if ($publicOnly) {
        $where[] = 'is_public = 1';
    }
    $sql = "SELECT * FROM school_calendar" . ($where ? ' WHERE ' . implode(' AND ', $where) : '')
        . " ORDER BY start_date, end_date, title";
    return $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
}

/** Rows shaped for FullCalendar (all-day; FullCalendar's end date is exclusive). */
function calendar_fullcalendar_events(array $rows): array
{
    return array_map(function ($r) {
        $cat = calendar_category($r);
        return [
            'id' => (string) $r['id'],
            'title' => $r['title'],
            'start' => $r['start_date'],
            'end' => date('Y-m-d', strtotime($r['end_date'] . ' +1 day')),
            'allDay' => true,
            'color' => $cat[1],
            'extendedProps' => [
                'description' => (string) $r['description'],
                'category' => $r['category'],
                'categoryLabel' => $cat[0],
                'customLabel' => (string) ($r['custom_label'] ?? ''),
                'color' => $cat[1],
                'source' => $r['source'],
                'sourceLabel' => CALENDAR_SOURCES[$r['source']] ?? '',
                'noClasses' => (bool) $r['no_classes'],
                'isPublic' => (bool) $r['is_public'],
                'isPreset' => $r['preset_key'] !== null,
                'isModified' => (bool) $r['is_modified'],
                'startDate' => $r['start_date'],
                'endDate' => $r['end_date'],
            ],
        ];
    }, $rows);
}

/** "Mon, Jun 8" or "Dec 19 – Jan 3, 2027" */
function calendar_date_range(string $start, string $end): string
{
    $s = strtotime($start);
    $e = strtotime($end);
    if ($start === $end) {
        return date('D, M j, Y', $s);
    }
    if (date('Y', $s) !== date('Y', $e)) {
        return date('M j, Y', $s) . ' – ' . date('M j, Y', $e);
    }
    return date('M j', $s) . ' – ' . (date('m', $s) === date('m', $e) ? date('j', $e) : date('M j', $e)) . ', ' . date('Y', $e);
}
