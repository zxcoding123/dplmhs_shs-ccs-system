<?php
include('processes/server/alert_system.php');

$page_title = 'Contact Us';
$page_description = 'Contact the Office of the Principal, Don Pablo Lorenzo Memorial High School – Stand-Alone Senior High School.';
$active_page = 'contact';
include __DIR__ . '/includes/site-header.php';
?>

<section class="au-page-hero">
    <div class="container">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb au-breadcrumb">
                <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                <li class="breadcrumb-item active" aria-current="page">Contact</li>
            </ol>
        </nav>
        <span class="au-eyebrow">Get in Touch</span>
        <h1>Contact Us</h1>
        <p>Have questions about enrollment, our strands or services? Reach the Office of the Principal through any of the channels below.</p>
    </div>
</section>

<section class="au-section">
    <div class="container">
        <div class="row g-4">
            <div class="col-md-4" data-aos="fade-up">
                <div class="au-card text-center">
                    <i class="bi bi-geo-alt au-card-icon"></i>
                    <h3>Our Address</h3>
                    <p class="mb-0">Don Pablo Lorenzo Memorial High School<br>Stand-Alone Senior High School, Zamboanga City, Philippines</p>
                </div>
            </div>
            <div class="col-md-4" data-aos="fade-up" data-aos-delay="100">
                <div class="au-card text-center">
                    <i class="bi bi-telephone au-card-icon"></i>
                    <h3>Call Us</h3>
                    <p class="mb-0">(062) 000-0000<br>Monday – Friday, 8:00 AM – 5:00 PM</p>
                </div>
            </div>
            <div class="col-md-4" data-aos="fade-up" data-aos-delay="200">
                <div class="au-card text-center">
                    <i class="bi bi-envelope au-card-icon"></i>
                    <h3>Email Us</h3>
                    <p class="mb-0"><a href="mailto:info@dplmhs-shs.edu.ph">info@dplmhs-shs.edu.ph</a></p>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="au-section au-section-paper">
    <div class="container" data-aos="fade-up">
        <div class="text-center mb-4">
            <span class="au-eyebrow">Visit Us</span>
            <h2 class="au-section-title">Find us on campus</h2>
            <hr class="au-rule">
        </div>
        <iframe class="au-map" style="height: 420px;" title="Map of Don Pablo Lorenzo Memorial High School"
            src="https://www.google.com/maps?q=Don+Pablo+Lorenzo+Memorial+High+School,+Zamboanga+City&output=embed"
            allowfullscreen loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
    </div>
</section>

<?php include __DIR__ . '/includes/site-footer.php'; ?>
