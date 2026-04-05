<?php
session_start();
require 'processes/server/conn.php'; // Include your PDO connection setup

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Collect form data
    $firstName = $_POST['first_name'];
    $middleName = $_POST['middle_name'];
    $lastName = $_POST['last_name'];

    $studentId = $_POST['studentId'];
    $studentName = $firstName . " " . $middleName . " " . $lastName;
    $studentEmail = $_POST['studentEmail'];
    $studentPhone = $_POST['studentPhone'];
    $courseYear = $_POST['course_year'];
    $studentAddress = $_POST['studentAddress'];
    $emergencyContact = $_POST['emergencyContact'];
    $studentGender = $_POST['studentGender'];

     // Check the selected value and assign the corresponding description
     if ($courseYear == 'BSIT-1') {
        $courseYearDescription = 'Bachelor of Science in Information Technology - 1st Year';
    } elseif ($courseYear == 'BSIT-2') {
        $courseYearDescription = 'Bachelor of Science in Information Technology - 2nd Year';
    } elseif ($courseYear == 'BSIT-3') {
        $courseYearDescription = 'Bachelor of Science in Information Technology - 3rd Year';
    } elseif ($courseYear == 'BSIT-4') {
        $courseYearDescription = 'Bachelor of Science in Information Technology - 4th Year';
    } elseif ($courseYear == 'BSCS-1') {
        $courseYearDescription = 'Bachelor of Science in Computer Science - 1st Year';
    } elseif ($courseYear == 'BSCS-2') {
        $courseYearDescription = 'Bachelor of Science in Computer Science - 2nd Year';
    } elseif ($courseYear == 'BSCS-3') {
        $courseYearDescription = 'Bachelor of Science in Computer Science - 3rd Year';
    } elseif ($courseYear == 'BSCS-4') {
        $courseYearDescription = 'Bachelor of Science in Computer Science - 4th Year';
    }

    // Check if student already exists in the database
    $checkSql = "SELECT COUNT(*) FROM student_info WHERE student_id = :studentId";
    
    try {
        $stmt = $pdo->prepare($checkSql);
        $stmt->bindParam(':studentId', $studentId);
        $stmt->execute();
        $count = $stmt->fetchColumn();

        if ($count > 0) {
            // If student exists, update the record
            $sql = "UPDATE student_info SET
                        full_name = :studentName,
                        email = :studentEmail,
                        phone_number = :studentPhone,
                        course_year = :courseYear,
                        address = :studentAddress,
                        emergency_contact = :emergencyContact,
                        gender = :studentGender
                    WHERE student_id = :studentId"; // Use student_id to identify the record

            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':studentId', $studentId);
            $stmt->bindParam(':studentName', $studentName);
            $stmt->bindParam(':studentEmail', $studentEmail);
            $stmt->bindParam(':studentPhone', $studentPhone);
            $stmt->bindParam(':courseYear', $courseYearDescription);
            $stmt->bindParam(':studentAddress', $studentAddress);
            $stmt->bindParam(':emergencyContact', $emergencyContact);
            $stmt->bindParam(':studentGender', $studentGender);

            // Execute the update statement for student_info table
            $stmt->execute();
            echo "Profile updated successfully in student_info.";

            // Update fields in the students table (fullName, first_name, middle_name, last_name)
            $updateStudentSql = "UPDATE students SET
                                    fullName = :studentName,
                                    first_name = :firstName,
                                    middle_name = :middleName,
                                    last_name = :lastName
                                  WHERE student_id = :studentId";
            
            // Update the student record
            $updateStmt = $pdo->prepare($updateStudentSql);
            $updateStmt->bindParam(':studentId', $studentId);
            $updateStmt->bindParam(':studentName', $studentName);
            $updateStmt->bindParam(':firstName', $firstName);
            $updateStmt->bindParam(':middleName', $middleName);
            $updateStmt->bindParam(':lastName', $lastName);

            // Execute the update statement for students table
            $updateStmt->execute();
            echo "Profile updated successfully in students.";
        } else {
            // If student does not exist, insert a new record in both tables
            $insertSql = "INSERT INTO student_info (student_id, full_name, email, phone_number, course_year, address, emergency_contact, gender)
                          VALUES (:studentId, :studentName, :studentEmail, :studentPhone, :courseYear, :studentAddress, :emergencyContact, :studentGender)";

            $stmt = $pdo->prepare($insertSql);
            $stmt->bindParam(':studentId', $studentId);
            $stmt->bindParam(':studentName', $studentName);
            $stmt->bindParam(':studentEmail', $studentEmail);
            $stmt->bindParam(':studentPhone', $studentPhone);
            $stmt->bindParam(':courseYear', $courseYearDescription);
            $stmt->bindParam(':studentAddress', $studentAddress);
            $stmt->bindParam(':emergencyContact', $emergencyContact);
            $stmt->bindParam(':studentGender', $studentGender);

            // Execute the insert statement for student_info table
            $stmt->execute();
            echo "Profile created successfully in student_info.";

       

         
        }
        $_SESSION['STATUS'] = "PROF_UPD_SUCCESS";
    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage();
        $_SESSION['STATUS'] = "PROF_UPD_ERROR";
    }
}
header("Location: student_dashboard.php"); // Change this to your desired page
?>
