<?php
include('processes/server/alert_system.php');

$page_title = 'Contact Us';
$page_description = 'Contact the Office of the Dean, College of Computer Studies, Aurelius University.';
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
        <p>Have questions about our programs or services? Reach the Office of the Dean through any of the channels below.</p>
    </div>
</section>

<section class="au-section">
    <div class="container">
        <div class="row g-4">
            <div class="col-md-4" data-aos="fade-up">
                <div class="au-card text-center">
                    <i class="bi bi-geo-alt au-card-icon"></i>
                    <h3>Our Address</h3>
                    <p class="mb-0">College of Computer Studies, Aurelius University<br>Bagumbayan Sur, Naga City, Philippines</p>
                </div>
            </div>
            <div class="col-md-4" data-aos="fade-up" data-aos-delay="100">
                <div class="au-card text-center">
                    <i class="bi bi-telephone au-card-icon"></i>
                    <h3>Call Us</h3>
                    <p class="mb-0">(054) 472-2368 local 4201<br>Monday – Friday, 8:00 AM – 5:00 PM</p>
                </div>
            </div>
            <div class="col-md-4" data-aos="fade-up" data-aos-delay="200">
                <div class="au-card text-center">
                    <i class="bi bi-envelope au-card-icon"></i>
                    <h3>Email Us</h3>
                    <p class="mb-0"><a href="mailto:ccs@au.edu.ph">ccs@au.edu.ph</a></p>
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
        <iframe class="au-map" style="height: 420px;" title="Map of Aurelius University"
            src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3931.3575875530817!2d123.19364561427916!3d13.621204787658985!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x33a18d5b68a5fc3f%3A0x18ffb84d435af41!2sAteneo%20de%20Naga%20University!5e0!3m2!1sen!2sph!4v1620372951307!5m2!1sen!2sph"
            allowfullscreen loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
    </div>
</section>

<?php include __DIR__ . '/includes/site-footer.php'; ?>
