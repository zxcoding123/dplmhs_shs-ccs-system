<?php
include('processes/server/alert_system.php');

$page_title = 'About the College';
$page_description = 'Mission, vision, values and history of the College of Computer Studies, Aurelius University.';
$active_page = 'about';
include __DIR__ . '/includes/site-header.php';
?>

<section class="au-page-hero">
    <div class="container">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb au-breadcrumb">
                <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                <li class="breadcrumb-item active" aria-current="page">About</li>
            </ol>
        </nav>
        <span class="au-eyebrow">About the College</span>
        <h1>Knowledge and virtue in the service of others</h1>
        <p>The College of Computer Studies of Aurelius University forms computing professionals of competence, conscience and compassion.</p>
    </div>
</section>

<section class="au-section">
    <div class="container">
        <div class="row g-4">
            <div class="col-md-4" data-aos="fade-up">
                <div class="au-card">
                    <i class="bi bi-flag au-card-icon"></i>
                    <h3>Our Mission</h3>
                    <p class="mb-0">To provide quality education and foster innovation in computing and information technology, empowering students to become globally competitive professionals.</p>
                </div>
            </div>
            <div class="col-md-4" data-aos="fade-up" data-aos-delay="100">
                <div class="au-card">
                    <i class="bi bi-eye au-card-icon"></i>
                    <h3>Our Vision</h3>
                    <p class="mb-0">To be a leading institution in computing education, research and community service, contributing to the advancement of technology and society.</p>
                </div>
            </div>
            <div class="col-md-4" data-aos="fade-up" data-aos-delay="200">
                <div class="au-card">
                    <i class="bi bi-gem au-card-icon"></i>
                    <h3>Our Values</h3>
                    <p class="mb-0">We uphold integrity, excellence, innovation and inclusivity, ensuring a supportive and dynamic learning environment for all.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="au-section au-section-paper">
    <div class="container">
        <div class="row align-items-center g-5">
            <div class="col-lg-6" data-aos="fade-right">
                <div class="au-split-img">
                    <img src="external/img/Kanto-TDRI-DMOC-4.webp" alt="Aurelius University campus">
                </div>
            </div>
            <div class="col-lg-6" data-aos="fade-left">
                <span class="au-eyebrow">Our History</span>
                <h2 class="au-section-title">A tradition of computing education in the region</h2>
                <hr class="au-rule">
                <p>The College of Computer Studies at Aurelius University has long been at the forefront of computing education in the region, producing thousands of graduates who excel in information technology locally and internationally.</p>
                <p class="mb-0">Our commitment to excellence and innovation has earned recognition as one of the country's leading computing schools. We continue to evolve with the changing landscape of technology so that our students are prepared for the challenges of the future.</p>
            </div>
        </div>
    </div>
</section>

<?php
$page_scripts = '<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>';
if (isset($_SESSION['STATUS']) && $_SESSION['STATUS'] == 'SEMESTER_NEAR_ENDING_NOTICE') {
    $page_scripts .= "
    <script>
        Swal.fire({
            title: 'Semester is almost ending!',
            text: 'The current semester is nearing its end. Teachers, please finalize all lessons and submit your grades. Students, be sure to submit all your assignments or requirements promptly.',
            icon: 'info'
        });
    </script>";
    unset($_SESSION['STATUS']);
} elseif (isset($_SESSION['STATUS']) && $_SESSION['STATUS'] == 'SEMESTER_ENDED_NOTICE') {
    $page_scripts .= "
    <script>
        Swal.fire({
            title: 'Semester has ended!',
            text: 'The semester has officially concluded. Teachers, ensure all grades are finalized. Students, make sure all your submissions are complete and any queries are resolved.',
            icon: 'warning'
        });
    </script>";
    unset($_SESSION['STATUS']);
}
include __DIR__ . '/includes/site-footer.php';
?>
