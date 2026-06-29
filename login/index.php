<?php
session_start();
require_once '../app/auth_bootstrap.php';

$pdo = Database::getConnection();

$stmt = $pdo->prepare("
    UPDATE classes_meetings
    SET status = 'Finished'
    WHERE STR_TO_DATE(CONCAT(CURDATE(), ' ', end_time), '%Y-%m-%d %h:%i %p') < NOW()
");
$stmt->execute();
?>

<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>AU - CCS | Student Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="external/css/login.css" rel="stylesheet">
    <link rel="icon" type="image/png" sizes="32x32" href="external/img/favicon-32x32.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900&display=swap"
        rel="stylesheet">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
</head>

<header>
    <nav class="navbar navbar-expand-lg  py-2">
        <div class="container-fluid">
            <!-- Logo -->
            <a class="navbar-brand d-flex align-items-center" href="../index.php">
                <img src="external/img/AU_Logo.png" alt="AU Logo" class="img-fluid" style="max-height:50px;">
            </a>

            <!-- Mobile toggle button -->
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent"
                aria-controls="navbarContent" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>

            <!-- Collapsible content -->
            <div class="collapse navbar-collapse justify-content-center" id="navbarContent">
                <span class="navbar-text fw-bold text-center text-light">
                    Student Management System
                </span>
            </div>
        </div>
    </nav>
</header>

<body>
    <div class="container-fluid login-container">
        <div class="row min-vh-100 justify-content-center align-items-center px-3">
            <!-- Left Column: Login Form -->
            <div class="col-lg-6 col-md-8 col-12 p-4">
                <div data-aos="fade-right">
                    <div class="d-flex justify-content-center mb-3">
                        <small><a href="../index.php" class="gb">
                                <i class="bi bi-arrow-left-circle-fill"></i> Go back
                            </a></small>
                    </div>

                    <div class="d-flex justify-content-center align-items-center mb-3 gap-2 flex-wrap">
                        <img src="external/img/AU_Logo.png" class="img-fluid" style="max-width:100px;" alt="AU Logo">
                        <!-- <img src="external/img/AU_CCS_Logo.png" class="img-fluid" style="max-width:100px;" alt="CCS Logo"> -->
                    </div>

                    <h3 class="text-center fw-bold mb-2">Welcome to AU - CCS</h3>
                    <p class="text-center text-muted mb-4">Login your account by filling out the form below</p>

                    <form action="processes/login.php" method="POST" class="login-container-with-input">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Email</label>
                            <input type="email" class="form-control" name="email" placeholder="Email" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Password</label>
                            <input type="password" id="password" name="password" class="form-control" placeholder="Password" required>
                        </div>

                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" id="showPassword" onclick="togglePasswordVisibility()">
                            <label class="form-check-label" for="showPassword">Show Password</label>
                        </div>

                        <div class="d-flex justify-content-between mb-3 flex-wrap gap-2">
                            <a href="create_account.php" class="gb-link">Create an Account</a>
                            <a data-bs-toggle="modal" data-bs-target="#resetPasswordModal" class="gb-link">Forgot your password?</a>
                        </div>

                        <button type="submit" class="btn btn-primary w-100">Login</button>
                    </form>
                </div>
            </div>

            <!-- Right Column: Image -->
            <div class="col-lg d-none d-lg-flex justify-content-center align-items-center">
                <div data-aos="fade-left">
                    <img src="external/img/login.png" class="img-fluid rounded" alt="AU Login Banner">
                </div>
            </div>
        </div>
    </div>

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

            .d-flex .big-logo {
                width: 100px;
                height: 100px;
            }

            .modal-dialog {
                max-width: 95%;
                margin: 0 auto;
            }

            .modal-content {
                padding: 10px;
            }

            .modal-title {
                font-size: 1.2rem;
            }

            .modal-footer button {
                padding: 5px 10px;
                font-size: 0.9rem;
            }

            .modal-body label {
                font-size: 0.9rem;
            }

            .modal-body input {
                font-size: 0.9rem;
                padding: 5px;
            }
        }
    </style>

    <div class="modal fade" id="resetPasswordModal" tabindex="-1" aria-labelledby="resetPasswordModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="resetPasswordModalLabel">Reset Password</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="resetPasswordForm" action="processes/reset.php" method="POST">
                        <div class="mb-3">
                            <label for="emailInput" class="form-label">Email Address</label>
                            <input type="email" class="input-control" id="emailInput" name="email"
                                placeholder="Enter your email address" required>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary" form="resetPasswordForm" id="submitReset">Send Reset
                        Link</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous">
    </script>

    <script>
        document.getElementById('submitReset').addEventListener('click', function() {
            // Show SweetAlert2 modal
            Swal.fire({
                title: 'Processing...',
                text: 'Please wait while we process your request.',
                icon: 'info',
                allowOutsideClick: false,
                showConfirmButton: false,
                didOpen: () => {
                    Swal.showLoading(); // Show loading indicator
                    // Submit the form after showing the alert
                    document.getElementById('resetPasswordForm').submit();
                }
            });
        });
    </script>
</body>

<script>
    function togglePasswordVisibility() {
        const passwordField = document.getElementById('password');
        passwordField.type = passwordField.type === 'password' ? 'text' : 'password';
    }
    AOS.init();
</script>

<?php
include('processes/alerts.php');
?>

</html>