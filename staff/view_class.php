<?php
session_start();
if (!isset($_SESSION['teacher_id'])) {
    $_SESSION['STATUS'] = "TEACHER_NOT_LOGGED_IN";
    header("Location: ../login/index.php");
}

include('processes/server/conn.php');
?>

<?php
// Assuming $pdo is already initialized for database connection

// Check if a class ID is passed
if (isset($_GET['class_id'])) {
    $class_id = $_GET['class_id'];

    $classQuery = "SELECT id, name AS class_name, subject, teacher, semester
    FROM classes
    WHERE id = :class_id";
    $classStmt = $pdo->prepare($classQuery);
    $classStmt->bindParam(':class_id', $class_id, PDO::PARAM_INT);
    $classStmt->execute();
    $classDetails = $classStmt->fetch(PDO::FETCH_ASSOC);

    // 2. Fetch Teacher Details (only if teacher ID exists in class)
    if (isset($classDetails['teacher'])) {
        $teacher_id = $classDetails['teacher']; // Get the teacher's ID

        $teacherQuery = "SELECT fullName AS teacher_name FROM staff_accounts WHERE id = :teacher_id";
        $teacherStmt = $pdo->prepare($teacherQuery);
        $teacherStmt->bindParam(':teacher_id', $teacher_id, PDO::PARAM_INT);
        $teacherStmt->execute();
        $teacherDetails = $teacherStmt->fetch(PDO::FETCH_ASSOC);

        // Add teacher name to class details if available
        if ($teacherDetails) {
            $classDetails['teacher_name'] = $teacherDetails['teacher_name'];
        } else {
            $classDetails['teacher_name'] = 'No Teacher Assigned';
        }
    } else {
        // Handle case where no teacher is assigned
        $classDetails['teacher_name'] = 'No Teacher Assigned';
    }

    // Output the result
    if ($classDetails) {
        echo "<p><strong>Class Name:</strong> " . htmlspecialchars($classDetails['class_name']) . "</p>";
        echo "<p><strong>Subject:</strong> " . htmlspecialchars($classDetails['subject']) . "</p>";
        echo "<p><strong>Teacher:</strong> " . htmlspecialchars($classDetails['teacher_name']) . "</p>";
        echo "<p><strong>Semester:</strong> " . htmlspecialchars($classDetails['semester']) . "</p>";
    } else {
        echo "<p>No class found with ID 64.</p>";
    }

    // Fetch students enrolled in the class
    $studentsStmt = $pdo->prepare("SELECT sa.fullName, sa.email
                                  FROM students sa
                                  JOIN class_students cs ON sa.id = cs.student_id
                                  WHERE cs.class_id = :class_id");
    $studentsStmt->bindParam(':class_id', $class_id);
    $studentsStmt->execute();
    $students = $studentsStmt->fetchAll(PDO::FETCH_ASSOC);

    // Get the class details or set defaults
    $class_name = $classDetails['class_name'] ?? 'Class not found';
    $subject = $classDetails['subject'] ?? 'N/A';
    $teacher = $classDetails['teacher_name'] ?? 'No Teacher Assigned';
    $semester = $classDetails['semester'] ?? 'Not Available';
} else {
    echo "<p>No class selected.</p>";
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AdNU - CCS | Student Management System</title>
    <link rel="icon" href="../external/img/favicon-32x32.png" type="image/x-icon">
    <link rel="stylesheet" href="path/to/bootstrap.css"> <!-- Adjust path -->
</head>

<body>
    <div class="container mt-5">
        <!-- Class Overview -->
        <div class="card">
            <div class="card-body">
                <h2>Class Overview</h2>
                <p><strong>Class:</strong> <?php echo htmlspecialchars($class_name); ?></p>
                <p><strong>Subject:</strong> <?php echo htmlspecialchars($subject); ?></p>
                <p><strong>Teacher:</strong> <?php echo htmlspecialchars($teacher); ?></p>
                <p><strong>Semester:</strong> <?php echo htmlspecialchars($semester); ?></p>
            </div>
        </div>

        <!-- Student List Section -->
        <div class="card mt-4">
            <div class="card-body">
                <h3>Enrolled Students</h3>
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone Number</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        if ($students) {
                            foreach ($students as $student) {
                                echo '<tr>';
                                echo '<td>' . htmlspecialchars($student['fullName']) . '</td>';
                                echo '<td>' . htmlspecialchars($student['email']) . '</td>';
                                echo '<td>' . htmlspecialchars($student['phone_number']) . '</td>';
                                echo '</tr>';
                            }
                        } else {
                            echo '<tr><td colspan="3">No students enrolled in this class.</td></tr>';
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Action Section (Manage Attendance, Grades, etc.) -->
        <div class="card mt-4">
            <div class="card-body">
                <h3>Actions</h3>
                <div class="btn-group" role="group">
                    <a href="manage_attendance.php?class_id=<?php echo $class_id; ?>" class="btn btn-primary">Manage
                        Attendance</a>
                    <a href="view_grades.php?class_id=<?php echo $class_id; ?>" class="btn btn-warning">View Grades</a>
                    <a href="assign_students.php?class_id=<?php echo $class_id; ?>" class="btn btn-info">Assign
                        Students</a>
                </div>
            </div>
        </div>

        <!-- Report Generation Section -->
        <div class="card mt-4">
            <div class="card-body">
                <h3>Generate Reports</h3>
                <a href="generate_report.php?class_id=<?php echo $class_id; ?>" class="btn btn-success">Generate
                    Performance Report</a>
                <a href="generate_attendance_report.php?class_id=<?php echo $class_id; ?>"
                    class="btn btn-secondary">Generate Attendance Report</a>
            </div>
        </div>
    </div>

    <script src="path/to/bootstrap.bundle.js"></script>
</body>

</html>