<?php
session_start();
include __DIR__ . '/database/connection.php';
require_once __DIR__ . '/includes/site-helpers.php';
$pdo = Database::getConnection();

// Latest published posts: the first is featured, the rest are listed beside it.
$stmt = $pdo->query("SELECT * FROM posts WHERE status = 'Published' ORDER BY created_at DESC LIMIT 5");
$posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
$featured_post = array_shift($posts);

$gallery_preview = array_slice(au_gallery_images($pdo), 0, 5);

$page_title = '';
$active_page = 'home';
include __DIR__ . '/includes/site-header.php';
?>

<!-- Hero -->
<section class="au-hero">
    <div class="container">
        <div class="row align-items-center g-5">
            <div class="col-lg-7" data-aos="fade-right">
                <span class="au-eyebrow">Stand-Alone Senior High School</span>
                <h1>Preparing learners for <em>college, careers</em> and life.</h1>
                <p>Don Pablo Lorenzo Memorial High School – Stand-Alone Senior High School offers Grades 11 and 12 under the DepEd K to 12 curriculum, equipping every learner with the knowledge, skills and values to succeed beyond high school.</p>
                <div class="d-flex flex-wrap gap-3 justify-content-center justify-content-lg-start">
                    <a href="programs.php" class="btn btn-au-gold btn-lg">Explore Tracks &amp; Strands</a>
                    <a href="news.php" class="btn btn-au-outline btn-lg">Latest News</a>
                </div>
                <p class="au-hero-motto">Maka-Diyos · Maka-tao · Makakalikasan · Makabansa</p>
            </div>
            <div class="col-lg-5 text-center d-none d-lg-block" data-aos="zoom-in" data-aos-delay="150">
                <img src="external/img/dp_logo.avif" class="au-hero-crest" alt="Don Pablo Lorenzo Memorial High School seal">
            </div>
        </div>
    </div>
</section>

<!-- Stats ribbon -->
<section class="au-stats" aria-label="The school at a glance">
    <div class="container">
        <div class="row g-0">
            <div class="col-6 col-md-3 au-stat"><span class="au-stat-number">11–12</span><span class="au-stat-label">Grade Levels</span></div>
            <div class="col-6 col-md-3 au-stat"><span class="au-stat-number">2</span><span class="au-stat-label">Tracks Offered</span></div>
            <div class="col-6 col-md-3 au-stat"><span class="au-stat-number">4</span><span class="au-stat-label">Academic Strands</span></div>
            <div class="col-6 col-md-3 au-stat"><span class="au-stat-number">K to 12</span><span class="au-stat-label">DepEd Curriculum</span></div>
        </div>
    </div>
</section>

<!-- Welcome from the Principal -->
<section class="au-section">
    <div class="container">
        <div class="row align-items-center g-5">
            <div class="col-lg-6" data-aos="fade-right">
                <div class="au-split-img">
                    <img src="external/img/4-Pillars-III-COLOR-MODIFIED-2048x1365-1.jpg" alt="Learners and teachers of Don Pablo Lorenzo Memorial High School">
                </div>
            </div>
            <div class="col-lg-6" data-aos="fade-left">
                <span class="au-eyebrow">Welcome from the Principal</span>
                <h2 class="au-section-title">An education that forms the whole learner</h2>
                <hr class="au-rule">
                <p>Senior high school is where learners choose their path. At DPLMHS, we help every learner discover their strengths, master the competencies of their strand and grow in character.</p>
                <p class="au-quote">“We do not simply prepare learners for examinations. We prepare them for higher education, meaningful work and responsible citizenship.”</p>
                <p class="au-meta mb-4">Office of the Principal, Stand-Alone Senior High School</p>
                <a href="about_us.php" class="au-link-arrow">Learn more about the school <i class="bi bi-arrow-right"></i></a>
            </div>
        </div>
    </div>
</section>

