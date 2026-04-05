<?php
include('processes/server/alert_system.php');
?>

<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>ADDU - CCS | Contact Us</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">

    <!-- Custom CSS -->
    <link href="external/css/index.css" rel="stylesheet">

    <!-- AOS Animation CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/aos@2.3.1/dist/aos.css">

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
</head>

<body>
    <?php include './header.php'; ?>

    <div class="container my-5">
        <div class="row text-center" data-aos="fade-down">
            <div class="col-lg-8 mx-auto">
                <h1 class="fw-bold mb-4">Contact Us</h1>
                <p class="mb-4">Have questions? Reach out to us using the form below or via our contact details.</p>
            </div>
        </div>

        <!-- <div class="row mt-5">
            <div class="col-md-5" data-aos="fade-right">
                <h3><i class="bi bi-geo-alt-fill text-primary"></i> Address</h3>
                <p>Ateneo de Davao University, College of Computing Studies, Zamboanga City</p>
                <h3><i class="bi bi-telephone-fill text-success"></i> Phone</h3>
                <p>+63 912 345 6789</p>
                <h3><i class="bi bi-envelope-fill text-danger"></i> Email</h3>
                <p>ccs@ADDU.edu.ph</p>
                <h3><i class="bi bi-clock-fill text-warning"></i> Office Hours</h3>
                <p>Monday - Friday: 8:00 AM - 5:00 PM</p>
            </div> -->
        <div class="container">
            <div class="row justify-content-center">
                <!-- Address -->
                <div class="col-md-4 mb-4" data-aos="fade-right">
                    <div class="card contact-card shadow p-4 text-center">
                        <i class="bi bi-geo-alt display-4 text-danger mb-3"></i>
                        <h5>Our Address</h5>
                        <p>Ateneo de Davao University, Veterans Avenue, Zamboanga City</p>
                    </div>
                </div>
                <!-- Phone -->
                <div class="col-md-4 mb-4" data-aos="fade-up">
                    <div class="card contact-card shadow p-4 text-center">
                        <i class="bi bi-telephone display-4 text-primary mb-3"></i>
                        <h5>Call Us</h5>
                        <p>+63 912 345 6789</p>
                    </div>
                </div>
                <!-- Email -->
                <div class="col-md-4 mb-4" data-aos="fade-left">
                    <div class="card contact-card shadow p-4 text-center">
                        <i class="bi bi-envelope display-4 text-success mb-3"></i>
                        <h5>Email Us</h5>
                        <p>info@ADDU.edu.ph</p>
                    </div>
                </div>
            </div>
        </div>

        <style>
            body {
                font-family: 'Poppins', sans-serif;
                background-color: #f8f9fa;
            }

            .contact-card {
                transition: transform 0.3s ease-in-out;
            }

            .contact-card:hover {
                transform: scale(1.05);
            }
        </style>
        <!-- Contact Form
            <div class="col-md-7" data-aos="fade-left">
                <div class="card shadow p-4">
                    <form>
                        <div class="mb-3">
                            <label for="name" class="form-label">Full Name</label>
                            <input type="text" class="form-control" id="name" placeholder="Enter your name">
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" placeholder="Enter your email">
                        </div>
                        <div class="mb-3">
                            <label for="message" class="form-label">Message</label>
                            <textarea class="form-control" id="message" rows="4" placeholder="Your message"></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Send Message</button>
                    </form>
                </div>
            </div>
        </div> -->

        <!-- Google Maps Embed -->
        <div class="row mt-5" data-aos="zoom-in">
            <div class="col-lg-12">
                <h2 class="text-center mb-4">Find Us Here</h2>
                <iframe class="w-100" height="400" src="https://maps.google.com/maps?q=Western%20Mindanao%20State%20University&t=&z=13&ie=UTF8&iwloc=&output=embed" frameborder="0"></iframe>
            </div>
        </div>
    </div>

    <!-- <div class="container text-center mt-5" data-aos="zoom-in">
                <h3>Follow Us</h3>
                <div class="d-flex justify-content-center mt-3">
                    <a href="#" class="me-3 text-primary fs-3"><i class="bi bi-facebook"></i></a>
                    <a href="#" class="me-3 text-info fs-3"><i class="bi bi-twitter"></i></a>
                    <a href="#" class="me-3 text-danger fs-3"><i class="bi bi-instagram"></i></a>
                    <a href="#" class="text-dark fs-3"><i class="bi bi-linkedin"></i></a>
                </div>
            </div> -->

    <!-- Footer -->
    <footer class="bg-light text-center py-4 mt-5">
        <div class="container">
            <p>&copy; 2024 ADDU - CCS. All rights reserved.</p>
            <p>
                <a href="#" class="text-primary mx-2"><i class="bi bi-facebook"></i></a>
                <a href="#" class="text-info mx-2"><i class="bi bi-twitter"></i></a>
                <a href="#" class="text-danger mx-2"><i class="bi bi-instagram"></i></a>
            </p>
        </div>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- AOS Animation JS -->
    <script src="https://cdn.jsdelivr.net/npm/aos@2.3.1/dist/aos.js"></script>
    <script>
        AOS.init();
    </script>
</body>

</html>