<?php
session_start();
require_once '../app/auth_bootstrap.php';

$pdo = Database::getConnection();

$stmt = $pdo->prepare("
    UPDATE classes_meetings
    SET status = 'Finished'
    WHERE STR_TO_DATE(CONCAT(CURDATE(), ' ', end_time), '%Y-%m-%d %h:%i %p') < NOW()
");
$stmt->execute();
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sign In | Don Pablo Lorenzo Memorial High School – Stand-Alone Senior High School</title>
    <meta name="theme-color" content="#061E4E">
    <?php
    $favicon_base_path = '../';
    include(__DIR__ . '/../includes/favicon-links.php');
    ?>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,600;0,700;1,600&family=Source+Sans+3:wght@400;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="<?php require_once __DIR__ . '/../includes/asset-version.php'; echo csms_asset('../external/css/site.css', __DIR__); ?>" rel="stylesheet">
    <link href="<?php require_once __DIR__ . '/../includes/asset-version.php'; echo csms_asset('../external/css/auth.css', __DIR__); ?>" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body class="au-site au-auth">
    <div class="au-auth-shell">
        <?php include __DIR__ . '/../includes/auth-brand.php'; ?>

        <main class="au-auth-main">
            <nav class="au-auth-topnav" aria-label="Page">
                <a class="au-brand au-auth-mobile-brand" href="../index.php">
                    <img src="../external/img/dp_logo.avif" alt="Don Pablo Lorenzo Memorial High School seal">
                    <span class="au-brand-text">
                        <span class="au-brand-university">Don Pablo Lorenzo Memorial High School</span>
                        <span class="au-brand-college">Stand-Alone Senior High School</span>
                    </span>
                </a>
                <a href="../index.php"><i class="bi bi-arrow-left me-1"></i> Back to website</a>
                <span class="au-auth-hide-mobile text-muted">New here? <a href="#" data-bs-toggle="modal" data-bs-target="#accountInfoModal">Create an account</a></span>
            </nav>

            <div class="au-auth-center">
                <div class="au-auth-card">
                    <span class="au-eyebrow">Student &amp; Faculty Portal</span>
                    <h2>Sign in to your account</h2>
                    <p class="au-lead">Use the email address registered with the school.</p>

                    <div class="au-auth-panel">
                        <form action="processes/login.php" method="POST" id="loginForm" novalidate>
                            <div class="mb-3">
                                <label for="email" class="form-label">Email address</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                                    <input type="email" class="form-control" id="email" name="email"
                                        placeholder="you@dplmhs-shs.edu.ph" autocomplete="username" required autofocus>
                                </div>
                                <div class="invalid-feedback d-block d-none" id="emailError">Please enter a valid email address.</div>
                            </div>

                            <div class="mb-2">
                                <div class="d-flex justify-content-between align-items-baseline">
                                    <label for="password" class="form-label">Password</label>
                                    <a href="#" class="small fw-semibold" data-bs-toggle="modal" data-bs-target="#resetPasswordModal">Forgot password?</a>
                                </div>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-lock"></i></span>
                                    <input type="password" class="form-control" id="password" name="password"
                                        placeholder="Enter your password" autocomplete="current-password" required>
                                    <button type="button" class="btn btn-toggle-pw" data-toggle-password="password" aria-label="Show password">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                </div>
                                <div class="invalid-feedback d-block d-none" id="passwordError">Please enter your password.</div>
                                <small class="au-field-hint d-none" id="capsWarning"><i class="bi bi-exclamation-triangle"></i> Caps Lock is on.</small>
                            </div>

                            <button type="submit" class="btn btn-au-navy btn-submit mt-4" id="loginSubmit">
                                <i class="bi bi-box-arrow-in-right me-1"></i> Sign In
                            </button>
                        </form>

                        <div class="au-auth-divider">New to the portal?</div>
                        <button type="button" class="btn btn-outline-secondary w-100 fw-semibold" data-bs-toggle="modal" data-bs-target="#accountInfoModal">Create an account</button>
                    </div>

                    <div class="au-auth-note mt-4">
                        <i class="bi bi-info-circle"></i>
                        <span>Student accounts are created by the school registrar; faculty accounts by the school's admin. Having trouble? Visit the <a href="../help.php">Help Center</a>.</span>
                    </div>
                </div>
            </div>

            <p class="au-auth-legal mb-0">&copy; <?= date('Y') ?> Don Pablo Lorenzo Memorial High School – Stand-Alone Senior High School<br>System developed by <a href="#" class="au-dev-link" data-bs-toggle="modal" data-bs-target="#developerModal">Ahmad Aquino</a></p>
        </main>
    </div>

    <?php include __DIR__ . '/../includes/developer-modal.php'; ?>

    <!-- Account creation notice: accounts are issued by an admin, not self-registered -->
    <div class="modal fade" id="accountInfoModal" tabindex="-1" aria-labelledby="accountInfoModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="accountInfoModalLabel"><i class="bi bi-person-lock me-2"></i>Account creation</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Accounts cannot be created online. <strong>All accounts are created and accredited by the respective admin or sub-admin</strong> of Don Pablo Lorenzo Memorial High School – Stand-Alone Senior High School.</p>
                    <ul class="mb-3">
                        <li><strong>Students</strong> — ask the school registrar or your class adviser for your account.</li>
                        <li><strong>Teachers and staff</strong> — ask the school admin to create your account.</li>
                    </ul>
                    <p class="text-muted small mb-0">Once your account is created, you will receive your login details at your registered email address.</p>
                </div>
                <div class="modal-footer">
                    <a href="../contact_us.php" class="btn btn-outline-secondary">Contact the school</a>
                    <button type="button" class="btn btn-au-navy" data-bs-dismiss="modal">Got it</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Reset password -->
    <div class="modal fade" id="resetPasswordModal" tabindex="-1" aria-labelledby="resetPasswordModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form id="resetPasswordForm" action="processes/reset.php" method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title" id="resetPasswordModalLabel">Reset your password</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p class="text-muted">Enter the email address on your account and we will send you a link to set a new password.</p>
                        <label for="resetEmail" class="form-label">Email address</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                            <input type="email" class="form-control" id="resetEmail" name="email" placeholder="you@dplmhs-shs.edu.ph" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-au-navy" id="submitReset">Send reset link</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <script>
        // Links to the old sign-up page land here with ?create=1
        if (new URLSearchParams(location.search).has('create')) {
            bootstrap.Modal.getOrCreateInstance(document.getElementById('accountInfoModal')).show();
        }

        // Show / hide password
        document.querySelectorAll('[data-toggle-password]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                const field = document.getElementById(btn.dataset.togglePassword);
                const show = field.type === 'password';
                field.type = show ? 'text' : 'password';
                btn.querySelector('i').className = show ? 'bi bi-eye-slash' : 'bi bi-eye';
                btn.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
            });
        });

        // Caps Lock warning
        document.getElementById('password').addEventListener('keyup', function (e) {
            document.getElementById('capsWarning').classList.toggle('d-none', !(e.getModifierState && e.getModifierState('CapsLock')));
        });

        // Client-side checks + loading state
        document.getElementById('loginForm').addEventListener('submit', function (e) {
            const email = document.getElementById('email');
            const password = document.getElementById('password');
            const emailOk = email.checkValidity();
            const passwordOk = password.value.length > 0;

            email.classList.toggle('is-invalid', !emailOk);
            password.classList.toggle('is-invalid', !passwordOk);
            document.getElementById('emailError').classList.toggle('d-none', emailOk);
            document.getElementById('passwordError').classList.toggle('d-none', passwordOk);

            if (!emailOk || !passwordOk) {
                e.preventDefault();
                (emailOk ? password : email).focus();
                return;
            }
            const btn = document.getElementById('loginSubmit');
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Signing in…';
        });

        // Reset password: show progress while the email is sent
        document.getElementById('resetPasswordForm').addEventListener('submit', function (e) {
            if (!this.checkValidity()) return;
            e.preventDefault();
            const form = this;
            Swal.fire({
                title: 'Sending reset link…',
                text: 'Please wait while we process your request.',
                allowOutsideClick: false,
                showConfirmButton: false,
                didOpen: function () {
                    Swal.showLoading();
                    form.submit();
                }
            });
        });
    </script>

    <?php include('processes/alerts.php'); ?>
</body>

</html>
