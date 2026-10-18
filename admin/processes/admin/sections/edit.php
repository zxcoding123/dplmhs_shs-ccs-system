<?php
/**
 * Update a section. When its code changes (e.g. STEM-11A -> STEM-11Rizal), every place that
 * stores the code is renamed with it: advisers, classes, announcements and the section's subjects.
 * Students advised in the section follow a strand or grade change.
 */
session_start();
require_once __DIR__ . '/../../../includes/guard.php';
require __DIR__ . '/../../../../database/connection.php';
require_once __DIR__ . '/../../../../includes/academic.php';

$back = '../../../section_management.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: $back");
    exit();
}

$id = (int) ($_POST['id'] ?? 0);
$strand = $_POST['strand'] ?? '';
$grade = $_POST['grade'] ?? '';
$name = trim($_POST['name'] ?? '');
$room = trim($_POST['room'] ?? '');

if (!$id || !isset(shs_strands()[$strand]) || !in_array($grade, shs_grades(), true)
    || !shs_valid_section_name($name) || mb_strlen($room) > 50) {
    $_SESSION['STATUS'] = 'ADMIN_SECTION_INVALID';
    header("Location: $back");
    exit();
}

try {
    $find = $pdo->prepare("SELECT * FROM sections WHERE id = :id");
    $find->execute(['id' => $id]);
    $old = $find->fetch(PDO::FETCH_ASSOC);
    if (!$old) {
        $_SESSION['STATUS'] = 'ADMIN_SECTION_UPDATE_ERROR';
        header("Location: $back");
        exit();
    }

    $code = shs_section_code($strand, $grade, $name);
    $oldCode = $old['code'];

    if ($code !== $oldCode) {
        $exists = $pdo->prepare("SELECT COUNT(*) FROM sections WHERE code = :code AND id <> :id");
        $exists->execute(['code' => $code, 'id' => $id]);
        if ($exists->fetchColumn() > 0) {
            $_SESSION['STATUS'] = 'ADMIN_SECTION_EXISTS';
            header("Location: $back");
            exit();
        }
    }

    $pdo->beginTransaction();

    $pdo->prepare("UPDATE sections SET strand = :strand, grade = :grade, name = :name, code = :code, room = :room WHERE id = :id")
        ->execute([
            'strand' => $strand,
            'grade' => $grade,
            'name' => $name,
            'code' => $code,
            'room' => $room !== '' ? $room : null,
            'id' => $id,
        ]);

    if ($code !== $oldCode) {
        $rename = ['old' => $oldCode, 'new' => $code];
        $pdo->prepare("UPDATE staff_advising SET class_advising = :new WHERE class_advising = :old")->execute($rename);
        $pdo->prepare("UPDATE classes SET name = :new WHERE name = :old")->execute($rename);
        $pdo->prepare("UPDATE announcements SET class_name = :new WHERE class_name = :old")->execute($rename);
        $pdo->prepare("UPDATE subjects SET course = :strand, year_level = :suffix WHERE course = :old_strand AND year_level = :old_suffix")
            ->execute([
                'strand' => $strand,
                'suffix' => $grade . $name,
                'old_strand' => $old['strand'],
                'old_suffix' => $old['grade'] . $old['name'],
            ]);
    }

    if ($strand !== $old['strand'] || $grade !== $old['grade']) {
        // Students advised in this section move with it
        $pdo->prepare("UPDATE students s
            JOIN students_advising sa ON sa.student_id = s.id
            JOIN staff_advising st ON st.id = sa.staff_advising_id
            SET s.course = :strand, s.year_level = :year_level
            WHERE st.class_advising = :code")
            ->execute(['strand' => $strand, 'year_level' => shs_grade_label($grade), 'code' => $code]);
    }

    $pdo->commit();
    $_SESSION['STATUS'] = 'ADMIN_SECTION_UPDATE_SUCCESS';
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Section update failed: ' . $e->getMessage());
    $_SESSION['STATUS'] = 'ADMIN_SECTION_UPDATE_ERROR';
}

header("Location: $back");
exit();
