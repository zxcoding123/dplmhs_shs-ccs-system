<?php
include('processes/server/alert_system.php');

$page_title = 'About the School';
$page_description = 'Mission, vision, core values and history of Don Pablo Lorenzo Memorial High School – Stand-Alone Senior High School.';
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
        <span class="au-eyebrow">About the School</span>
        <h1>Forming learners ready for college, careers and life</h1>
        <p>Don Pablo Lorenzo Memorial High School – Stand-Alone Senior High School provides Grades 11 and 12 learners with quality, relevant and values-driven basic education.</p>
    </div>
</section>

<section class="au-section">
    <div class="container">
        <div class="row g-4">
            <div class="col-md-4" data-aos="fade-up">
                <div class="au-card">
                    <i class="bi bi-flag au-card-icon"></i>
                    <h3>Our Mission</h3>
                    <p class="mb-0">To protect and promote the right of every learner to quality, equitable, culture-based and complete basic education, in a learner-friendly, safe and motivating environment.</p>
                </div>
            </div>
            <div class="col-md-4" data-aos="fade-up" data-aos-delay="100">
                <div class="au-card">
                    <i class="bi bi-eye au-card-icon"></i>
                    <h3>Our Vision</h3>
                    <p class="mb-0">We dream of learners who love their country and whose values and competencies enable them to realize their full potential and contribute meaningfully to building the nation.</p>
                </div>
            </div>
            <div class="col-md-4" data-aos="fade-up" data-aos-delay="200">
                <div class="au-card">
                    <i class="bi bi-gem au-card-icon"></i>
                    <h3>Our Core Values</h3>
                    <p class="mb-0">Maka-Diyos, Maka-tao, Makakalikasan and Makabansa — guiding how we teach, learn and serve our community.</p>
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
                    <img src="external/img/Kanto-TDRI-DMOC-4.webp" alt="Don Pablo Lorenzo Memorial High School campus">
                </div>
            </div>
            <div class="col-lg-6" data-aos="fade-left">
                <span class="au-eyebrow">Our School</span>
                <h2 class="au-section-title">A senior high school built on a proud tradition</h2>
                <hr class="au-rule">
                <p>The Stand-Alone Senior High School carries forward the legacy of Don Pablo Lorenzo Memorial High School, serving Grade 11 and Grade 12 learners of Zamboanga City under the DepEd K to 12 program.</p>
                <p class="mb-0">Through academic and technical-vocational tracks, work immersion and values formation, we prepare our graduates for higher education, employment, entrepreneurship and active citizenship.</p>
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
