<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>ADDU - CCS | Comprehensive Student Management System</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <link href="external/css/login_staff.css" rel="stylesheet">

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
            <small><a href="teacher_login_page.html" class="gb"><i class="bi bi-arrow-left-circle-fill"></i> Go
                    back</a></small>
            <img src="external/img/ADDU_Logo-removebg-preview.png" class="img-fluid small-logo">
            <h5 class="bold">STAFF | CREATE AN ACCOUNT</h5>

            <div class="container login-container-with-input">
                <form method="POST" action="processes/teachers/account/add.php">
                    <div class="form-group">

                        <label class="bold" for="last_name">Last Name</label>
                        <input class="form-control" id="last_name" name="last_name" placeholder="Last Name" required>
                        <br>

                        <label class="bold" for="first_name">First Name</label>
                        <input class="form-control" id="first_name" name="first_name" placeholder="First Name" required>
                        <br>

                        <label class="bold" for="middle_name">Middle Name</label>
                        <input class="form-control" id="middle_name" name="middle_name" placeholder="Middle Name">
                        <br>

                        <label class="bold" for="email">Email</label>
                        <input class="form-control" id="email" name="email" placeholder="Email" type="email" required>
                        <br>

                        <label class="bold" for="phone_number">Phone Number</label>
                        <input class="form-control" id="phone_number" name="phone_number" placeholder="Phone Number"
                            value="+63" type="tel" pattern="[+0-9]{1,}"
                            title="Phone number must be digits only, with an optional leading '+'." required>
                        <br>

                        <label class="bold" for="password">Password</label>
                        <input class="form-control" id="password" name="password" placeholder="Password" type="password"
                            required>
                        <br>

                        <label class="bold" for="confirm_password">Confirm Password</label>
                        <input class="form-control" id="confirm_password" name="confirm_password"
                            placeholder="Confirm Password" type="password" required>

                        <label class="bold" for="gender">Gender</label>
                        <select class="form-control" name="gender" required>
                            <option>Select a gender</option>
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                        </select>

                    </div>
            </div>

            <div class="button-linkers d-flex justify-content-between">

            </div>
            <div class="container login-container-with-input">
                <div class="row">
                    <div class="col">
                        <input type="submit" value="Create Account" class="login-btn">
                    </div>
                    </form>
                    <div class="col">
                        <button onclick="goBack()" class="login-btn">Go
                            back </button>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <script>
        function goBack() {
            window.location.href = "teacher_login_page.php";
        }
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous">

    </script>
</body>

</html>