<!-- Four pillars -->
<section class="au-section au-section-paper">
    <div class="container">
        <div class="text-center mb-5" data-aos="fade-up">
            <span class="au-eyebrow">Why DPLMHS</span>
            <h2 class="au-section-title">What we commit to every learner</h2>
            <hr class="au-rule">
        </div>
        <div class="row g-4">
            <div class="col-md-6 col-lg-3" data-aos="fade-up">
                <div class="au-pillar">
                    <span class="au-pillar-icon"><i class="bi bi-mortarboard-fill"></i></span>
                    <h3>Academic Excellence</h3>
                    <p>A strong K to 12 curriculum that prepares learners for college entrance and lifelong learning.</p>
                </div>
            </div>
            <div class="col-md-6 col-lg-3" data-aos="fade-up" data-aos-delay="100">
                <div class="au-pillar">
                    <span class="au-pillar-icon"><i class="bi bi-tools"></i></span>
                    <h3>Career Readiness</h3>
                    <p>Work immersion and skills training that connect classroom learning to real workplaces.</p>
                </div>
            </div>
            <div class="col-md-6 col-lg-3" data-aos="fade-up" data-aos-delay="200">
                <div class="au-pillar">
                    <span class="au-pillar-icon"><i class="bi bi-heart-fill"></i></span>
                    <h3>Values Formation</h3>
                    <p>Guidance and homeroom programs that nurture character, wellbeing and good citizenship.</p>
                </div>
            </div>
            <div class="col-md-6 col-lg-3" data-aos="fade-up" data-aos-delay="300">
                <div class="au-pillar">
                    <span class="au-pillar-icon"><i class="bi bi-people-fill"></i></span>
                    <h3>Community</h3>
                    <p>Partnerships with parents, local government and industry that support every learner.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- News & announcements -->
<section class="au-section" id="news">
    <div class="container">
        <div class="au-section-head" data-aos="fade-up">
            <div>
                <span class="au-eyebrow">News &amp; Announcements</span>
                <h2 class="au-section-title mb-0">From the School</h2>
            </div>
            <a href="news.php" class="au-link-arrow">View all news <i class="bi bi-arrow-right"></i></a>
        </div>

        <?php if (!$featured_post): ?>
            <div class="au-empty">
                <i class="bi bi-newspaper"></i>
                <p class="mt-2 mb-0">No announcements have been published yet. Please check back soon.</p>
            </div>
        <?php else: ?>
            <div class="row g-4">
                <div class="<?= $posts ? 'col-lg-7' : 'col-12' ?>" data-aos="fade-up">
                    <a href="<?= au_article_url($featured_post) ?>" class="au-feature-story">
                        <img src="<?= au_post_image($featured_post) ?>" alt="">
                        <div class="au-feature-story-overlay">
                            <div><span class="au-badge <?= au_category_class($featured_post['category']) ?>"><?= au_e($featured_post['category']) ?></span></div>
                            <h3><?= au_e($featured_post['title']) ?></h3>
                            <p class="mb-2"><?= au_e(au_post_excerpt($featured_post, 180)) ?></p>
                            <span class="au-meta"><i class="bi bi-calendar3 me-1"></i> <?= au_post_date($featured_post) ?></span>
                        </div>
                    </a>
                </div>

                <?php if ($posts): ?>
                    <div class="col-lg-5" data-aos="fade-up" data-aos-delay="100">
                        <?php foreach ($posts as $post): ?>
                            <a href="<?= au_article_url($post) ?>" class="au-news-list-item">
                                <img src="<?= au_post_image($post) ?>" alt="" loading="lazy">
                                <div>
                                    <span class="au-meta"><?= au_e($post['category']) ?> · <?= au_post_date($post, 'M j, Y') ?></span>
                                    <h4><?= au_e($post['title']) ?></h4>
                                    <span class="au-meta d-none d-sm-block"><?= au_e(au_post_excerpt($post, 80)) ?></span>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<!-- Tracks and strands -->
