<?php
session_start();
$page_title = 'Academic Programs';
$page_description = 'Undergraduate, graduate and professional programs offered by the College of Computer Studies, Aurelius University.';
$active_page = 'programs';
include __DIR__ . '/includes/site-header.php';
?>

<section class="au-page-hero">
    <div class="container">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb au-breadcrumb">
                <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                <li class="breadcrumb-item active" aria-current="page">Academic Programs</li>
            </ol>
        </nav>
        <span class="au-eyebrow">Academics</span>
        <h1>Academic Programs</h1>
        <p>Explore the undergraduate, graduate and professional offerings of the College of Computer Studies.</p>
    </div>
</section>

<section class="au-section">
    <div class="container">
        <div class="row g-4">
            <div class="col-md-6" id="bscs" data-aos="fade-up">
                <div class="au-card">
                    <span class="au-program-level text-uppercase" style="color: var(--au-crimson);">Undergraduate</span>
                    <div class="d-flex align-items-center gap-3 mb-3 mt-1">
                        <i class="bi bi-cpu au-card-icon mb-0"></i>
                        <h3 class="mb-0">BS in Computer Science</h3>
                    </div>
                    <p>Focused on advanced programming, artificial intelligence, algorithms and software engineering.</p>
                    <ul>
                        <li>Duration: 4 years</li>
                        <li>Tracks: AI &amp; Data Science, Software Development</li>
                        <li>Capstone and research project</li>
                    </ul>
                    <a href="contact_us.php" class="btn btn-au-navy mt-2">Inquire Now</a>
                </div>
            </div>

            <div class="col-md-6" id="bsit" data-aos="fade-up" data-aos-delay="100">
                <div class="au-card">
                    <span class="au-program-level text-uppercase" style="color: var(--au-crimson);">Undergraduate</span>
                    <div class="d-flex align-items-center gap-3 mb-3 mt-1">
                        <i class="bi bi-hdd-network au-card-icon mb-0"></i>
                        <h3 class="mb-0">BS in Information Technology</h3>
                    </div>
                    <p>Practical training in web systems, databases, networking, cybersecurity and IT infrastructure.</p>
                    <ul>
                        <li>Duration: 4 years</li>
                        <li>Tracks: Web &amp; Mobile Development, Network Administration</li>
                        <li>Internship and capstone</li>
                    </ul>
                    <a href="contact_us.php" class="btn btn-au-navy mt-2">Inquire Now</a>
                </div>
            </div>

            <div class="col-md-6" id="data-analytics" data-aos="fade-up" data-aos-delay="200">
                <div class="au-card">
                    <span class="au-program-level text-uppercase" style="color: var(--au-crimson);">Professional</span>
                    <div class="d-flex align-items-center gap-3 mb-3 mt-1">
                        <i class="bi bi-bar-chart-line au-card-icon mb-0"></i>
                        <h3 class="mb-0">Professional Certificate in Data Analytics</h3>
                    </div>
                    <p>Designed for working professionals. Learn Python, SQL, Excel and data storytelling in six months.</p>
                    <ul>
                        <li>Flexible schedule</li>
                        <li>Industry-aligned curriculum</li>
                        <li>Certificate of Completion</li>
                    </ul>
                    <a href="contact_us.php" class="btn btn-au-navy mt-2">Enroll Now</a>
                </div>
            </div>

            <div class="col-md-6" id="mscs" data-aos="fade-up" data-aos-delay="300">
                <div class="au-card">
                    <span class="au-program-level text-uppercase" style="color: var(--au-crimson);">Graduate</span>
                    <div class="d-flex align-items-center gap-3 mb-3 mt-1">
                        <i class="bi bi-mortarboard au-card-icon mb-0"></i>
                        <h3 class="mb-0">MS in Computer Science</h3>
                    </div>
                    <p>A research-focused graduate program for specialization in machine learning, systems or theory.</p>
                    <ul>
                        <li>Thesis or non-thesis track</li>
                        <li>Evening and weekend classes</li>
                        <li>For IT faculty and industry professionals</li>
                    </ul>
                    <a href="contact_us.php" class="btn btn-au-navy mt-2">Apply Now</a>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include __DIR__ . '/includes/site-footer.php'; ?>
