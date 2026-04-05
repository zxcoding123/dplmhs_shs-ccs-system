<?php
session_start();

?>

<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>ADNU - CCS | Student Management System</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <link href="external/css/login_student.css" rel="stylesheet">

    <link rel="icon" type="image/png" sizes="32x32" href="external/img/favicon-32x32.png">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap"
        rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">

    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>

</head>

<header>
    <nav class="navbar navbar-expand-lg">
        <div class="container-fluid d-flex">
            <a class="navbar-brand" href="../index.php">
                <img src="external/img/ADNU_Logo.png" class="img-fluid logo">
            </a>
            <div class="mx-auto"> Student Management System
            </div>

        </div>
    </nav>
</header>

<body>

    <div class="container-fluid login-container">
        <div class="actual-login-container">
            <!-- Back Button -->



            <style>
                @media screen and (max-width: 500px) {
                    .login-container-with-input {
                        width: 100% !important;
                    }

                    .button-linkers {
                        justify-content: center !important;
                        text-align: center !important;
                        font-size: 12px !important;
                        gap: 2rem;
                    }
                }
            </style>
            <!-- Registration Form -->
            <div class="container-fluid mt-4 login-container-with-input">
                <div class="container-fluid px-3 py-5">
                    <div class="row justify-content-center align-items-center min-vh-100">
                        <div class="col-lg-6 d-none d-lg-flex align-items-start justify-content-center">
                            <div class="position-sticky" style="top: 2rem; z-index: 1;">
                                <div data-aos="fade-right">
                                    <img src="external/img/register.png"
                                        class="img-fluid w-100"
                                        alt="Registration Banner"
                                        style="border-radius: 5%;">
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-6 col-md-10 col-sm-12">
                            <div class="container login-container-with-input">
                                <div data-aos="fade-left">
                                    <div class="text-center">
                                        <small><a href="index.php" class="gb text-center"><i class="bi bi-arrow-left-circle-fill"></i> Go back</a></small>
                                    </div>
                                    <div class="d-flex justify-content-center align-items-center flex-row mt-3">
                                        <img src="external/img/ADNU_Logo.png" class="img-fluid big-logo me-2" alt="ADNU Logo">
                                        <img src="external/img/ADNU_CCS_Logo.png" class="img-fluid big-logo" alt="CCS Logo">
                                    </div>
                                    <h3 class="text-center bold">Welcome to AdNU - CCS</h3>
                                    <p class="text-center text-muted">Create your account by filling out the form below</p>
                                    <form action="processes/register.php" method="POST" id="registrationForm">
                                        <!-- Name -->
                                        <div class="mb-3">
                                            <label for="firstName" class="form-label bold">First Name</label>
                                            <input type="text" class="input-control" id="firstName" name="firstName"
                                                placeholder="Enter your first name" required>
                                        </div>

                                        <div class="mb-3">
                                            <label for="middleName" class="form-label bold">Middle Name</label>
                                            <input type="text" class="input-control" id="middleName" name="middleName"
                                                placeholder="Enter your middle name" required>
                                        </div>

                                        <div class="mb-3">
                                            <label for="lastName" class="form-label bold">Last Name</label>
                                            <input type="text" class="input-control" id="lastName" name="lastName"
                                                placeholder="Enter your last name" required>
                                        </div>

                                        <!-- Email -->
                                        <div class="mb-3">
                                            <label for="email" class="form-label bold">Email Address</label>
                                            <input type="email" class="input-control" id="email" name="email"
                                                placeholder="Enter your email" required>
                                        </div>

                                        <!-- Password -->
                                        <div class="mb-3">
                                            <label for="password" class="form-label bold">Password</label>
                                            <input type="password" class="input-control" id="password" name="password"
                                                placeholder="Create a password" required>
                                            <div class="form-check form-switch mt-2">
                                                <input class="form-check-input toggle-switch" type="checkbox" id="togglePassword"
                                                    onclick="toggleVisibility('password', this)">
                                                <label class="form-check-label ms-2" for="togglePassword">Show Password</label>
                                            </div>
                                        </div>

                                        <!-- Confirm Password -->
                                        <div class="mb-3">
                                            <label for="confirm_password" class="form-label bold">Confirm Password</label>
                                            <input type="password" class="input-control" id="confirm_password" name="confirm_password"
                                                placeholder="Re-enter your password" required>
                                            <div class="form-check form-switch mt-2">
                                                <input class="form-check-input toggle-switch" type="checkbox" id="toggleConfirmPassword"
                                                    onclick="toggleVisibility('confirm_password', this)">
                                                <label class="form-check-label ms-2" for="toggleConfirmPassword">Show Confirm Password</label>
                                            </div>
                                        </div>


                                        <!-- Gender -->
                                        <div class="mb-3">
                                            <label for="gender" class="form-label bold">Gender</label>
                                            <select class="selector" id="gender" name="gender" required>
                                                <option value="" disabled selected>Select Gender</option>
                                                <option value="Male">Male</option>
                                                <option value="Female">Female</option>
                                                <option value="Others">Others</option>
                                            </select>
                                        </div>

                                        <!-- Role -->
                                        <div class="mb-3">
                                            <label for="role" class="form-label bold">Role</label>
                                            <select class="selector" id="role" name="role" required onchange="updateFormFields()">
                                                <option value="" disabled selected>Select Role</option>
                                                <option value="student">Student</option>
                                            </select>
                                        </div>

                                        <!-- Role-Specific Fields -->
                                        <div id="additional-fields"></div>

                                        <!-- Submit -->
                                        <div class="d-grid mt-4">
                                            <button type="submit" class="btn btn-primary">Register</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>



    <script>
        function validateInputs() {
            const email = document.getElementById('email').value;
            const studentId = document.getElementById('student_id').value;

            // Regular Expression to extract the number from email
            const numberMatch = email.match(/\d+/);

            if (numberMatch && numberMatch[0] === studentId) {
                return true; // Validation passes; proceed to submit
            } else {
                alert("The Student ID does not match the number in the email.");
                return false; // Block form submission
            }
        }


        function togglePassword(fieldId, toggleButton) {
            const field = document.getElementById(fieldId);
            const icon = toggleButton.querySelector("i");

            if (field.type === "password") {
                field.type = "text";
                icon.classList.remove("bi-eye");
                icon.classList.add("bi-eye-slash");
            } else {
                field.type = "password";
                icon.classList.remove("bi-eye-slash");
                icon.classList.add("bi-eye");
            }
        }

        function updateFormFields() {
            const role = document.getElementById('role').value;
            const additionalFields = document.getElementById('additional-fields');

            additionalFields.innerHTML = ''; // Clear previous inputs

            if (role === 'staff') {
                additionalFields.innerHTML = `
                <div class="mb-3">
                    <label for="department" class="form-label bold">Department</label>
                    <select class="selector" id="department" name="department" required>
                        <option value="" disabled selected>Select Department</option>
                        <option value="Department of Information Technology ">Department of Information Technology</option>
                        <option value="Department of Computer Science">Department of Computer Science</option>
                    </select>
                </div>
                  <div class="mb-3">
    <label for="phoneNumber" class="form-label bold">Phone Number</label>
    <input type="tel" class="input-control" id="phoneNumber" name="phone_number" placeholder="Enter phone number" required>
</div>

            `;
            } else if (role === 'student') {
                additionalFields.innerHTML = `
                        <div class="mb-3">
                                <label style="text-align: left !important;" class="bold mb-1">Student ID</label>
                                <br>
                                <input type="number" class="input-control" name="student_id" id="student_id" placeholder="Student ID" oninput="checkInputs()" required>
                </div>
                <div class="mb-3">
                  <label for="course" class="form-label bold">Course</label>
                    <select class="selector" id="course" name="course" required>
                        <option value="" disabled selected>Select Course</option>
                        <option value="BSIT">Bachelor of Science in Information Technology</option>
                        <option value="BSCS">Bachelor of Science in Computer Science</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="year_level" class="form-label bold">Year Level</label>
                    <select class="selector" id="year_level" name="year_level" requifred>
                        <option value="" disabled selected>Select Year Level</option>
                        <option value="1st Year">1st Year</option>
                        <option value="2nd Year">2nd Year</option>
                        <option value="3rd Year">3rd Year</option>
                        <option value="4th Year">4th Year</option>
                    </select>
                </div>
            `;
            }
        }
    </script>

    <script>
        document.getElementById('registrationForm').addEventListener('submit', function(e) {
            e.preventDefault();

            const form = e.target;
            const formData = new FormData(form);

            Swal.fire({
                title: 'Submitting...',
                text: 'Please wait while we process your registration.',
                icon: 'info',
                allowOutsideClick: false,
                showConfirmButton: false,
                didOpen: () => {
                    Swal.showLoading();

                    fetch(form.action, {
                            method: 'POST',
                            body: formData
                        })
                        .then(response => response.json()) // expect JSON from PHP
                        .then(data => {
                            Swal.close();

                            if (data.success) {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Success!',
                                    text: data.message || 'Registration completed!'
                                }).then(() => {
                                    form.reset(); // optional
                                });
                            } else {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error',
                                    text: data.message || 'Something went wrong.'
                                });
                            }
                        })
                        .catch(error => {
                            Swal.close();
                            Swal.fire({
                                icon: 'error',
                                title: 'Request Failed',
                                text: 'Server error. Please try again.'
                            });
                            console.error(error);
                        });
                }
            });
        });
    </script>


    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous">
    </script>
    <script>
        function toggleVisibility(fieldId, toggleEl) {
            const field = document.getElementById(fieldId);
            field.type = toggleEl.checked ? "text" : "password";

            // Optional: subtle animation highlight
            field.classList.toggle('password-visible', toggleEl.checked);
        }
        AOS.init();
    </script>
</body>

<?php
include('processes/alerts.php');
?>

</html>