<section class="au-section au-section-navy">
    <div class="container">
        <div class="au-section-head" data-aos="fade-up">
            <div>
                <span class="au-eyebrow">Academics</span>
                <h2 class="au-section-title mb-0">Tracks and strands</h2>
            </div>
            <a href="programs.php" class="au-link-arrow" style="color: var(--au-gold-light);">All strands <i class="bi bi-arrow-right"></i></a>
        </div>
        <div class="row g-4">
            <div class="col-md-6 col-lg-3" data-aos="fade-up">
                <div class="au-program">
                    <i class="bi bi-cpu"></i>
                    <span class="au-program-level">Academic Track</span>
                    <h3>STEM</h3>
                    <p>Science, Technology, Engineering and Mathematics for learners headed to science, engineering, health and IT courses.</p>
                    <a href="programs.php#stem" class="au-link-arrow">Strand details <i class="bi bi-arrow-right"></i></a>
                </div>
            </div>
            <div class="col-md-6 col-lg-3" data-aos="fade-up" data-aos-delay="100">
                <div class="au-program">
                    <i class="bi bi-graph-up-arrow"></i>
                    <span class="au-program-level">Academic Track</span>
                    <h3>ABM</h3>
                    <p>Accountancy, Business and Management for future entrepreneurs, accountants and business leaders.</p>
                    <a href="programs.php#abm" class="au-link-arrow">Strand details <i class="bi bi-arrow-right"></i></a>
                </div>
            </div>
            <div class="col-md-6 col-lg-3" data-aos="fade-up" data-aos-delay="200">
                <div class="au-program">
                    <i class="bi bi-chat-quote"></i>
                    <span class="au-program-level">Academic Track</span>
                    <h3>HUMSS</h3>
                    <p>Humanities and Social Sciences for learners drawn to education, law, communication and public service.</p>
                    <a href="programs.php#humss" class="au-link-arrow">Strand details <i class="bi bi-arrow-right"></i></a>
                </div>
            </div>
            <div class="col-md-6 col-lg-3" data-aos="fade-up" data-aos-delay="300">
                <div class="au-program">
                    <i class="bi bi-wrench-adjustable"></i>
                    <span class="au-program-level">TVL Track</span>
                    <h3>Technical-Vocational-Livelihood</h3>
                    <p>Hands-on specializations that lead to TESDA National Certificates and job-ready skills.</p>
                    <a href="programs.php#tvl" class="au-link-arrow">Strand details <i class="bi bi-arrow-right"></i></a>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Gallery preview -->
<section class="au-section">
    <div class="container">
        <div class="au-section-head" data-aos="fade-up">
            <div>
                <span class="au-eyebrow">Campus Life</span>
                <h2 class="au-section-title mb-0">Moments at DPLMHS</h2>
            </div>
            <a href="gallery.php" class="au-link-arrow">Open the gallery <i class="bi bi-arrow-right"></i></a>
        </div>
        <div class="au-gallery-grid au-gallery-preview" data-aos="fade-up">
            <?php foreach ($gallery_preview as $i => $image): ?>
                <a href="gallery.php" class="au-gallery-item<?= $i === 0 ? ' is-wide is-tall' : '' ?>">
                    <img src="<?= $image['src'] ?>" alt="<?= au_e($image['title']) ?>" loading="lazy">
                    <span class="au-gallery-caption"><small><?= au_e($image['album']) ?></small><?= au_e($image['title']) ?></span>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Testimonials -->
