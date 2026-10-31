<?php
/**
 * Printable records: print.php?doc=coe|good_moral|sf9|sf10&student=<students.id>[&sy=2026-2027][&request=<id>]
 *
 *  coe         Certificate of Enrollment (current or latest enrollment record)
 *  good_moral  Certificate of Good Moral Character
 *  sf9         Report card for one school year (both semesters)
 *  sf10        Permanent record: every term on file
 *
 * Grades come from records_student_grades(); only accepted (locked) grades print,
 * anything else shows as "not final".
 */
require __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/../includes/records.php';

$doc = $_GET['doc'] ?? '';
$types = records_document_types();
$stmt = $pdo->prepare("SELECT s.*, u.email FROM students s LEFT JOIN users u ON u.id = s.user_id WHERE s.id = ?");
$stmt->execute([(int) ($_GET['student'] ?? 0)]);
$student = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$student || !isset($types[$doc])) {
    registrar_redirect('students.php', ['Not found', 'That student or document does not exist.', 'error']);
}

$request = null;
if (!empty($_GET['request'])) {
    $stmt = $pdo->prepare("SELECT * FROM document_requests WHERE id = ? AND student_row_id = ?");
    $stmt->execute([(int) $_GET['request'], $student['id']]);
    $request = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

$records = $pdo->prepare("SELECT * FROM enrollment_records WHERE student_row_id = ? ORDER BY school_year, term");
$records->execute([$student['id']]);
$records = $records->fetchAll(PDO::FETCH_ASSOC);
$recordFor = [];
foreach ($records as $r) {
    $recordFor[$r['school_year'] . '|' . $r['term']] = $r;
}

$name = trim($student['first_name'] . ' ' . ($student['middle_name'] ? $student['middle_name'] . ' ' : '') . $student['last_name']);
$nameFormal = strtoupper(trim($student['last_name'] . ', ' . $student['first_name'] . ' ' . $student['middle_name']));
$lrn = $student['lrn'] ?: '____________';
$purpose = $request['purpose'] ?? trim($_GET['purpose'] ?? '');
$issued = date('F j, Y');
$e = 'admin_e';

/** "21st", "3rd" for the certificate's date line. */
function ordinal_day(int $d): string
{
    $suffix = ($d % 100 >= 11 && $d % 100 <= 13) ? 'th' : (['th', 'st', 'nd', 'rd'][$d % 10] ?? 'th');
    return $d . $suffix;
}

$error = null;
$grades = in_array($doc, ['sf9', 'sf10'], true) ? records_student_grades($pdo, $student) : [];

if ($doc === 'coe') {
    $current = enrollment_current_term($pdo);
    $enrollment = $recordFor[$current['school_year'] . '|' . $current['term']] ?? null;
    if (!$enrollment || $enrollment['status'] !== 'enrolled') {
        $enrolledRows = array_values(array_filter($records, fn($r) => $r['status'] === 'enrolled'));
        $enrollment = end($enrolledRows) ?: null;
    }
    if (!$enrollment) {
        $error = $name . ' has no enrollment record marked Enrolled, so a Certificate of Enrollment cannot be issued.';
    }
}
if ($doc === 'good_moral') {
    $latest = end($records) ?: null;
}
if ($doc === 'sf9') {
    $years = array_unique(array_merge(array_column($records, 'school_year'), array_column($grades, 'school_year')));
    rsort($years);
    $sy = in_array($_GET['sy'] ?? '', $years, true) ? $_GET['sy'] : ($request['school_year'] ?? ($years[0] ?? enrollment_current_term($pdo)['school_year']));
    $syTerms = array_filter($grades, fn($t) => $t['school_year'] === $sy);
    $syRecord = null;
    foreach ($records as $r) {
        if ($r['school_year'] === $sy) $syRecord = $r;
    }
}

/** One semester's subjects table (SF9 and SF10). */
function grade_table(array $term): void
{
    $e = 'admin_e'; ?>
    <table class="grid">
        <thead>
            <tr><th class="left">Subjects</th><th>1st Quarter</th><th>2nd Quarter</th><th>Semester Final Grade</th><th>Remarks</th></tr>
        </thead>
        <tbody>
            <?php if (!$term['subjects']): ?>
                <tr><td colspan="5" class="muted">No subjects on record for this semester.</td></tr>
            <?php endif; ?>
            <?php foreach ($term['subjects'] as $s): ?>
                <tr>
                    <td class="left"><?= $e($s['subject']) ?></td>
                    <?php if ($s['official']): ?>
                        <td><?= $e($s['q1']) ?></td><td><?= $e($s['q2']) ?></td><td><strong><?= $e($s['final']) ?></strong></td><td><?= $e($s['remarks']) ?></td>
                    <?php else: ?>
                        <td colspan="4" class="muted">Not final yet</td>
                    <?php endif; ?>
                </tr>
            <?php endforeach; ?>
            <tr class="total">
                <td class="left" colspan="3">General Average for the Semester</td>
                <td><strong><?= $term['general_average'] !== null ? $e((string) (int) round($term['general_average'])) : '—' ?></strong></td>
                <td><?= $term['general_average'] !== null ? ($term['general_average'] >= DEPED_PASSING_GRADE ? 'Passed' : 'Failed') : '' ?></td>
            </tr>
        </tbody>
    </table>
    <?php
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= $e($types[$doc]) ?> · <?= $e($name) ?></title>
    <?php include __DIR__ . '/../includes/favicon-links.php'; ?>
    <style>
        :root { --ink: #111; --muted: #555; --line: #333; }
        * { box-sizing: border-box; }
        body { margin: 0; background: #e9e9e9; color: var(--ink); font: 11pt/1.45 "Times New Roman", Times, serif; }
        .toolbar { position: sticky; top: 0; display: flex; gap: 8px; justify-content: center; flex-wrap: wrap; padding: 10px 16px; background: #061E4E; }
        .toolbar a, .toolbar button { padding: 6px 14px; border: 1px solid #c2a74d; border-radius: 6px; background: #fff; color: #061E4E; font: 600 13px system-ui, sans-serif; text-decoration: none; cursor: pointer; }
        .toolbar button { background: #c2a74d; }
        .page { width: 210mm; min-height: 297mm; margin: 16px auto; padding: 16mm 18mm; background: #fff; box-shadow: 0 2px 12px rgba(0,0,0,.15); }
        .head { display: grid; grid-template-columns: 22mm 1fr 22mm; align-items: center; text-align: center; border-bottom: 2px solid var(--line); padding-bottom: 8px; margin-bottom: 14px; }
        .head img { width: 20mm; height: 20mm; object-fit: contain; }
        .head p { margin: 0; font-size: 10pt; }
        .head h1 { margin: 2px 0; font-size: 13pt; text-transform: uppercase; }
        h2.title { margin: 18px 0 14px; text-align: center; font-size: 15pt; letter-spacing: .08em; text-transform: uppercase; }
        .form-code { float: right; font: 9pt system-ui, sans-serif; color: var(--muted); }
        .body p { margin: 0 0 12px; text-align: justify; text-indent: 2.5em; font-size: 12pt; }
        .info { width: 100%; border-collapse: collapse; margin-bottom: 10px; font-size: 10.5pt; }
        .info td { padding: 2px 4px; vertical-align: bottom; }
        .info .v { border-bottom: 1px solid var(--line); font-weight: bold; }
        .grid { width: 100%; border-collapse: collapse; margin: 6px 0 14px; font-size: 10.5pt; }
        .grid th, .grid td { border: 1px solid var(--line); padding: 4px 6px; text-align: center; }
        .grid th { background: #f0f0f0; font-size: 9.5pt; }
        .grid .left { text-align: left; }
        .grid .total td { background: #f7f7f7; }
        .muted { color: var(--muted); font-style: italic; }
        h3 { margin: 14px 0 4px; font-size: 11pt; text-transform: uppercase; }
        .term-head { display: flex; flex-wrap: wrap; gap: 4px 18px; font-size: 10pt; margin-top: 10px; }
        .signs { display: grid; grid-template-columns: 1fr 1fr; gap: 28px 40px; margin-top: 40px; }
        .sign { text-align: center; font-size: 10.5pt; }
        .sign .line { border-top: 1px solid var(--line); padding-top: 2px; font-weight: bold; text-transform: uppercase; }
        .note { margin-top: 28px; font-size: 9pt; color: var(--muted); }
        .error { max-width: 640px; margin: 40px auto; padding: 20px; background: #fff; border-left: 4px solid #b42338; font: 15px system-ui, sans-serif; }
        @media (max-width: 820px) { .page { width: auto; min-height: 0; margin: 8px; padding: 16px; } }
        @media print {
            body { background: #fff; }
            .toolbar { display: none; }
            .page { width: auto; min-height: 0; margin: 0; padding: 0; box-shadow: none; }
            @page { size: A4; margin: 14mm 16mm; }
        }
    </style>
</head>

<body>
    <div class="toolbar">
        <a href="student.php?id=<?= (int) $student['id'] ?>">&larr; Back to <?= $e($name) ?></a>
        <?php if ($request): ?><a href="requests.php">Document requests</a><?php endif; ?>
        <?php if (!$error): ?><button type="button" onclick="window.print()">Print</button><?php endif; ?>
    </div>

    <?php if ($error): ?>
        <div class="error"><?= $e($error) ?></div>
    <?php else: ?>
    <div class="page">
        <div class="head">
            <img src="../external/img/dp_logo.avif" alt="School seal">
            <div>
                <p>Republic of the Philippines · Department of Education</p>
                <h1><?= $e(records_school_name()) ?></h1>
                <p><?= $e(records_school_address()) ?></p>
            </div>
            <span></span>
        </div>

        <?php if ($doc === 'coe'): ?>
            <h2 class="title">Certificate of Enrollment</h2>
            <div class="body">
                <p>This is to certify that <strong><?= $e(strtoupper($name)) ?></strong>, with Learner Reference Number (LRN) <strong><?= $e($lrn) ?></strong>,
                    is officially enrolled in this school as a <strong><?= $e($enrollment['grade_level']) ?></strong> student under the
                    <strong><?= $e(shs_strand_label($enrollment['strand'])) ?></strong> strand<?= $enrollment['section_code'] ? ', section <strong>' . $e($enrollment['section_code']) . '</strong>' : '' ?>,
                    for the <strong><?= $e($enrollment['term']) ?></strong> of School Year <strong><?= $e($enrollment['school_year']) ?></strong>.</p>
                <p>This certification is issued upon the request of the above-named learner<?= $purpose !== '' ? ' for <strong>' . $e(strtolower($purpose)) . '</strong> purposes' : ' for whatever legal purpose it may serve' ?>.</p>
                <p>Issued this <?= ordinal_day((int) date('j')) ?> day of <?= date('F Y') ?> at <?= $e(records_school_address()) ?>.</p>
            </div>
        <?php elseif ($doc === 'good_moral'): ?>
            <h2 class="title">Certificate of Good Moral Character</h2>
            <div class="body">
                <p>This is to certify that <strong><?= $e(strtoupper($name)) ?></strong>, with Learner Reference Number (LRN) <strong><?= $e($lrn) ?></strong>,
                    <?php if ($latest): ?>
                        is/was a <strong><?= $e($latest['grade_level']) ?></strong> learner under the <strong><?= $e(shs_strand_label($latest['strand'])) ?></strong> strand of this school during School Year <strong><?= $e($latest['school_year']) ?></strong>,
                    <?php else: ?>
                        is/was a learner of this school,
                    <?php endif; ?>
                    and has not been subjected to any disciplinary action. To the best of our knowledge, the learner is of good moral character.</p>
                <p>This certification is issued upon the request of the above-named learner<?= $purpose !== '' ? ' for <strong>' . $e(strtolower($purpose)) . '</strong> purposes' : ' for whatever legal purpose it may serve' ?>.</p>
                <p>Issued this <?= ordinal_day((int) date('j')) ?> day of <?= date('F Y') ?> at <?= $e(records_school_address()) ?>.</p>
            </div>
        <?php elseif ($doc === 'sf9'): ?>
            <span class="form-code">SF9-SHS</span>
            <h2 class="title">Learner's Progress Report Card</h2>
            <table class="info">
                <tr><td>Name:</td><td class="v" colspan="3"><?= $e($nameFormal) ?></td><td>LRN:</td><td class="v"><?= $e($lrn) ?></td></tr>
                <tr><td>Sex:</td><td class="v"><?= $e($student['gender']) ?></td><td>Grade:</td><td class="v"><?= $e($syRecord['grade_level'] ?? $student['year_level']) ?></td><td>Section:</td><td class="v"><?= $e($syRecord['section_code'] ?? '—') ?></td></tr>
                <tr><td>Track/Strand:</td><td class="v" colspan="3"><?= $e(shs_strand_label($syRecord['strand'] ?? $student['course'])) ?></td><td>School Year:</td><td class="v"><?= $e($sy) ?></td></tr>
            </table>
            <?php foreach (enrollment_terms() as $t):
                $term = $grades[$sy . '|' . $t] ?? ['term' => $t, 'subjects' => [], 'general_average' => null]; ?>
                <h3><?= $e($t) ?></h3>
                <?php grade_table($term); ?>
            <?php endforeach; ?>
            <h3>Attendance record</h3>
            <table class="grid">
                <tr><th class="left">Month</th><?php foreach (['Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec', 'Jan', 'Feb', 'Mar', 'Apr', 'May'] as $m): ?><th><?= $m ?></th><?php endforeach; ?><th>Total</th></tr>
                <?php foreach (['No. of school days', 'No. of days present', 'No. of days absent'] as $rowLabel): ?>
                    <tr><td class="left"><?= $rowLabel ?></td><?= str_repeat('<td>&nbsp;</td>', 13) ?></tr>
                <?php endforeach; ?>
            </table>
            <p class="muted" style="font-size:9pt">Descriptors: Outstanding 90–100 · Very Satisfactory 85–89 · Satisfactory 80–84 · Fairly Satisfactory 75–79 · Did Not Meet Expectations below 75 (Failed).</p>
            <div class="signs">
                <div class="sign"><div class="line">&nbsp;</div>Class Adviser</div>
                <div class="sign"><div class="line">&nbsp;</div>School Principal</div>
                <div class="sign"><div class="line">&nbsp;</div>Parent / Guardian</div>
                <div class="sign"><div class="line"><?= $e($registrarName) ?></div>School Registrar</div>
            </div>
        <?php else: /* sf10 */ ?>
            <span class="form-code">SF10-SHS</span>
            <h2 class="title">Learner's Permanent Academic Record</h2>
            <table class="info">
                <tr><td>Last name:</td><td class="v"><?= $e(strtoupper($student['last_name'])) ?></td><td>First name:</td><td class="v"><?= $e(strtoupper($student['first_name'])) ?></td><td>Middle name:</td><td class="v"><?= $e(strtoupper($student['middle_name'])) ?></td></tr>
                <tr><td>LRN:</td><td class="v"><?= $e($lrn) ?></td><td>Sex:</td><td class="v"><?= $e($student['gender']) ?></td><td>Student ID:</td><td class="v"><?= $e($student['student_id']) ?></td></tr>
            </table>
            <?php
            $keys = array_unique(array_merge(array_keys($recordFor), array_keys($grades)));
            sort($keys);
            if (!$keys): ?>
                <p class="muted">No enrollment or grade records on file.</p>
            <?php endif;
            foreach ($keys as $key):
                [$tsy, $tname] = explode('|', $key, 2);
                $rec = $recordFor[$key] ?? null;
                $term = $grades[$key] ?? ['term' => $tname, 'subjects' => [], 'general_average' => null]; ?>
                <div class="term-head">
                    <span>School: <strong><?= $e(records_school_name()) ?></strong></span>
                    <span>School Year: <strong><?= $e($tsy) ?></strong></span>
                    <span>Semester: <strong><?= $e($tname) ?></strong></span>
                    <span>Grade: <strong><?= $e($rec['grade_level'] ?? '—') ?></strong></span>
                    <span>Track/Strand: <strong><?= $e($rec ? shs_strand_label($rec['strand']) : '—') ?></strong></span>
                    <span>Section: <strong><?= $e($rec['section_code'] ?? '—') ?></strong></span>
                    <?php if ($rec && $rec['status'] !== 'enrolled' && $rec['status'] !== 'completed'): ?><span>Status: <strong><?= $e(enrollment_statuses()[$rec['status']]) ?></strong></span><?php endif; ?>
                </div>
                <?php grade_table($term); ?>
            <?php endforeach; ?>
            <div class="signs">
                <div class="sign"><div class="line"><?= $e($registrarName) ?></div>School Registrar</div>
                <div class="sign"><div class="line">&nbsp;</div>School Principal</div>
            </div>
        <?php endif; ?>

        <?php if ($doc === 'coe' || $doc === 'good_moral'): ?>
            <div class="signs" style="grid-template-columns: 1fr;">
                <div class="sign" style="justify-self:end; min-width: 70mm;"><div class="line"><?= $e($registrarName) ?></div>School Registrar</div>
            </div>
        <?php endif; ?>
        <p class="note">Issued <?= $e($issued) ?><?= $request ? ' · Request #' . (int) $request['id'] . ($request['copies'] > 1 ? ' · ' . (int) $request['copies'] . ' copies' : '') : '' ?>. Not valid without the school seal.</p>
    </div>
    <?php endif; ?>
</body>

</html>
