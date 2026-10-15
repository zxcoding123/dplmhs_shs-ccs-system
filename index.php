<?php
session_start();
include('login/processes/conn.php');
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
                <span class="au-eyebrow">College of Computer Studies</span>
                <h1>Shaping the <em>future</em> of computing, rooted in purpose.</h1>
                <p>For generations, Aurelius University has formed leaders of competence and conscience. Our College of Computer Studies carries that tradition into artificial intelligence, software, data and networks.</p>
                <div class="d-flex flex-wrap gap-3 justify-content-center justify-content-lg-start">
                    <a href="programs.php" class="btn btn-au-gold btn-lg">Explore Programs</a>
                    <a href="news.php" class="btn btn-au-outline btn-lg">Latest News</a>
                </div>
                <p class="au-hero-motto">“Scientia et Virtus” — Knowledge and virtue in the service of others.</p>
            </div>
            <div class="col-lg-5 text-center d-none d-lg-block" data-aos="zoom-in" data-aos-delay="150">
                <img src="external/img/AU_Logo.png" class="au-hero-crest" alt="Aurelius University seal">
            </div>
        </div>
    </div>
</section>

<!-- Stats ribbon -->
<section class="au-stats" aria-label="The college at a glance">
    <div class="container">
        <div class="row g-0">
            <div class="col-6 col-md-3 au-stat"><span class="au-stat-number">4</span><span class="au-stat-label">Degree Programs</span></div>
            <div class="col-6 col-md-3 au-stat"><span class="au-stat-number">1,200+</span><span class="au-stat-label">Students</span></div>
            <div class="col-6 col-md-3 au-stat"><span class="au-stat-number">60+</span><span class="au-stat-label">Faculty &amp; Staff</span></div>
            <div class="col-6 col-md-3 au-stat"><span class="au-stat-number">10,000+</span><span class="au-stat-label">Alumni</span></div>
        </div>
    </div>
</section>

<!-- Welcome from the Dean -->
<section class="au-section">
    <div class="container">
        <div class="row align-items-center g-5">
            <div class="col-lg-6" data-aos="fade-right">
                <div class="au-split-img">
                    <img src="external/img/4-Pillars-III-COLOR-MODIFIED-2048x1365-1.jpg" alt="Students and faculty of Aurelius University">
                </div>
            </div>
            <div class="col-lg-6" data-aos="fade-left">
                <span class="au-eyebrow">Welcome from the Dean</span>
                <h2 class="au-section-title">An education that forms the whole person</h2>
                <hr class="au-rule">
                <p>At the College of Computer Studies, technical mastery is only half of the story. We prepare students to design systems that matter — and to ask who those systems serve.</p>
                <p class="au-quote">“We do not simply train programmers. We form professionals who use technology with integrity, creativity and care for the community.”</p>
                <p class="au-meta mb-4">Office of the Dean, College of Computer Studies</p>
                <a href="about_us.php" class="au-link-arrow">Learn more about the College <i class="bi bi-arrow-right"></i></a>
            </div>
        </div>
    </div>
</section>

<!-- Four pillars -->
<section class="au-section au-section-paper">
    <div class="container">
        <div class="text-center mb-5" data-aos="fade-up">
            <span class="au-eyebrow">Why Aurelius</span>
            <h2 class="au-section-title">The four pillars of an Aurelian education</h2>
            <hr class="au-rule">
        </div>
        <div class="row g-4">
            <div class="col-md-6 col-lg-3" data-aos="fade-up">
                <div class="au-pillar">
                    <span class="au-pillar-icon"><i class="bi bi-mortarboard-fill"></i></span>
                    <h3>Academic Excellence</h3>
                    <p>The only autonomous university in Bicol, offering programs from pre-school through graduate and law school.</p>
                </div>
            </div>
            <div class="col-md-6 col-lg-3" data-aos="fade-up" data-aos-delay="100">
                <div class="au-pillar">
                    <span class="au-pillar-icon"><i class="bi bi-people-fill"></i></span>
                    <h3>Social Involvement</h3>
                    <p>Scholarships and outreach programs that give priority to the marginalized and serve the wider community.</p>
                </div>
            </div>
            <div class="col-md-6 col-lg-3" data-aos="fade-up" data-aos-delay="200">
                <div class="au-pillar">
                    <span class="au-pillar-icon"><i class="bi bi-heart-fill"></i></span>
                    <h3>Values Formation</h3>
                    <p>Retreats and formation programs for personal and spiritual growth, open to students and alumni alike.</p>
                </div>
            </div>
            <div class="col-md-6 col-lg-3" data-aos="fade-up" data-aos-delay="300">
                <div class="au-pillar">
                    <span class="au-pillar-icon"><i class="bi bi-lightbulb-fill"></i></span>
                    <h3>Research</h3>
                    <p>A prime venue for research in Bicol, backed by facilities, partnerships and training programs.</p>
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
                <h2 class="au-section-title mb-0">From the College</h2>
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

