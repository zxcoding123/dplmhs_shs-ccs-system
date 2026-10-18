<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>DPLMHS-SHS | Student Management System</title>
    <link rel="icon" href="../external/img/favicon-32x32.png" type="image/x-icon">

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
        integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH"
        crossorigin="anonymous">

    <link rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <link
        href="<?php require_once __DIR__ . '/../includes/asset-version.php'; echo csms_asset('external/css/login_student.css', __DIR__); ?>"
        rel="stylesheet">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap"
        rel="stylesheet">

</head>

<header>
    <nav class="navbar navbar-expand-lg">
        <div class="container-fluid d-flex">
            <a class="navbar-brand" href="#">
                <img src="external/img/ccs_logo-removebg-preview.png"
                    class="img-fluid logo">
            </a>
            <div class="mx-auto">
                Comprehensive Student Management System
            </div>

        </div>
    </nav>
</header>

<body>

    <div class="container-fluid login-container">
        <div class="actual-login-container">
            <small><a href="student_login_page.php" class="gb"><i
                        class="bi bi-arrow-left-circle-fill"></i> Go
                    back</a></small>
            <img src="external/img/ADDU_Logo-removebg-preview.png"
                class="img-fluid small-logo">
            <h5 class="bold">STUDENT ACCOUNT CREATION</h5>

            <div class="container-fluid login-container-with-input">
                <form action="processes/students/account/register.php" enctype="multipart/form-data" method="POST">
                    <div class="row">
                        <div class="col">
                            <label style="text-align: left !important;" class="bold">Last Name</label>
                            <input class="form-control" name="last_name" placeholder="Last Name" type="text" required>
                            <br>
                            <label style="text-align: left !important;" class="bold">First Name</label>
                            <input class="form-control" name="first_name" placeholder="First Name" type="text" required>
                            <br>
                            <label style="text-align: left !important;" class="bold">Middle Name</label>
                            <input class="form-control" name="middle_name" placeholder="Middle Name" type="text" required>
                            <br>
                            <label style="text-align: left !important;" class="bold">Email</label>
                            <input class="form-control" name="email" placeholder="Email" type="email" required>
                            <br>
                            <label style="text-align: left !important;" class="bold">Password</label>
                            <input class="form-control" name="password" placeholder="Password" type="password" required>
                            <br>
                            <label style="text-align: left !important;" class="bold">Confirm Password</label>
                            <input class="form-control" name="confirm_password" placeholder="Confirm Password" type="password" required>
                        </div>
                        <div class="col">
                            <div class="container qr-code text-center">

                                <label style="text-align: left !important;" class="bold">Gender</label>
                                <select class="form-control" name="gender" id="gender" required>
                                    <option default selected>Select gender here</option>
                                    <option value="Male">Male</option>
                                    <option value="Female">Female</option>
                                </select>

                                <br>


                                <label style="text-align: left !important;" class="bold">Student ID</label>
                                <input type="text" class="form-control" name="student_id" id="student_id" placeholder="Student ID" oninput="checkInputs()" required>

                                <br>

                                <?php require_once __DIR__ . '/../includes/academic.php'; ?>
                                <label style="text-align: left !important;" class="bold">Strand &amp; Section</label>
                                <select class="form-control" name="course" id="course" oninput="checkInputs()" required>
                                    <option value="" disabled selected>Select strand and section</option>
                                    <?php foreach (shs_sections() as $strand => $list): ?>
                                        <optgroup label="<?= htmlspecialchars(shs_strand_label($strand)) ?>">
                                            <?php foreach ($list as $sec): ?><option value="<?= $sec ?>"><?= str_replace('-', ' - ', $sec) ?></option><?php endforeach; ?>
                                        </optgroup>
                                    <?php endforeach; ?>
                                </select>


                                <br>
                                <label style="text-align: left !important;" class="bold">Grade Level</label>
                                <select class="form-control" name="year_level" id="year_level" oninput="checkInputs()" required>
                                    <option value="" disabled selected>Select Grade Level</option>
                                    <?php foreach (shs_grade_levels() as $g): ?><option value="<?= $g ?>"><?= $g ?></option><?php endforeach; ?>
                                </select>


                                <br>



                            </div>
                        </div>






                        <script>
                            function checkInputs() {
                                const studentId = document.getElementById('student_id').value.trim();
                                const course = document.getElementById('course').value.trim();
                                const yearLevel = document.getElementById('year_level').value.trim();
                                const generateBtn = document.getElementById('generateBtn');


                                if (studentId && course && yearLevel) {
                                    generateBtn.disabled = false;
                                } else {
                                    generateBtn.disabled = true;
                                }
                            }
                        </script>
                    </div>
            </div>
        </div>

        <div class="button-linkers d-flex justify-content-between">

        </div>
        <div class="container login-container-with-input">
            <div class="row">
                <div class="col">
                    <input type="submit" value="Create Account"
                        class="login-btn">
                </div>
                </form>
                <div class="col">
                    <button onclick="goBack()" class="login-btn">Go
                        back </button>
                </div>
            </div>

        </div>

    </div>

    </form>
    </div>

    <script>
        function goBack() {
            window.location.href = "student_login_page.php";
        }
    </script>

    <script
        src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz"
        crossorigin="anonymous">

    </script>
</body>

</html>