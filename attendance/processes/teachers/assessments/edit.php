<?php
session_start();
require_once '../../server/conn.php';


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get the assessment ID from URL

    $assessment_id = $_GET['id'];
    $subject_id = $_GET['subject_id'];

    // Initialize variables
    $title = $_POST['title'];
    $description = $_POST['description'];
    $dueDate = $_POST['dueDate'];
    $dueTime = $_POST['dueTime'];
    $points = $_POST['points'];
    $passingPoints = $_POST['passingPoints'];
    $assessmentType = $_POST['assessment_type'];
    $attachment = null;

    // Check if the name already exists for the same subject and type
    $sql_check = "SELECT COUNT(*) FROM assessments WHERE name = :name AND type = :type AND subject_id = :subject_id AND id != :assessment_id";
    $stmt_check = $pdo->prepare($sql_check);
    $stmt_check->bindParam(':name', $title, PDO::PARAM_STR);
    $stmt_check->bindParam(':type', $assessmentType, PDO::PARAM_STR);
    $stmt_check->bindParam(':subject_id', $subject_id, PDO::PARAM_INT);
    $stmt_check->bindParam(':assessment_id', $assessment_id, PDO::PARAM_INT);
    $stmt_check->execute();

    $name_exists = $stmt_check->fetchColumn();

    if ($name_exists) {
        $_SESSION['STATUS'] = "ASSESSMENT_NAME_EXISTS_ERROR";
        header('Location: ../../../teacher_subject_management_activity_editable.php?id=' . $assessment_id . '&subject_id='. $subject_id);
        exit();
    }

    // Handle file upload
    if (isset($_FILES['fileInput']) && $_FILES['fileInput']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['fileInput']['tmp_name'];
        $fileName = $_FILES['fileInput']['name'];
        $fileNameCmps = explode(".", $fileName);
        $fileExtension = strtolower(end($fileNameCmps));

        // Define allowed file extensions
        $allowedFileExtensions = array('jpg', 'jpeg', 'png', 'pdf', 'docx', 'xlsx', 'mp4', 'mpeg');

        if (in_array($fileExtension, $allowedFileExtensions)) {
            // Generate unique file name based on subject and assessment name
            $uploadFileDir = '../../../../uploads/files/';
            $newFileName = $subject_id . "_" . $assessmentType . "_" . time() . "_" . $fileName;
            $dest_path = $uploadFileDir . $newFileName;

            // Delete the previous file if exists
            $sql_get_old_file = "SELECT attachment FROM assessments WHERE id = :id";
            $stmt_get_old_file = $pdo->prepare($sql_get_old_file);
            $stmt_get_old_file->bindParam(':id', $assessment_id, PDO::PARAM_INT);
            $stmt_get_old_file->execute();
            $old_file = $stmt_get_old_file->fetchColumn();

            if ($old_file && file_exists($uploadFileDir . $old_file)) {
                unlink($uploadFileDir . $old_file);
            }

            // Move new uploaded file to the destination directory
            if (move_uploaded_file($fileTmpPath, $dest_path)) {
                $attachment = $newFileName;
            } else {
                $_SESSION['STATUS'] = "ASSESSMENT_FILE_PATHING_ERROR";
                header('Location: ../../../teacher_subject_management_activity.php?id=' . $subject_id);
                exit();
            }
        } else {
            $_SESSION['STATUS'] = "ASSESSMENT_FILE_HANDLING_ERROR";
            header('Location: ../../../teacher_subject_management_activity.php?id=' . $subject_id);
            exit();
        }
    }

    // Update the assessment details
    try {
        if ($attachment !== null) {
            $sql_update = "UPDATE assessments
                           SET name = :name, 
                               description = :description, 
                               due_date = :due_date, 
                               due_time = :due_time, 
                               max_points = :max_points, 
                               passing_points = :passing_points, 
                               type = :type, 
                               attachment = :attachment
                           WHERE id = :id";
        } else {
            // Do not update the attachment if a new file is not uploaded
            $sql_update = "UPDATE assessments
                           SET name = :name, 
                               description = :description, 
                               due_date = :due_date, 
                               due_time = :due_time, 
                               max_points = :max_points, 
                               passing_points = :passing_points, 
                               type = :type
                           WHERE id = :id";
        }

        $stmt_update = $pdo->prepare($sql_update);
        $stmt_update->bindParam(':id', $assessment_id, PDO::PARAM_INT);
        $stmt_update->bindParam(':name', $title, PDO::PARAM_STR);
        $stmt_update->bindParam(':description', $description, PDO::PARAM_STR);
        $stmt_update->bindParam(':due_date', $dueDate, PDO::PARAM_STR);
        $stmt_update->bindParam(':due_time', $dueTime, PDO::PARAM_STR);
        $stmt_update->bindParam(':max_points', $points, PDO::PARAM_INT);
        $stmt_update->bindParam(':passing_points', $passingPoints, PDO::PARAM_INT);
        $stmt_update->bindParam(':type', $assessmentType, PDO::PARAM_STR);
        if ($attachment !== null) {
            $stmt_update->bindParam(':attachment', $attachment, PDO::PARAM_STR);
        }

        $stmt_update->execute();

        $_SESSION['STATUS'] = "ASSESSMENT_UPDATE_SUCCESS";
    } catch (PDOException $e) {
        $_SESSION['STATUS'] = "ASSESSMENT_UPDATE_ERROR: " . $e->getMessage();
    }

    // Redirect back to the management page
    header('Location: ../../../teacher_subject_management_activity_dashboard.php?id=' . $subject_id);
    exit();
}
?>
