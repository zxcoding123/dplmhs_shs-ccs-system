<?php
session_start();

?>

<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>ADDU - CCS | Student Management System</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <link href="../external/css/login.css" rel="stylesheet">

    <link rel="icon" type="image/png" sizes="32x32" href="external/img/favicon-32x32.png">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap"
        rel="stylesheet">

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

</head>

<header>
    <nav class="navbar navbar-expand-lg">
        <div class="container-fluid d-flex">
            <a class="navbar-brand" href="#">
                <img src="../external/img/ccs_logo-removebg-preview.png" class="img-fluid logo">
            </a>
            <div class="mx-auto">
                Student Management System
            </div>

        </div>
    </nav>
</header>

<body>

    <div class="container-fluid login-container">

        <div class="actual-login-container">
            <div class="d-flex">
                <img src="../external/img/ADDU_Logo-removebg-preview.png" class="img-fluid big-logo">
                <img src="../external/img/ccs_logo-removebg-preview.png" class="img-fluid big-logo">
            </div>
            <h5 class="bold">RESET PASSWORD</h5>
            <div class="container login-container-with-input">
                <form action="reset_processor.php" method="POST">
                    <input type="hidden" name="token" value="<?php echo $_GET['token']; ?>">
                    <div class="mb-3">
                        <label for="password" class="form-label bold">Password</label>
                        <div class="input-group">
                            <input type="password" class="form-control" id="password" name="password"
                                placeholder="Create a password" required>
                            <button type="button" class="btn btn-outline-secondary"
                                onclick="togglePassword('password', this)">
                                <i class="bi bi-eye" aria-hidden="true"></i>
                            </button>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="confirm_password" class="form-label bold">Confirm Password</label>
                        <div class="input-group">
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password"
                                placeholder="Re-enter your password" required>
                            <button type="button" class="btn btn-outline-secondary"
                                onclick="togglePassword('confirm_password', this)">
                                <i class="bi bi-eye" aria-hidden="true"></i>
                            </button>
                        </div>
                    </div>

                    <button type="submit" class="login-btn">Submit New Password</button>
                </form>
            </div>
            <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
                integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz"
                crossorigin="anonymous"></script>
</body>

<script>
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
</script>



</html>

<?php
include('alerts.php');
?>