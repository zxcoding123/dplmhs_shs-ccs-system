<?php
include('processes/server/alert_system.php');

$page_title = 'Help Center';
$page_description = 'Answers to common questions about the Don Pablo Lorenzo Memorial High School – Stand-Alone Senior High School Student Management System.';
$active_page = 'help';
include __DIR__ . '/includes/site-header.php';
?>

<section class="au-page-hero">
    <div class="container">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb au-breadcrumb">
                <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                <li class="breadcrumb-item active" aria-current="page">Help Center</li>
            </ol>
        </nav>
        <span class="au-eyebrow">Support</span>
        <h1>Help Center</h1>
        <p>Find answers to common questions about the Student Management System.</p>
    </div>
</section>

<section class="au-section">
    <div class="container" style="max-width: 860px;">
        <div class="accordion au-accordion" id="faqAccordion">
            <div class="accordion-item">
                <h2 class="accordion-header" id="headingOne">
                    <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#faq1" aria-expanded="true" aria-controls="faq1">
                        How do I reset my password?
                    </button>
                </h2>
                <div id="faq1" class="accordion-collapse collapse show" aria-labelledby="headingOne" data-bs-parent="#faqAccordion">
                    <div class="accordion-body">
                        Click the "Forgot Password" link on the <a href="login/index.php">login page</a> and follow the instructions sent to your email.
                    </div>
                </div>
            </div>

            <div class="accordion-item">
                <h2 class="accordion-header" id="headingTwo">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq2" aria-expanded="false" aria-controls="faq2">
                        How do I contact support?
                    </button>
                </h2>
                <div id="faq2" class="accordion-collapse collapse" aria-labelledby="headingTwo" data-bs-parent="#faqAccordion">
                    <div class="accordion-body">
                        Email us at <a href="mailto:info@dplmhs-shs.edu.ph">info@dplmhs-shs.edu.ph</a> or call <strong>(062) 975 1294</strong>, Monday to Friday, 8:00 AM – 5:00 PM.
                    </div>
                </div>
            </div>

            <div class="accordion-item">
                <h2 class="accordion-header" id="headingThree">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq3" aria-expanded="false" aria-controls="faq3">
                        Where can I access my grades?
                    </button>
                </h2>
                <div id="faq3" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#faqAccordion">
                    <div class="accordion-body">
                        Log in to the Student Management System and open the "Grades" section of your dashboard.
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include __DIR__ . '/includes/site-footer.php'; ?>
