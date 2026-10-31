<?php
/**
 * Public site footer: closes <main>, renders footer, loads scripts.
 * Pages may set $page_scripts (raw HTML) to append page-specific scripts.
 */
?>
    </main>

    <footer class="au-footer">
        <div class="au-footer-main">
            <div class="container">
                <div class="row g-5">
                    <div class="col-lg-4">
                        <a class="au-footer-brand" href="index.php">
                            <img src="external/img/AU_CCS_Logo.png" alt="Stand-Alone Senior High School seal">
                            <span>Don Pablo Lorenzo Memorial High School<small>Stand-Alone Senior High School</small></span>
                        </a>
                        <p>Forming competent, responsible and future-ready senior high school learners prepared for college, employment and life.</p>
                        <p class="au-footer-motto mb-0">Maka-Diyos · Maka-tao · Makakalikasan · Makabansa</p>
                        <div class="au-social">
                            <a href="#" aria-label="Facebook"><i class="bi bi-facebook"></i></a>
                            <a href="#" aria-label="X (Twitter)"><i class="bi bi-twitter-x"></i></a>
                            <a href="#" aria-label="Instagram"><i class="bi bi-instagram"></i></a>
                            <a href="#" aria-label="LinkedIn"><i class="bi bi-linkedin"></i></a>
                            <a href="#" aria-label="YouTube"><i class="bi bi-youtube"></i></a>
                        </div>
                    </div>

                    <div class="col-6 col-md-4 col-lg-2">
                        <h5>Explore</h5>
                        <ul>
                            <li><a href="about_us.php">About the School</a></li>
                            <li><a href="programs.php">Tracks &amp; Strands</a></li>
                            <li><a href="news.php">News &amp; Events</a></li>
                            <li><a href="gallery.php">Photo Gallery</a></li>
                            <li><a href="contact_us.php">Contact Us</a></li>
                        </ul>
                    </div>

                    <div class="col-6 col-md-4 col-lg-2">
                        <h5>Resources</h5>
                        <ul>
                            <li><a href="login/index.php">Student Portal</a></li>
                            <li><a href="login/index.php">Faculty Portal</a></li>
                            <li><a href="login/index.php?create=1">Create an Account</a></li>
                            <li><a href="calendar.php">School Calendar</a></li>
                            <li><a href="help.php">Help Center</a></li>
                        </ul>
                    </div>

                    <div class="col-md-4 col-lg-4">
                        <h5>Get in Touch</h5>
                        <ul class="au-footer-contact">
                            <li><i class="bi bi-geo-alt-fill"></i><span>Office of the Principal, Stand-Alone Senior High School<br>Don Pablo Lorenzo Memorial High School<br>Zamboanga City, Philippines</span></li>
                            <li><i class="bi bi-telephone-fill"></i><span>(062) 000-0000</span></li>
                            <li><i class="bi bi-envelope-fill"></i><a href="mailto:info@dplmhs-shs.edu.ph">info@dplmhs-shs.edu.ph</a></li>
                            <li><i class="bi bi-clock-fill"></i><span>Mon – Fri, 8:00 AM – 5:00 PM</span></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <div class="au-footer-bottom">
            <div class="container">
                <span>&copy; <?= date('Y') ?> Don Pablo Lorenzo Memorial High School – Stand-Alone Senior High School. All rights reserved. System developed by <a href="#" class="au-dev-link" data-bs-toggle="modal" data-bs-target="#developerModal">Ahmad Aquino</a>.</span>
                <ul>
                    <li><a href="help.php">Privacy Notice</a></li>
                    <li><a href="help.php">Terms of Use</a></li>
                    <li><a href="contact_us.php">Accessibility</a></li>
                </ul>
            </div>
        </div>
    </footer>

    <?php include __DIR__ . '/developer-modal.php'; ?>

    <button type="button" class="au-back-to-top" id="auBackToTop" aria-label="Back to top">
        <i class="bi bi-arrow-up"></i>
    </button>

    <div class="au-snackbars" id="auSnackbars" role="status" aria-live="polite"></div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        AOS.init({ once: true, duration: 700, offset: 60 });

        (function () {
            var toTop = document.getElementById('auBackToTop');
            window.addEventListener('scroll', function () {
                toTop.classList.toggle('show', window.scrollY > 600);
            }, { passive: true });
            toTop.addEventListener('click', function () {
                window.scrollTo({ top: 0, behavior: 'smooth' });
            });
        })();

        /**
         * auSnackbar('Saved.', 'success') — type: success | danger | warning | info.
         * Pages can also set $page_snackbar = [type, message] before including this footer.
         */
        window.auSnackbar = (function () {
            var icons = { success: 'bi-check-circle-fill', danger: 'bi-x-circle-fill', warning: 'bi-exclamation-triangle-fill', info: 'bi-info-circle-fill' };
            var box = document.getElementById('auSnackbars');
            return function (message, type) {
                type = icons[type] ? type : 'info';
                var bar = document.createElement('div');
                bar.className = 'au-snackbar is-' + type;
                bar.innerHTML = '<i class="bi ' + icons[type] + '" aria-hidden="true"></i><span class="au-snackbar-text"></span>'
                    + '<button type="button" class="au-snackbar-close" aria-label="Dismiss"><i class="bi bi-x-lg"></i></button>';
                bar.querySelector('.au-snackbar-text').textContent = message;
                // Keep at most three on screen
                while (box.children.length >= 3) box.firstElementChild.remove();
                box.appendChild(bar);

                var timer;
                function close() {
                    clearTimeout(timer);
                    bar.classList.add('is-leaving');
                    setTimeout(function () { bar.remove(); }, 200);
                }
                function start() { timer = setTimeout(close, type === 'danger' || type === 'warning' ? 7000 : 4500); }
                bar.querySelector('.au-snackbar-close').addEventListener('click', close);
                // Pause while the reader is hovering or focused on it
                bar.addEventListener('mouseenter', function () { clearTimeout(timer); });
                bar.addEventListener('mouseleave', start);
                bar.addEventListener('focusin', function () { clearTimeout(timer); });
                bar.addEventListener('focusout', start);
                start();
            };
        })();
    </script>
    <?php if (!empty($page_snackbar)): ?>
        <script>auSnackbar(<?= json_encode((string) $page_snackbar[1], JSON_HEX_TAG) ?>, <?= json_encode((string) $page_snackbar[0]) ?>);</script>
    <?php endif; ?>
    <?= $page_scripts ?? '' ?>
</body>

</html>
