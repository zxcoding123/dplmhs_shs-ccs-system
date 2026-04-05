<?php
date_default_timezone_set('Asia/Singapore'); // or 'Asia/Manila'
require 'conn.php'; // Adjust the path based on your directory structure
session_start();

// Get today's date in 'Y-m-d' format
$today = (new DateTime())->format('Y-m-d');

try {
    // Fetch the current active semester where today's date matches the end_date
    $sql = "SELECT id, name FROM semester WHERE end_date = :today AND status = 'active' LIMIT 1";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':today', $today);
    $stmt->execute();
    $currentSemester = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($currentSemester) {
        // Begin a transaction
        $pdo->beginTransaction();

        try {
            // Delete all records from `current_semester`
            $deleteCurrentSemesterStmt = $pdo->prepare("DELETE FROM current_semester");
            $deleteCurrentSemesterStmt->execute();

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
            $disableClassStmt = $pdo->prepare("UPDATE classes SET status = 'archived', is_archived = 1 WHERE semester = :semester_name");
            $disableClassStmt->bindParam(':semester_name', $currentSemester['name'], PDO::PARAM_STR);
            $disableClassStmt->execute();

            // Archive subjects associated with this semester
            $disableSubjectsStmt = $pdo->prepare("UPDATE subjects SET is_archived = 1 WHERE semester = :semester_name");
            $disableSubjectsStmt->bindParam(':semester_name', $currentSemester['name'], PDO::PARAM_STR);
            $disableSubjectsStmt->execute();

            // Insert admin notification
            $type = 'semester';
            $title = 'Semester Has Ended: ' . htmlspecialchars($currentSemester['name']);
            $description = 'The semester "' . htmlspecialchars($currentSemester['name']) . '" has ended.';
            $date = date('Y-m-d H:i:s');
            $link = '/admin/semester_management.php';

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
                $link = '/staff/class_management.php';

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
                $link = '/students/student_dashboard.php';

                $insertStudentNotificationStmt = $pdo->prepare("
                    INSERT INTO student_notifications (user_id, type, title, description, date, link) 
                    VALUES (:user_id, :type, :title, :description, :date, :link)
                ");
                $insertStudentNotificationStmt->execute(compact('user_id', 'type', 'title', 'description', 'date', 'link'));
            }

            // Set session status
            $_SESSION['STATUS'] = "SEMESTER_ENDED_NOTICE";

            // Commit the transaction
            $pdo->commit();
        } catch (PDOException $e) {
            // Roll back the transaction on error
            $pdo->rollBack();
            echo "Error: " . $e->getMessage();
        }
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
try {
    // Get today's date
    $today = new DateTime();
    $todayStr = $today->format('Y-m-d');
    $threeDaysLater = clone $today;
    $threeDaysLater->modify('+3 days');
    $threeDaysLaterStr = $threeDaysLater->format('Y-m-d');

    // Fetch active semesters ending within the next 3 days
    $query = "SELECT * FROM semester WHERE end_date BETWEEN :today AND :threeDaysLater AND status = 'active'";
    $stmt = $pdo->prepare($query);
    $stmt->execute([
        ':today' => $todayStr,
        ':threeDaysLater' => $threeDaysLaterStr
    ]);

    $semesters = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!empty($semesters)) {
        try {
            $pdo->beginTransaction(); // Start transaction

            foreach ($semesters as $semester) {
                $title = 'Semester Ending Soon: ' . htmlspecialchars($semester['name']);
                $description = "The semester '{$semester['name']}' is ending soon. Please complete all required tasks.";
                $endDate = (new DateTime($semester['end_date']))->format('F j, Y');

                // 🔍 Check if admin notification already exists
                $checkAdminQuery = "SELECT COUNT(*) FROM admin_notifications WHERE title = :title";
                $checkAdminStmt = $pdo->prepare($checkAdminQuery);
                $checkAdminStmt->execute([':title' => $title]);
                
                if ($checkAdminStmt->fetchColumn() == 0) {
                    $pdo->prepare(
                        "INSERT INTO admin_notifications (type, title, description, link) 
                        VALUES ('semester', :title, :description, '/admin/semester_management.php')"
                    )->execute([
                        ':title' => $title,
                        ':description' => $description
                    ]);
                }

                // ✅ Notify staff and students in a single loop
                $users = $pdo->query("SELECT id, 'staff' AS role FROM staff_accounts UNION ALL SELECT student_id AS id, 'student' AS role FROM students_enrollments")->fetchAll(PDO::FETCH_ASSOC);
                
                foreach ($users as $user) {
                    $notificationTable = ($user['role'] === 'staff') ? 'staff_notifications' : 'student_notifications';
                    $userLink = ($user['role'] === 'staff') ? '/staff/class_management.php' : '/students/student_dashboard.php';
                    $userDesc = ($user['role'] === 'staff') 
                        ? "The semester '{$semester['name']}' is ending on {$endDate}. Please finalize your records."
                        : "The semester '{$semester['name']}' is ending soon. Submit all required work before {$endDate}!";
                    
                    // Check if notification already exists for user
                    $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM $notificationTable WHERE user_id = :user_id AND title = :title");
                    $checkStmt->execute([':user_id' => $user['id'], ':title' => $title]);
                    
                    if ($checkStmt->fetchColumn() == 0) {
                        $pdo->prepare(
                            "INSERT INTO $notificationTable (user_id, type, title, description, date, link) 
                            VALUES (:user_id, 'semester', :title, :description, NOW(), :link)"
                        )->execute([
                            ':user_id' => $user['id'],
                            ':title' => $title,
                            ':description' => $userDesc,
                            ':link' => $userLink
                        ]);
                    }
                }
            }
            
            $pdo->commit(); // Commit transaction if everything is successful
        } catch (Exception $e) {
            $pdo->rollBack(); // Rollback transaction in case of error
            error_log("Notification Error: " . $e->getMessage()); // Log the error for debugging
        }
    }

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "Error: " . $e->getMessage();
}
// 🚨 Attendance Warnings
try {
    $stmt_classes = $pdo->prepare("
        SELECT c.id AS class_id, s.id AS teacher_id 
        FROM classes c
        JOIN staff_accounts s ON c.teacher = s.fullName
        WHERE c.is_archived = 0
    ");
    $stmt_classes->execute();
    $active_classes = $stmt_classes->fetchAll(PDO::FETCH_ASSOC);

    if (!empty($active_classes)) {
        foreach ($active_classes as $class) {
            $class_id = $class['class_id'];
            $teacher_id = $class['teacher_id'];

            // Count absences per student in this class (only if >= 3)
            $stmt_absences = $pdo->prepare("
                SELECT student_id, COUNT(*) as absent_count
                FROM attendance
                WHERE status = 'absent' AND class_id = ?
                GROUP BY student_id
                HAVING absent_count >= 3
            ");
            $stmt_absences->execute([$class_id]);
            $students_with_warnings = $stmt_absences->fetchAll(PDO::FETCH_ASSOC);

            if (!empty($students_with_warnings)) {
                foreach ($students_with_warnings as $student) {
                    $student_id = $student['student_id'];
                    $absent_count = $student['absent_count'];
                    $warningTitle = 'Attendance Warning';

                    // 🔍 Check if student already has this warning
                    $checkStudentWarningStmt = $pdo->prepare("SELECT COUNT(*) FROM student_notifications WHERE user_id = :user_id AND title = :title");
                    $checkStudentWarningStmt->execute([':user_id' => $student_id, ':title' => $warningTitle]);

                    if ($checkStudentWarningStmt->fetchColumn() == 0) {
                        $pdo->prepare("
                            INSERT INTO student_notifications (user_id, type, title, description, date, link) 
                            VALUES (:user_id, 'attendance_warning', :title, :description, NOW(), '/student/attendance.php')
                        ")->execute([
                            ':user_id' => $student_id,
                            ':title' => $warningTitle,
                            ':description' => "You have {$absent_count} absences in class (ID: {$class_id}). Further absences may affect your grade."
                        ]);
                    }

                    // 🔍 Check if teacher already has this warning
                    $checkTeacherWarningStmt = $pdo->prepare("SELECT COUNT(*) FROM staff_notifications WHERE user_id = :user_id AND title = :title");
                    $checkTeacherWarningStmt->execute([':user_id' => $teacher_id, ':title' => $warningTitle]);

                    if ($checkTeacherWarningStmt->fetchColumn() == 0) {
                        $pdo->prepare("
                            INSERT INTO staff_notifications (user_id, type, title, description, date, link) 
                            VALUES (:user_id, 'attendance_warning', :title, :description, NOW(), '/staff/class_management.php')
                        ")->execute([
                            ':user_id' => $teacher_id,
                            ':title' => $warningTitle,
                            ':description' => "A student (ID: {$student_id}) in your class (ID: {$class_id}) has accumulated {$absent_count} absences."
                        ]);
                    }
                }
            }
        }
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}


?>