<!-- Academic programs -->
<section class="au-section au-section-navy">
    <div class="container">
        <div class="au-section-head" data-aos="fade-up">
            <div>
                <span class="au-eyebrow">Academics</span>
                <h2 class="au-section-title mb-0">Programs of study</h2>
            </div>
            <a href="programs.php" class="au-link-arrow" style="color: var(--au-gold-light);">All programs <i class="bi bi-arrow-right"></i></a>
        </div>
        <div class="row g-4">
            <div class="col-md-6 col-lg-3" data-aos="fade-up">
                <div class="au-program">
                    <i class="bi bi-cpu"></i>
                    <span class="au-program-level">Undergraduate</span>
                    <h3>BS Computer Science</h3>
                    <p>Algorithms, software engineering, artificial intelligence and data science on a rigorous theoretical foundation.</p>
                    <a href="programs.php#bscs" class="au-link-arrow">Program details <i class="bi bi-arrow-right"></i></a>
                </div>
            </div>
            <div class="col-md-6 col-lg-3" data-aos="fade-up" data-aos-delay="100">
                <div class="au-program">
                    <i class="bi bi-hdd-network"></i>
                    <span class="au-program-level">Undergraduate</span>
                    <h3>BS Information Technology</h3>
                    <p>Web and mobile systems, networking, cybersecurity and IT infrastructure, with industry internships.</p>
                    <a href="programs.php#bsit" class="au-link-arrow">Program details <i class="bi bi-arrow-right"></i></a>
                </div>
            </div>
            <div class="col-md-6 col-lg-3" data-aos="fade-up" data-aos-delay="200">
                <div class="au-program">
                    <i class="bi bi-mortarboard"></i>
                    <span class="au-program-level">Graduate</span>
                    <h3>MS Computer Science</h3>
                    <p>A research-focused degree in machine learning, systems or theory, with evening and weekend classes.</p>
                    <a href="programs.php#mscs" class="au-link-arrow">Program details <i class="bi bi-arrow-right"></i></a>
                </div>
            </div>
            <div class="col-md-6 col-lg-3" data-aos="fade-up" data-aos-delay="300">
                <div class="au-program">
                    <i class="bi bi-bar-chart-line"></i>
                    <span class="au-program-level">Professional</span>
                    <h3>Certificate in Data Analytics</h3>
                    <p>A six-month program in Python, SQL and data storytelling designed for working professionals.</p>
                    <a href="programs.php#data-analytics" class="au-link-arrow">Program details <i class="bi bi-arrow-right"></i></a>
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
                <h2 class="au-section-title mb-0">Moments at the College</h2>
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
                        <blockquote>CCS helped me build the skills I now use every day in the tech industry — and the values that guide how I use them.</blockquote>
                        <cite>Althea<span>BS Computer Science, Class of 2022</span></cite>
                    </figure>
                </div>
                <div class="carousel-item">
                    <figure class="au-testimonial">
                        <i class="bi bi-quote"></i>
                        <blockquote>The professors and laboratories are world-class. I found my passion here, and mentors who believed in it.</blockquote>
                        <cite>Miguel<span>BS Information Technology Student</span></cite>
                    </figure>
                </div>
                <div class="carousel-item">
                    <figure class="au-testimonial">
                        <i class="bi bi-quote"></i>
                        <blockquote>More than technology, I learned to lead and to serve. That is what an Aurelian education gave me.</blockquote>
                        <cite>Rachel<span>BS Computer Science Alumna</span></cite>
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
                <h2 class="mb-2">Stay connected with CCS</h2>
                <p class="mb-0">Receive news on programs, events, research and opportunities from the College.</p>
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
                <h2 class="au-section-title">Office of the Dean</h2>
                <hr class="au-rule">
                <div class="au-contact-item">
                    <i class="bi bi-building"></i>
                    <div><strong>College of Computer Studies</strong>Aurelius University, Bagumbayan Sur, Naga City, Philippines</div>
                </div>
                <div class="au-contact-item">
                    <i class="bi bi-envelope"></i>
                    <div><strong>Email</strong><a href="mailto:ccs@au.edu.ph">ccs@au.edu.ph</a></div>
                </div>
                <div class="au-contact-item">
                    <i class="bi bi-telephone"></i>
                    <div><strong>Phone</strong>(054) 472-2368 local 4201</div>
                </div>
                <div class="au-contact-item">
                    <i class="bi bi-clock"></i>
                    <div><strong>Office Hours</strong>Monday – Friday, 8:00 AM – 5:00 PM</div>
                </div>
            </div>
            <div class="col-lg-7" data-aos="fade-left">
                <iframe class="au-map" title="Map of Aurelius University"
                    src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3931.3575875530817!2d123.19364561427916!3d13.621204787658985!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x33a18d5b68a5fc3f%3A0x18ffb84d435af41!2sAteneo%20de%20Naga%20University!5e0!3m2!1sen!2sph!4v1620372951307!5m2!1sen!2sph"
                    allowfullscreen loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
            </div>
        </div>
    </div>
</section>

<?php include __DIR__ . '/includes/site-footer.php'; ?>
