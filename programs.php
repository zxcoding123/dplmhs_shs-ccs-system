<?php
session_start();
$page_title = 'Tracks & Strands';
$page_description = 'Senior high school tracks and strands offered by Don Pablo Lorenzo Memorial High School – Stand-Alone Senior High School.';
$active_page = 'programs';
include __DIR__ . '/includes/site-header.php';
?>

<section class="au-page-hero">
    <div class="container">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb au-breadcrumb">
                <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                <li class="breadcrumb-item active" aria-current="page">Tracks &amp; Strands</li>
            </ol>
        </nav>
        <span class="au-eyebrow">Academics</span>
        <h1>Tracks &amp; Strands</h1>
        <p>Explore the Grade 11 and Grade 12 offerings of the Stand-Alone Senior High School under the DepEd K to 12 curriculum.</p>
    </div>
</section>

<section class="au-section">
    <div class="container">
        <div class="row g-4">
            <div class="col-md-6" id="stem" data-aos="fade-up">
                <div class="au-card">
                    <span class="au-program-level text-uppercase" style="color: var(--au-crimson);">Academic Track</span>
                    <div class="d-flex align-items-center gap-3 mb-3 mt-1">
                        <i class="bi bi-cpu au-card-icon mb-0"></i>
                        <h3 class="mb-0">Science, Technology, Engineering and Mathematics (STEM)</h3>
                    </div>
                    <p>For learners planning to take science, engineering, health, mathematics or IT courses in college.</p>
                    <ul>
                        <li>Duration: 2 years (Grades 11–12)</li>
                        <li>Pre-calculus, calculus, biology, chemistry and physics</li>
                        <li>Research or capstone project</li>
                    </ul>
                    <a href="contact_us.php" class="btn btn-au-navy mt-2">Inquire Now</a>
                </div>
            </div>

            <div class="col-md-6" id="abm" data-aos="fade-up" data-aos-delay="100">
                <div class="au-card">
                    <span class="au-program-level text-uppercase" style="color: var(--au-crimson);">Academic Track</span>
                    <div class="d-flex align-items-center gap-3 mb-3 mt-1">
                        <i class="bi bi-graph-up-arrow au-card-icon mb-0"></i>
                        <h3 class="mb-0">Accountancy, Business and Management (ABM)</h3>
                    </div>
                    <p>For future accountants, entrepreneurs and managers who want a head start in business.</p>
                    <ul>
                        <li>Duration: 2 years (Grades 11–12)</li>
                        <li>Accounting, business math, economics and marketing</li>
                        <li>Business plan and work immersion</li>
                    </ul>
                    <a href="contact_us.php" class="btn btn-au-navy mt-2">Inquire Now</a>
                </div>
            </div>

            <div class="col-md-6" id="humss" data-aos="fade-up" data-aos-delay="200">
                <div class="au-card">
                    <span class="au-program-level text-uppercase" style="color: var(--au-crimson);">Academic Track</span>
                    <div class="d-flex align-items-center gap-3 mb-3 mt-1">
                        <i class="bi bi-chat-quote au-card-icon mb-0"></i>
                        <h3 class="mb-0">Humanities and Social Sciences (HUMSS)</h3>
                    </div>
                    <p>For learners interested in education, law, journalism, psychology, social work and public service.</p>
                    <ul>
                        <li>Duration: 2 years (Grades 11–12)</li>
                        <li>Creative writing, philosophy, politics and governance</li>
                        <li>Community engagement and research</li>
                    </ul>
                    <a href="contact_us.php" class="btn btn-au-navy mt-2">Inquire Now</a>
                </div>
            </div>

            <div class="col-md-6" id="gas" data-aos="fade-up" data-aos-delay="300">
                <div class="au-card">
                    <span class="au-program-level text-uppercase" style="color: var(--au-crimson);">Academic Track</span>
                    <div class="d-flex align-items-center gap-3 mb-3 mt-1">
                        <i class="bi bi-compass au-card-icon mb-0"></i>
                        <h3 class="mb-0">General Academic Strand (GAS)</h3>
                    </div>
                    <p>For learners still exploring their options, with electives drawn from several academic strands.</p>
                    <ul>
                        <li>Duration: 2 years (Grades 11–12)</li>
                        <li>Flexible mix of humanities, social science and business electives</li>
                        <li>Research project and work immersion</li>
                    </ul>
                    <a href="contact_us.php" class="btn btn-au-navy mt-2">Inquire Now</a>
                </div>
            </div>

            <div class="col-md-6" id="tvl" data-aos="fade-up">
                <div class="au-card">
                    <span class="au-program-level text-uppercase" style="color: var(--au-crimson);">Technical-Vocational-Livelihood Track</span>
                    <div class="d-flex align-items-center gap-3 mb-3 mt-1">
                        <i class="bi bi-wrench-adjustable au-card-icon mb-0"></i>
                        <h3 class="mb-0">Technical-Vocational-Livelihood (TVL)</h3>
                    </div>
                    <p>Hands-on specializations that build job-ready skills and prepare learners for TESDA assessment.</p>
                    <ul>
                        <li>Duration: 2 years (Grades 11–12)</li>
                        <li>Specializations such as ICT, Home Economics and Industrial Arts</li>
                        <li>Work immersion and National Certificate (NC) assessment</li>
                    </ul>
                    <a href="contact_us.php" class="btn btn-au-navy mt-2">Inquire Now</a>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include __DIR__ . '/includes/site-footer.php'; ?>
