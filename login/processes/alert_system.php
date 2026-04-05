<?php
require 'conn.php'; // Adjust the path based on your directory structure
// Get today's date in 'Y-m-d' format
$today = (new DateTime())->format('Y-m-d');
try {
    // Check if 'semester_transition' flag is false
    $flagQuery = "SELECT status FROM admin_auto_notifications WHERE name = 'semester_transition'";
    $flagStmt = $pdo->prepare($flagQuery);
    $flagStmt->execute();
    $flag = $flagStmt->fetch(PDO::FETCH_ASSOC);

    if ($flag && $flag['status'] === 'false') {
        // Fetch the current active semester where today's date matches the end_date
        $sql = "SELECT id, name FROM semester WHERE end_date = :today AND status = 'active' LIMIT 1";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':today', $today);
        $stmt->execute();
        $currentSemester = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($currentSemester) {
            // Begin a transaction
            $pdo->beginTransaction();

            // Archive the current semester
            $archiveStmt = $pdo->prepare("UPDATE semester SET status = 'archived', archived = 1 WHERE id = :id");
            $archiveStmt->bindParam(':id', $currentSemester['id'], PDO::PARAM_INT);
            $archiveStmt->execute();

            // Insert a record into the `archived_semesters` table
            $archiveReason = "Semester end reached";
            $insertArchiveStmt = $pdo->prepare("
                INSERT INTO archived_semesters (semester_id, archive_reason) 
                VALUES (:semester_id, :archive_reason)
            ");
            $insertArchiveStmt->bindParam(':semester_id', $currentSemester['id'], PDO::PARAM_INT);
            $insertArchiveStmt->bindParam(':archive_reason', $archiveReason, PDO::PARAM_STR);
            $insertArchiveStmt->execute();

            // Archive classes associated with this semester
            $disableClassStmt = $pdo->prepare("UPDATE classes SET status = 'archived' WHERE semester = :semester_name");
            $disableClassStmt->bindParam(':semester_name', $currentSemester['name'], PDO::PARAM_STR);
            $disableClassStmt->execute();

            // Insert admin notification
            $type = 'semester';
            $title = 'Semester Has Ended: ' . htmlspecialchars($currentSemester['name']);
            $description = 'The semester "' . htmlspecialchars($currentSemester['name']) . '" has ended.';
            $date = date('Y-m-d H:i:s');
            $link = '/ccs-sms.com/capstone/admin/semester_management.php';

            $insertAdminNotificationStmt = $pdo->prepare("
                INSERT INTO admin_notifications (type, title, description, date, link) 
                VALUES (:type, :title, :description, :date, :link)
            ");
            $insertAdminNotificationStmt->execute(compact('type', 'title', 'description', 'date', 'link'));

            // Notify all staff
            $staffQuery = "SELECT id, fullName, email FROM staff_accounts";
            $staffStmt = $pdo->prepare($staffQuery);
            $staffStmt->execute();
            $staffAccounts = $staffStmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($staffAccounts as $staff) {
                $user_id = $staff['id'];
                $type = 'semester';
                $title = 'Semester Has Ended: ' . htmlspecialchars($currentSemester['name']);
                $description = 'The semester "' . htmlspecialchars($currentSemester['name']) . '" has ended. Please submit the required documentary requirements to the dean!';
                $link = '/ccs-sms.com/capstone/staff/class_management.php';


                $insertStaffNotificationStmt = $pdo->prepare("
                    INSERT INTO staff_notifications (user_id, type, title, description, date, link) 
                    VALUES (:user_id, :type, :title, :description, :date, :link)
                ");
                $insertStaffNotificationStmt->execute(compact('user_id', 'type', 'title', 'description', 'date', 'link'));
            }

            // Notify all students
            $studentsQuery = "SELECT student_id FROM students_enrollments";
            $studentsStmt = $pdo->prepare($studentsQuery);
            $studentsStmt->execute();
            $studentAccounts = $studentsStmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($studentAccounts as $student) {
                $user_id = $student['student_id'];
                $type = 'semester';
                $title = 'Semester Has Ended: ' . htmlspecialchars($currentSemester['name']);
                $description = 'The semester "' . htmlspecialchars($currentSemester['name']) . '" has ended. Please wait for any official announcements from your college dean or professors!';
                $link = '/ccs-sms.com/capstone/students/student_dashboard.php';

                $insertStudentNotificationStmt = $pdo->prepare("
                    INSERT INTO student_notifications (user_id, type, title, description, date, link) 
                    VALUES (:user_id, :type, :title, :description, :date, :link)
                ");
                $insertStudentNotificationStmt->execute(compact('user_id', 'type', 'title', 'description', 'date', 'link'));
            }

            // Update the 'semester_transition' flag to prevent re-processing
            $updateFlagQuery = "UPDATE admin_auto_notifications SET status = 'true' WHERE name = 'semester_transition'";
            $pdo->prepare($updateFlagQuery)->execute();

            // Commit the transaction
            $pdo->commit();
        } else {
        }
    } else {
    }
} catch (PDOException $e) {
    $pdo->rollBack();
}

try {
    // Fetch the current status of the 'semester_near_ending' flag
    $flagQuery = "SELECT status FROM admin_auto_notifications WHERE name = 'semester_near_ending'";
    $flagStmt = $pdo->prepare($flagQuery);
    $flagStmt->execute();
    $flag = $flagStmt->fetch(PDO::FETCH_ASSOC);

    if ($flag && strtolower($flag['status']) === 'false') { // Ensure case-insensitive comparison
        $oneWeekLater = (new DateTime())->modify('+7 days');
        $oneWeekLaterStr = $oneWeekLater->format('Y-m-d');
        $currentDateStr = (new DateTime())->format('Y-m-d');

        // Fetch active semesters ending in the next week
        $query = "SELECT * FROM semester WHERE end_date BETWEEN :currentDate AND :oneWeekLater AND status = 'active'";
        $stmt = $pdo->prepare($query);
        $stmt->execute([
            ':currentDate' => $currentDateStr,
            ':oneWeekLater' => $oneWeekLaterStr,
        ]);
        $semesters = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if ($semesters) { // Only insert notifications if semesters exist
            // Begin transaction
            $pdo->beginTransaction();

            foreach ($semesters as $semester) {
                $type = 'semester';
                $title = 'Semester Ending Soon: ' . htmlspecialchars($semester['name']);
                $endDate = (new DateTime($semester['end_date']))->format('F j, Y'); // Format as "Month day, Year"
                $description = 'The semester "' . htmlspecialchars($semester['name']) . '" is ending on ' . $endDate . '.';
                $link = '/ccs-sms.com/capstone/admin/semester_management.php';

                // Insert the notification into the admin_notifications table
                $insertAdminNotificationQuery = "INSERT INTO admin_notifications (type, title, description, link) 
                                VALUES (:type, :title, :description, :link)";
                $insertAdminStmt = $pdo->prepare($insertAdminNotificationQuery);
                $insertAdminStmt->execute([
                    ':type' => $type,
                    ':title' => $title,
                    ':description' => $description,
                    ':link' => $link,
                ]);

                // Notify all staff
                $staffQuery = "SELECT id FROM staff_accounts";
                $staffStmt = $pdo->prepare($staffQuery);
                $staffStmt->execute();
                $staffAccounts = $staffStmt->fetchAll(PDO::FETCH_ASSOC);

                foreach ($staffAccounts as $staff) {
                    $user_id = $staff['id'];
                    $notificationTitle = 'Semester Ending Soon: ' . htmlspecialchars($semester['name']);
                    $notificationDescription = 'The semester "' . htmlspecialchars($semester['name']) . '" is ending soon. Please submit/complete any required documentary requirements to the dean on time!';
                    $date = date('Y-m-d H:i:s');
                    $link = '/ccs-sms.com/capstone/staff/class_management.php';
                    $insertStaffNotificationQuery = "INSERT INTO staff_notifications (user_id, type, title, description, date, link) 
                                                      VALUES (:user_id, :type, :title, :description, :date, :link)";
                    $insertStaffNotificationStmt = $pdo->prepare($insertStaffNotificationQuery);
                    $insertStaffNotificationStmt->execute([
                        ':user_id' => $user_id,
                        ':type' => $type,
                        ':title' => $notificationTitle,
                        ':description' => $notificationDescription,
                        ':date' => $date,
                        ':link' => $link,
                    ]);
                }

                // Notify all students
                $studentQuery = "SELECT student_id FROM students_enrollments";
                $studentStmt = $pdo->prepare($studentQuery);
                $studentStmt->execute();
                $studentAccounts = $studentStmt->fetchAll(PDO::FETCH_ASSOC);

                foreach ($studentAccounts as $student) {
                    $user_id = $student['student_id'];
                    $notificationTitle = 'Semester Ending Soon: ' . htmlspecialchars($semester['name']);
                    $notificationDescription = 'The semester "' . htmlspecialchars($semester['name']) . '" is ending soon. Please submit/complete any required class requirements on time!';
                    $date = date('Y-m-d H:i:s');
                    $link = '/ccs-sms.com/capstone/students/student_dashboard.php';
                    $insertStudentNotificationQuery = "INSERT INTO student_notifications (user_id, type, title, description, date, link) 
                                                       VALUES (:user_id, :type, :title, :description, :date, :link)";
                    $insertStudentNotificationStmt = $pdo->prepare($insertStudentNotificationQuery);
                    $insertStudentNotificationStmt->execute([
                        ':user_id' => $user_id,
                        ':type' => $type,
                        ':title' => $notificationTitle,
                        ':description' => $notificationDescription,
                        ':date' => $date,
                        ':link' => $link,
                    ]);
                }
            }

            // Update flag status to prevent duplicate processing
            $updateFlagQuery = "UPDATE admin_auto_notifications SET status = 'true' WHERE name = 'semester_near_ending'";
            $pdo->prepare($updateFlagQuery)->execute();

            // Commit transaction
            $pdo->commit();
        } else {
        }
    } else {
    }
} catch (PDOException $e) {
    // Rollback transaction on error
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    // Log the error

}


// **3. Process semester_ending_notice**
try {
    $flagQuery = "SELECT status FROM admin_auto_notifications WHERE name = 'semester_ending_notice'";
    $flagStmt = $pdo->prepare($flagQuery);
    $flagStmt->execute();
    $flag = $flagStmt->fetch(PDO::FETCH_ASSOC);

    if ($flag && $flag['status'] === 'false') {


        $updateFlagQuery = "UPDATE admin_auto_notifications SET status = 'true' WHERE name = 'semester_ending_notice'";
        $pdo->prepare($updateFlagQuery)->execute();
        $_SESSION['STATUS'] = "SEMESTER_ENDED_NOTICE";
    }
} catch (PDOException $e) {
}
