<?php
session_start();
include('login/processes/conn.php');
$pdo = Database::getConnection();
// $lines = file(__DIR__ . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
// foreach ($lines as $line) {
//     if (strpos(trim($line), '#') === 0) continue;
//     list($name, $value) = explode('=', $line, 2);
//     $_ENV[trim($name)] = trim($value);
// }
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>College of Computer Studies - AU</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;700&display=swap" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <!-- Add this inside <head> -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">

    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <style>
        :root {
            --main-color: #061E4E;
            --accent-color: #c2a74d;
            --white: #ffffff;
            --highlight: #e92b2d;
        }

        body {
            font-family: 'Montserrat', sans-serif;
            background-color: var(--white);
            color: #333;
        }

        .logo {
            width: 40px;
            height: auto;
        }

        /* Default: show text */
        .header-text {
            display: inline;
            color: white !important;
        }

        /* Hide text on smaller screens */
        @media (max-width: 768px) {
            .header-text {
                display: none;
            }
        }

        header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0.75rem 1.5rem;
            position: relative;
            z-index: 100;
        }

        .header-brand {
            display: flex;
            align-items: center;
            gap: 0.6rem;
            flex: 1;
        }

        .header-brand img {
            width: 40px;
            height: 40px;
            object-fit: contain;
        }

        .header-right {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        header {
            background-color: var(--main-color);
        }

        nav {

            display: flex;
            gap: 1.25rem;
        }

        .hamburger {
            display: none;
            flex-direction: column;
            gap: 5px;
            cursor: pointer;
            background: none;
            border: none;
        }

        .hamburger span {
            display: block;
            width: 24px;
            height: 2px;
            background: currentColor;
            border-radius: 2px;
            transition: all 0.3s ease;
        }

        .hamburger.open span:nth-child(1) {
            transform: translateY(7px) rotate(45deg);
        }

        .hamburger.open span:nth-child(2) {
            opacity: 0;
        }

        .hamburger.open span:nth-child(3) {
            transform: translateY(-7px) rotate(-45deg);
        }

        .mobile-menu {
            display: none;
            flex-direction: column;
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s ease, padding 0.3s ease;
        }

        .mobile-menu.open {
            max-height: 300px;
            padding: 0.5rem 0 1rem;
        }

        @media (max-width: 640px) {
            .header-right {
                display: none;
            }

            .hamburger {
                display: flex;
            }

            .mobile-menu {
                display: flex;
            }
        }

        .hero {
            background-image: url('external/img/Kanto-TDRI-DMOC-4.webp');
            background-color: #061E4E;
            background-repeat: no-repeat;
            background-size: cover;
            background-blend-mode: multiply;
            color: var(--white);
            text-align: center;
            padding: 5rem 2rem;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-direction: column;
        }

        .hero h2 {
            font-size: 2.5rem;
            margin-bottom: 25px;
        }

        .hero p {
            font-size: 1.2rem;
            max-width: 600px;
        }

        .btn-cta {
            background-color: var(--accent-color);
            color: var(--main-color);
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 30px;
            font-weight: bold;
        }

        .btn-cta:hover {
            background-color: var(--highlight);
            color: var(--white);
        }

        .features {
            padding: 4rem 2rem;
            background-color: #f9f9f9;
        }

        .feature-box {
            border-left: 5px solid var(--accent-color);
            background-color: #fff;
            padding: 1.5rem;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
        }

        .carousel-section {
            background-color: #fff;
            padding: 3rem 1rem;
        }

        .newsletter {
            background-color: var(--highlight);
            color: white;
            padding: 3rem 2rem;
            text-align: center;
        }

        .newsletter input[type="email"] {
            max-width: 300px;
            display: inline-block;
        }

        iframe {
            width: 100%;
            border: 0;
            height: 300px;
        }

        footer {
            background-color: var(--main-color);
            color: var(--white);
            text-align: center;
            padding: 1rem;
        }

        .blockquote-footer {
            color: white;
        }

        .navy-card {
            background-color: #061E4E !important;
            color: white !important;
        }

        .beige-card {
            background-color: #c2a74d !important;
            color: white !important;
        }

        .content {
            padding: 45px;
        }

        .dean-announcement {
            background-color: #061E4E !important;
            color: white !important;
            padding: 10px;
        }

        .btn-outline-beige {
            border: 1px solid #c2a74d;
        }

        .btn-outline-beige:hover {
            background-color: #c2a74d;
            color: white;
        }

        .nav-link {
            font-weight: bolder;
            position: relative;
            color: white;
            text-decoration: none;
            padding: 5px 0;
            display: inline-block;
        }

        /* underline (hidden by default) */
        .nav-link::after {
            content: "";
            position: absolute;
            left: 0;
            bottom: 0;
            width: 100%;
            height: 2px;
            background-color: white;

            transform: scaleX(0);
            transform-origin: left;
            transition: transform 0.3s ease;
        }

        /* hover effect */

        .nav-link:hover {
            color: white;
        }


        .nav-link:hover::after {
            transform: scaleX(1);
        }

        .login-btn {
            background-color: var(--accent-color);
            color: var(--main-color);
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            text-decoration: none;
            font-weight: bold;
            transition: background-color 0.3s ease;
        }

        .login-btn:hover {
            background-color: var(--highlight);
            color: var(--white);
        }
    </style>
</head>

<body>
    <header>
        <h6>
            <img src="external/img/AU_Logo.png" class="logo">
            <span class="header-text">
                &nbsp; Aurelius University - College of Computer Studies
            </span>
        </h6>

        <div class="d-flex align-items-center">
            <nav class="me-3">
                <a href="index.php" class="nav-link">Home</a>
                <a href="programs.php" class="nav-link">Programs</a>
            </nav>
            <a href="login/index.php" class="login-btn">Login</a>
        </div>
    </header>

    <!-- Mobile dropdown menu -->
    <div class="mobile-menu" id="mobileMenu">
        <a href="index.php">Home</a>
        <a href="programs.php">Programs</a>
        <a href="login/index.php" class="login-btn">Login</a>
    </div>

    <section>
        <div class="hero">
            <img src="external/img/AU_Logo.png" class="img-fluid" style="width: auto; height: 500px;">
            <br>
            <h2><b>Innovating the Future</b></h2>
            <p>Welcome to the College of Computer Studies at Aurelius University — where technology meets Jesuit excellence.</p>
            <a href=" programs.php" class="btn btn-cta">Explore Programs</a>
        </div>
    </section>

    <section>

        <div class="row">

            <div class="col beige-card">
                <div data-aos="fade-up">
                    <div class="content">
                        <h3><b>ACADEMIC EXCELLENCE</b></h3>
                        <p>AU is the only Autonomous university in Bicol, offering pre-school, grade school, junior high school, senior high school, college, graduate school, and law school.</p>
                    </div>
                </div>
            </div>

            <div class="col navy-card">
                <div data-aos="fade-down">
                    <div class="content">
                        <h3><b>SOCIAL INVOLVEMENT</b></h3>
                        <p>Aside from offering quality Jesuit education to all academic levels, the university also has programs which give priority to the marginalized, from scholarships to outreach activities.</p>
                    </div>
                </div>
            </div>
            <div class="col beige-card">
                <div data-aos="fade-up">
                    <div class="content">
                        <h3><b>VALUES FORMATION</b></h3>
                        <p>AU offers programs for personal and spiritual growth. Numerous annual retreats and alternative programs are open to the Ateneo community members and alumni.</p>
                    </div>
                </div>
            </div>
            <div class="col navy-card">
                <div data-aos="fade-down">
                    <div class="content">
                        <h3><b>RESEARCH</b></h3>
                        <p>The university serves as prime venue in Bicol for research opportunities thanks to the school’s available facilities, resources, partnership, and training programs.</p>
                    </div>
                </div>
            </div>
        </div>

    </section>
    <section class="container features">
        <div class="row g-4">

            <div class="col-md-6 col-lg-3">
                <div data-aos="fade-up" data-aos-duration="600">
                    <div class="feature-box text-center">
                        <i class="bi bi-cpu-fill display-4 text-primary mb-3"></i>
                        <h5><b>BS in Computer Science</b></h5>
                        <p>Master software engineering, AI, and data science through a rigorous curriculum.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div data-aos="fade-up" data-aos-duration="700">
                    <div class="feature-box text-center">
                        <i class="bi bi-hdd-network-fill display-4 text-primary mb-3"></i>
                        <h5><b>BS in Information Technology</b></h5>
                        <p>Focus on systems admin, web dev, and networking with hands-on experience.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div data-aos="fade-up" data-aos-duration="800">
                    <div class="feature-box text-center">
                        <i class="bi bi-pc-display-horizontal display-4 text-primary mb-3"></i>
                        <h5><b>Modern Labs</b></h5>
                        <p>Equipped with smart classrooms, research spaces, and innovation hubs.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div data-aos="fade-up" data-aos-duration="900">
                    <div class="feature-box text-center">
                        <i class="bi bi-book-half display-4 text-primary mb-3"></i>
                        <h5><b>Jesuit Education</b></h5>
                        <p>Grounded in values of service, integrity, and academic excellence.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>


    <div class="container mt-5">
        <section class="dean-announcement text-center">
            <div data-aos="fade-up">
                <h1 class="display-5"><b>Dean's Announcements</b></h1>
                <p class="lead">Updates, memos, and official statements from the Office of the Dean</p>
            </div>
        </section>
    </div>

    <section class="container my-5">
        <div data-aos="zoom-in">


            <div class="row g-4">
                <?php
                // Query to get published posts
                $sql = "SELECT * FROM posts WHERE status = 'Published' ORDER BY created_at DESC LIMIT 2";
                $stmt = $pdo->query($sql);
                $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

                // Default image path
                $default_image = "4-Pillars-III-COLOR-MODIFIED-2048x1365-1.jpg";

                if (empty($posts)) {
                    echo '<div class="col-12 text-center"><p>No announcements available at this time.</p></div>';
                } else {
                    foreach ($posts as $post) {
                        // Determine which image to use
                        $post_image = !empty($post['featured_image']) ?
                            $post['featured_image'] :
                            $default_image;

                        // Truncate content for preview
                        $preview_content = strlen($post['content']) > 100 ?
                            substr(strip_tags($post['content']), 0, 100) . '...' :
                            strip_tags($post['content']);

                        // Determine icon based on category
                        $icon = $post['category'] == 'Event' ?
                            'bi-calendar-event-fill' : 'bi-megaphone-fill';

                        echo '
            <div class="col-md-6">
                <div class="card announcement-card h-100">
                    <div class="card-body">
                        <div style="height: 200px; overflow: hidden;">
                            <img src="uploads/posts/' . $post_image . '" class="img-fluid w-100 h-100 object-fit-cover" alt="' . htmlspecialchars($post['title']) . '">
                        </div>
                        <h5 class="card-title mt-3">
                            <i class="bi ' . $icon . ' text-primary me-2"></i>
                            ' . htmlspecialchars($post['title']) . '
                        </h5>
                        <p class="card-text">' . $preview_content . '</p>
                        <div class="text-center">
                            <button class="btn btn-sm btn-outline-beige" 
                                    data-bs-toggle="modal" 
                                    data-bs-target="#postModal' . $post['id'] . '">
                                Read More
                            </button>
                        </div>
                    </div>
                </div>
            </div>';
                    }
                }
                ?>
            </div>
        </div>
    </section>

    <style>
        /* For all modals with WYSIWYG content */
        .modal .post-content img {
            max-width: 100%;
            height: auto;
            display: block;
            margin: 1rem auto;
        }

        .modal .post-content {
            overflow-wrap: break-word;
        }

        /* Optional scrolling for long content */
        .modal .post-content {
            max-height: 60vh;
            overflow-y: auto;
        }

        /* Featured image styling */
        .modal .modal-body>img.img-fluid {
            max-height: 50vh;
            object-fit: contain;
            width: 100%;
        }
    </style>

    <!-- Dynamic Modals for Each Post -->
    <?php
    foreach ($posts as $post) {
        $post_image = !empty($post['featured_image']) ?
            htmlspecialchars($post['featured_image']) :
            $default_image;

        echo '
    <div class="modal fade" id="postModal' . $post['id'] . '" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">' . htmlspecialchars($post['title']) . '</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                   
                    <div class="post-content">' . $post['content'] . '</div>
                    <div class="mt-3 text-muted">
                        <small>Posted on: ' . date('M d, Y', strtotime($post['created_at'])) . '</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>';
    }
    ?>




    <section class="carousel-section text-center">
        <div data-aos="zoom-in-up">
            <h3 class="mb-4"><b>What Our Students Say</b></h3>
            <div id="testimonialCarousel" class="carousel slide mx-auto" style="max-width:800px;" data-bs-ride="carousel">
                <div class="carousel-inner">
                    <div class="carousel-item active">
                        <blockquote class="blockquote">
                            <p>"CCS helped me build the skills I now use in the tech industry."</p>
                            <footer class="blockquote-footer">Althea, BSCS Graduate 2022</footer>
                        </blockquote>
                    </div>
                    <div class="carousel-item">
                        <blockquote class="blockquote">
                            <p>"The professors and labs are world-class. I found my passion here."</p>
                            <footer class="blockquote-footer">Miguel, BSIT Student</footer>
                        </blockquote>
                    </div>
                    <div class="carousel-item">
                        <blockquote class="blockquote">
                            <p>"More than just tech, I learned to lead and serve through CCS."</p>
                            <footer class="blockquote-footer">Rachel, BSCS Alumna</footer>
                        </blockquote>
                    </div>
                </div>
                <button class="carousel-control-prev" type="button" data-bs-target="#testimonialCarousel" data-bs-slide="prev">
                    <span class="carousel-control-prev-icon"></span>
                </button>
                <button class="carousel-control-next" type="button" data-bs-target="#testimonialCarousel" data-bs-slide="next">
                    <span class="carousel-control-next-icon"></span>
                </button>
            </div>
        </div>
    </section>

    <section class="newsletter">
        <div data-aos="zoom-in-down">
            <h4><b>Subscribe to our Newsletter</b></h4>
            <p>Stay updated on programs, events, and tech opportunities.</p>
            <form class="d-flex justify-content-center mt-3 flex-wrap gap-2">
                <input type="email" class="form-control" placeholder="Enter your email" required />
                <button class="btn btn-light text-danger fw-bold">Subscribe</button>
            </form>
        </div>
    </section>

    <section class="py-5 bg-light">
        <div class="container">
            <div class="row g-4 align-items-center">

                <div class="col-md-6">
                    <h4 class="mb-4" data-aos="fade-right">Contact Us</h4>

                    <p data-aos="fade-up" data-aos-delay="100">
                        <i class="bi bi-building me-2 text-primary"></i>
                        <strong>College of Computer Studies</strong><br>
                        Aurelius University<br>
                        Bagumbayan Sur, Naga City, Philippines
                    </p>
                    <p data-aos="fade-up" data-aos-delay="200">
                        <i class="bi bi-envelope-fill me-2 text-primary"></i>
                        <strong>Email:</strong> <a href="mailto:ccs@AU.edu.ph">ccs@AU.edu.ph</a>
                    </p>
                    <p data-aos="fade-up" data-aos-delay="300">
                        <i class="bi bi-telephone-fill me-2 text-primary"></i>
                        <strong>Phone:</strong> (054) 472-2368 local 4201
                    </p>
                    <p data-aos="fade-up" data-aos-delay="400">
                        <i class="bi bi-clock-fill me-2 text-primary"></i>
                        <strong>Office Hours:</strong><br>
                        Monday - Friday: 8:00 AM - 5:00 PM
                    </p>
                </div>


                <!-- Map -->
                <div class="col-md-6">
                    <iframe
                        src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3931.3575875530817!2d123.19364561427916!3d13.621204787658985!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x33a18d5b68a5fc3f%3A0x18ffb84d435af41!2sAteneo%20de%20Naga%20University!5e0!3m2!1sen!2sph!4v1620372951307!5m2!1sen!2sph"
                        width="100%" height="300" style="border:0;" allowfullscreen="" loading="lazy"></iframe>
                </div>
            </div>
        </div>
    </section>
    <footer>
        <p>&copy; 2025 Aurelius University - CCS. All rights reserved.</p>
    </footer>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        AOS.init();

        const btn = document.getElementById('hamburger');
        const menu = document.getElementById('mobileMenu');
        btn.addEventListener('click', () => {
            btn.classList.toggle('open');
            menu.classList.toggle('open');
        });
    </script>
</body>

</html>