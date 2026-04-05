<?php
include('processes/server/alert_system.php');
?>

<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>ADDU - CCS | About Us</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">

    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <!-- Custom CSS -->
    <link href="external/css/index.css" rel="stylesheet">

    <!-- Favicon -->
    <link rel="icon" type="image/png" sizes="32x32" href="external/img/favicon-32x32.png">

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap"
        rel="stylesheet">

    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body>
    <?php
    include './header.php'
    ?>

    <!-- About Us Content -->
    <div class="container my-5">
        <div class="row about">
            <div class="col-lg-8 mx-auto text-center">
                <h1 class="fw-bold mb-4">About Us</h1>
                <p class="mb-4">
                    Welcome to the <strong>Ateneo de Davao University - College of Computing Studies</strong>
                    Student Management System. We are dedicated to providing a seamless and efficient platform for
                    students, faculty, and administrators to manage academic activities and resources.
                </p>
            </div>
        </div>

        <div class="row mt-5">
            <div class="col-md-4 mb-4">
                <div class="card h-100 shadow">
                    <div class="card-body text-center">
                        <i class="bi bi-building display-4 text-primary mb-3"></i>
                        <h3 class="card-title">Our Mission</h3>
                        <p class="card-text">
                            To provide quality education and foster innovation in the field of computing and information
                            technology, empowering students to become globally competitive professionals.
                        </p>
                    </div>
                </div>
            </div>

            <div class="col-md-4 mb-4">
                <div class="card h-100 shadow">
                    <div class="card-body text-center">
                        <i class="bi bi-eye display-4 text-success mb-3"></i>
                        <h3 class="card-title">Our Vision</h3>
                        <p class="card-text">
                            To be a leading institution in computing education, research, and community service,
                            contributing to the advancement of technology and society.
                        </p>
                    </div>
                </div>
            </div>

            <div class="col-md-4 mb-4">
                <div class="card h-100 shadow">
                    <div class="card-body text-center">
                        <i class="bi bi-people display-4 text-warning mb-3"></i>
                        <h3 class="card-title">Our Values</h3>
                        <p class="card-text">
                            We uphold integrity, excellence, innovation, and inclusivity in all our endeavors,
                            ensuring a supportive and dynamic learning environment for all.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mt-5">
            <div class="history">
                <div class="col-lg-8 mx-auto">
                    <h2 class="fw-bold mb-4">Our History</h2>
                    <p>
                        Established a very long time ago, the College of Computing Studies at Ateneo de Davao University
                        has been at the forefront of computing education in the region. Over the years, we have
                        produced thousands of graduates who have excelled in various fields of information technology,
                        both locally and internationally.
                    </p>
                    <p>
                        Our commitment to excellence and innovation has earned us recognition as one of the top
                        computing schools in the country. We continue to evolve and adapt to the changing landscape
                        of technology, ensuring that our students are well-prepared for the challenges of the future.
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-light text-center py-4">
        <div class="container">
            <p class="mb-0">&copy; 2023 ADDU - CCS. All rights reserved.</p>
        </div>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz"
        crossorigin="anonymous"></script>
</body>

</html>

<?php
if (isset($_SESSION['STATUS']) && $_SESSION['STATUS'] == 'SEMESTER_NEAR_ENDING_NOTICE') {
    echo "
    <script>
        Swal.fire({
            title: 'Semester is almost ending!',
            text: 'The current semester is nearing its end. Teachers, please finalize all lessons and submit your grades. Students, be sure to submit all your assignments or requirements promptly.',
            icon: 'info'
        });
    </script>
    ";
    unset($_SESSION['STATUS']);
} elseif (isset($_SESSION['STATUS']) && $_SESSION['STATUS'] == 'SEMESTER_ENDED_NOTICE') {
    echo "
    <script>
        Swal.fire({
            title: 'Semester has ended!',
            text: 'The semester has officially concluded. Teachers, ensure all grades are finalized. Students, make sure all your submissions are complete and any queries are resolved.',
            icon: 'warning'
        });
    </script>
    ";
    unset($_SESSION['STATUS']);
}
?>

<style>
    .history {
        color: white;
        justify-content: center;
        text-align: center;
    }

    .about {
        color: white;
    }
</style>