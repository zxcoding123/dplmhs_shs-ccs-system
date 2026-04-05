<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>ADDU - CCS | Comprehensive Student Management System</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <link href="external/css/login.css" rel="stylesheet">

    <link rel="icon" type="image/png" sizes="32x32" href="external/img/favicon-32x32.png">

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
                <img src="external/img/ccs_logo-removebg-preview.png" class="img-fluid logo">
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
            <small><a href="../index.php" class="gb"><i class="bi bi-arrow-left-circle-fill"></i> Go back</a></small>
            <img src="external/img/ADDU_Logo-removebg-preview.png" class="img-fluid big-logo">
            <h5 class="bold">STUDENT LOGIN</h5>

            <div class="container login-container-with-input">
                <form method="POST" action="processes/students/account/login.php">
                    <label style="text-align: left !important;" class="bold">EMAIL</label>
                    <input class="form-control" name="email" type="email" placeholder="Email" required>
                    <br>
                    <label style="text-align: left !important;" class="bold">PASSWORD</label>
                    <input class="form-control" name="password" type="password" placeholder="Password" required>
            </div>

            <div class="button-linkers d-flex justify-content-between">
                <a href="student_create_account.php" class="gb-link me-auto" class="gb">Create an Account</a>
                <a data-bs-toggle="modal" data-bs-target="#resetPasswordModal" class="gb-link">Forgot your password?</a>

            </div>
            <div class="container login-container-with-input">
                <input type="submit" value="Login" class="login-btn">
            </div>
        </div>

        </form>
    </div>


    <div class="modal fade" id="resetPasswordModal" tabindex="-1" aria-labelledby="resetPasswordModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="resetPasswordModalLabel">Reset Password</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="resetPasswordForm">
                        <div class="mb-3">
                            <label for="emailInput" class="form-label">Email Address</label>
                            <input type="email" class="form-control" id="emailInput"
                                placeholder="Enter your email address" required>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary" form="resetPasswordForm">Send Reset Link</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz"
        crossorigin="anonymous"></script>
</body>

</html>