<?php
session_start();
if (!isset($_SESSION['teacher_id'])) {
    $_SESSION['STATUS'] = "TEACHER_NOT_LOGGED_IN";
    header("Location: ../login/index.php");
}

include('processes/server/conn.php');
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>ADDU - CCS | Comprehensive Student Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link href="css/app.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.4.1/css/responsive.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.3/css/jquery.dataTables.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.3/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.4.1/js/dataTables.responsive.min.js"></script>



</head>

<style>
    table.dataTable {
        font-size: 12px;
    }

    td {
        text-align: center;
        vertical-align: middle;
        border-bottom: 1px solid black;
    }

    .btn-csms {
        background-color: #709775;
        color: white;
    }

    .btn-csms:hover {
        border: 1px solid #709775;
    }
</style>

<body>
    <div class="wrapper">
        <?php
        include('sidebar.php')
        ?>

        <div class="main">
            <nav class="navbar navbar-expand navbar-light navbar-bg">
                <a class="sidebar-toggle js-sidebar-toggle">
                    <i class="hamburger align-self-center"></i>
                </a>
                <img src="external/img/ccs_logo-removebg-preview.png" class="logo-small">
                <span class="text-white">ADDU - Comprehensive Student Management System </span>
                <div class="navbar-collapse collapse">
                    <?php include('top-bar.php') ?>
                </div>
            </nav>

            <main class="content">
                <div id="page-content-wrapper">
                    <div class="container-fluid">
                        <div class="card">
                            <div class="card-body">
                                <h1>Class Management</h1>
                                <?php
                                // Fetching the teacher's full name from the session
                                $teacher_id = $_SESSION['teacher_id'];
                                try {
                                    $stmt = $pdo->prepare("SELECT fullName FROM staff_accounts WHERE id = :teacher_id");
                                    $stmt->bindParam(':teacher_id', $teacher_id);
                                    $stmt->execute();
                                    $teacher = $stmt->fetch(PDO::FETCH_ASSOC);
                                    if ($teacher) {
                                        echo '<p>Welcome, ' . htmlspecialchars($teacher['fullName']) . '!</p>';
                                    } else {
                                        echo '<p>Welcome, Teacher!</p>';
                                    }
                                } catch (PDOException $e) {
                                    echo "Error: " . $e->getMessage();
                                }
                                ?>
                                <p>Here's an overview of your class and student-related tasks.</p>

                                <!-- Dashboard Summary -->
                                <div class="row mb-4">
                                    <div class="col">
                                        <div class="card bg-light">
                                            <div class="card-body">
                                                <h5 class="card-title">Class Overview</h5>
                                                <?php
                                                // Check if the teacher is logged in and their ID is available
                                                if (isset($_SESSION['teacher_id'])) {
                                                    $teacher_id = $_SESSION['teacher_id'];
                                                    try {
                                                        // Query to get the teacher's assigned class from the staff_accounts table
                                                        $stmt = $pdo->prepare("SELECT class FROM staff_accounts WHERE id = :teacher_id");
                                                        $stmt->bindParam(':teacher_id', $teacher_id);
                                                        $stmt->execute();

                                                        // Fetch the teacher's class assignment
                                                        $teacher = $stmt->fetch(PDO::FETCH_ASSOC);
                                                        if ($teacher && isset($teacher['class'])) {
                                                            $className = $teacher['class'];



                                                            // Query the 'classes' table to fetch class details
                                                            $classStmt = $pdo->prepare("SELECT id, name, subject, teacher FROM classes WHERE name = :className");
                                                            $classStmt->bindParam(':className', $className);
                                                            $classStmt->execute();

                                                            $classes = $classStmt->fetchAll(PDO::FETCH_ASSOC);

                                                            if ($classes) {
                                                                echo '<ul>';
                                                                foreach ($classes as $classDetails) {
                                                                    // Display each class and make it clickable
                                                                    echo '<li><a href="view_individual_class.php?class_id=' . htmlspecialchars($classDetails['name']) . '">' . htmlspecialchars($classDetails['name']) . ' - ' . htmlspecialchars($classDetails['subject']) . ' (Teacher: ' . htmlspecialchars($classDetails['teacher']) . ')</a></li>';
                                                                }
                                                                echo '</ul>';
                                                            } else {
                                                                echo '<p>No classes found for the teacher.</p>';
                                                            }
                                                        } else {
                                                            echo '<p>No class assigned to this teacher.</p>';
                                                        }
                                                    } catch (PDOException $e) {
                                                        echo "Error: " . $e->getMessage();
                                                    }
                                                } else {
                                                    echo '<p>Please log in to view class details.</p>';
                                                }
                                                ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
        </div>
        </main>
    </div>
    <script src="js/app.js"></script>
    <?php
    include('processes/server/modals.php');
    ?>
    <script>
        function getTime() {
            const now = new Date();
            const newTime = now.toLocaleString();
            console.log(newTime);
            document.querySelector("#currentTime").textContent = "The current date and time is: " + newTime;
        }
        setInterval(getTime, 100);
    </script>

    <script>
        $(document).ready(function() {
            // Initialize DataTable
            $('#classesTable').DataTable({
                "paging": true,
                "searching": true,
                "ordering": true,
                "info": true
            });
        });
    </script>

</html>

<?php
include('processes/server/alerts.php');
?>