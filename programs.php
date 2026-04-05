<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Our Programs - CCS | Ateneo de Naga University</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;700&display=swap" rel="stylesheet" />
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <style>
        :root {
            --main-color: #293891;
            --accent-color: #c2a74d;
            --highlight: #e92b2d;
            --white: #ffffff;
        }

        header {
            background-color: var(--main-color);
            color: var(--white);
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
        }

        header h1 {
            font-size: 1.5rem;
            margin: 0;
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

        .hero p {
            font-size: 1.2rem;
            max-width: 600px;
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

        body {
            font-family: 'Montserrat', sans-serif;
            background-color: var(--white);
            color: #333;
        }

        .logo {
            width: 40px;
            height: auto;
        }


        .page-header {
            background-color: var(--main-color);
            color: var(--white);
            padding: 4rem 1rem;
            text-align: center;
        }

        .program-card {
            border-left: 5px solid var(--accent-color);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            transition: transform 0.2s ease;
        }

        .program-card:hover {
            transform: translateY(-5px);
        }

        .program-icon {
            font-size: 3rem;
            color: var(--main-color);
        }

        .btn-program {
            background-color: var(--accent-color);
            color: var(--main-color);
            border: none;
        }

        .btn-program:hover {
            background-color: var(--highlight);
            color: var(--white);
        }


        .login-btn {
            background-color: var(--accent-color);
            color: var(--main-color);
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            text-decoration: none;
            font-weight: bold;
        }

        .login-btn:hover {
            background-color: var(--highlight);
            color: var(--white);
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

        footer {
            background-color: var(--main-color);
            color: var(--white);
            text-align: center;
            padding: 1rem;
            margin-top: 4rem;
        }
    </style>

</head>

<body>

    <header>
        <h6>
            <img src="external/img/ADNU_Logo.png" class="logo">
            <span class="header-text">
                &nbsp; Ateneo de Naga University - College of Computer Studies
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

    <div class="page-header">
        <h1 class="display-5">Our Programs</h1>
        <p class="lead">Explore academic offerings under the College of Computer Studies</p>
    </div>

    <section class="container py-5">
        <div class="row g-4">

            <!-- BSCS -->
            <div class="col-md-6" data-aos="fade-up" data-aos-delay="100">
                <div class="p-4 bg-white program-card rounded">
                    <div class="d-flex align-items-center mb-3">
                        <i class="bi bi-cpu-fill program-icon me-3"></i>
                        <h4 class="mb-0">BS in Computer Science</h4>
                    </div>
                    <p>
                        Focused on advanced programming, artificial intelligence, algorithms, and software engineering.
                    </p>
                    <ul>
                        <li>Duration: 4 years</li>
                        <li>Tracks: AI & Data Science, Software Development</li>
                        <li>Capstone + Research Project</li>
                    </ul>
                    <a href="#" class="btn btn-program mt-2">Learn More</a>
                </div>
            </div>

            <!-- BSIT -->
            <div class="col-md-6" data-aos="fade-up" data-aos-delay="200">
                <div class="p-4 bg-white program-card rounded">
                    <div class="d-flex align-items-center mb-3">
                        <i class="bi bi-hdd-network-fill program-icon me-3"></i>
                        <h4 class="mb-0">BS in Information Technology</h4>
                    </div>
                    <p>
                        Practical training in web systems, databases, networking, cybersecurity, and IT infrastructure.
                    </p>
                    <ul>
                        <li>Duration: 4 years</li>
                        <li>Tracks: Web & Mobile Development, Network Admin</li>
                        <li>Internship + Capstone</li>
                    </ul>
                    <a href="#" class="btn btn-program mt-2">Learn More</a>
                </div>
            </div>

            <!-- New Short-Term or Master's Program -->
            <div class="col-md-6" data-aos="fade-up" data-aos-delay="300">
                <div class="p-4 bg-white program-card rounded">
                    <div class="d-flex align-items-center mb-3">
                        <i class="bi bi-person-workspace program-icon me-3"></i>
                        <h4 class="mb-0">Professional Certificate in Data Analytics</h4>
                    </div>
                    <p>
                        Designed for working professionals. Learn Python, SQL, Excel, and data storytelling in 6 months.
                    </p>
                    <ul>
                        <li>Flexible schedule</li>
                        <li>Industry-aligned curriculum</li>
                        <li>Certificate of Completion</li>
                    </ul>
                    <a href="#" class="btn btn-program mt-2">Enroll Now</a>
                </div>
            </div>

            <!-- Master's Program -->
            <div class="col-md-6" data-aos="fade-up" data-aos-delay="400">
                <div class="p-4 bg-white program-card rounded">
                    <div class="d-flex align-items-center mb-3">
                        <i class="bi bi-mortarboard-fill program-icon me-3"></i>
                        <h4 class="mb-0">MS in Computer Science</h4>
                    </div>
                    <p>
                        Graduate-level research-focused program for specialization in machine learning, systems, or theory.
                    </p>
                    <ul>
                        <li>Thesis or Non-Thesis Track</li>
                        <li>Evening and weekend classes</li>
                        <li>For IT faculty and industry professionals</li>
                    </ul>
                    <a href="#" class="btn btn-program mt-2">Apply Now</a>
                </div>
            </div>

        </div>
    </section>

    <footer>
        <p>&copy; 2025 College of Computer Studies, Ateneo de Naga University</p>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        AOS.init();
    </script>
</body>

</html>