<section class="au-section au-section-navy">
    <div class="container" data-aos="fade-up">
        <div id="testimonialCarousel" class="carousel slide" data-bs-ride="carousel" data-bs-interval="7000">
            <div class="carousel-inner">
                <div class="carousel-item active">
                    <figure class="au-testimonial">
                        <i class="bi bi-quote"></i>
                        <blockquote>The STEM strand prepared me for my college entrance exams — and my teachers taught me how to keep going when things got hard.</blockquote>
                        <cite>Alumna<span>STEM Strand</span></cite>
                    </figure>
                </div>
                <div class="carousel-item">
                    <figure class="au-testimonial">
                        <i class="bi bi-quote"></i>
                        <blockquote>Work immersion showed me what a real workplace is like. I found my passion here, and teachers who believed in it.</blockquote>
                        <cite>Grade 12 Learner<span>TVL Track</span></cite>
                    </figure>
                </div>
                <div class="carousel-item">
                    <figure class="au-testimonial">
                        <i class="bi bi-quote"></i>
                        <blockquote>More than lessons, I learned to lead and to serve. That is what DPLMHS gave me.</blockquote>
                        <cite>Alumnus<span>HUMSS Strand</span></cite>
                    </figure>
                </div>
            </div>
            <div class="carousel-indicators">
                <button type="button" data-bs-target="#testimonialCarousel" data-bs-slide-to="0" class="active" aria-current="true" aria-label="Testimonial 1"></button>
                <button type="button" data-bs-target="#testimonialCarousel" data-bs-slide-to="1" aria-label="Testimonial 2"></button>
                <button type="button" data-bs-target="#testimonialCarousel" data-bs-slide-to="2" aria-label="Testimonial 3"></button>
            </div>
        </div>
    </div>
</section>

<!-- Newsletter CTA -->
<section class="au-cta">
    <div class="container">
        <div class="row align-items-center g-4">
            <div class="col-lg-6" data-aos="fade-right">
                <h2 class="mb-2">Stay connected with DPLMHS</h2>
                <p class="mb-0">Receive news on enrollment, events and opportunities from the school.</p>
            </div>
            <div class="col-lg-6" data-aos="fade-left">
                <form class="d-flex flex-column flex-sm-row gap-2" onsubmit="event.preventDefault(); this.innerHTML = '<p class=&quot;mb-0 fw-semibold&quot;><i class=&quot;bi bi-check-circle-fill me-2&quot;></i>Thank you for subscribing!</p>';">
                    <label for="newsletterEmail" class="visually-hidden">Email address</label>
                    <input type="email" id="newsletterEmail" class="form-control" placeholder="Your email address" required>
                    <button class="btn btn-au-gold text-nowrap">Subscribe</button>
                </form>
            </div>
        </div>
    </div>
</section>

<!-- Contact -->
<section class="au-section au-section-paper" id="contact">
    <div class="container">
        <div class="row g-5">
            <div class="col-lg-5" data-aos="fade-right">
                <span class="au-eyebrow">Visit Us</span>
                <h2 class="au-section-title">Office of the Principal</h2>
                <hr class="au-rule">
                <div class="au-contact-item">
                    <i class="bi bi-building"></i>
                    <div><strong>Stand-Alone Senior High School</strong>Don Pablo Lorenzo Memorial High School, Zamboanga City, Philippines</div>
                </div>
                <div class="au-contact-item">
                    <i class="bi bi-envelope"></i>
                    <div><strong>Email</strong><a href="mailto:info@dplmhs-shs.edu.ph">info@dplmhs-shs.edu.ph</a></div>
                </div>
                <div class="au-contact-item">
                    <i class="bi bi-telephone"></i>
                    <div><strong>Phone</strong>(062) 000-0000</div>
                </div>
                <div class="au-contact-item">
                    <i class="bi bi-clock"></i>
                    <div><strong>Office Hours</strong>Monday – Friday, 8:00 AM – 5:00 PM</div>
                </div>
            </div>
            <div class="col-lg-7" data-aos="fade-left">
                <iframe class="au-map" title="Map of Don Pablo Lorenzo Memorial High School"
                    src="https://www.google.com/maps?q=Don+Pablo+Lorenzo+Memorial+High+School,+Zamboanga+City&output=embed"
                    allowfullscreen loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
            </div>
        </div>
    </div>
</section>

<?php include __DIR__ . '/includes/site-footer.php'; ?>
