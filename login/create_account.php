<?php
session_start();

// Self-registration is closed: accounts are created by an admin or sub-admin.
// Send visitors to the login page, which explains this in a modal.
header('Location: index.php?create=1');
exit;

$auth_heading = 'Begin your journey at <em>DPLMHS</em>';
$auth_text = 'Create your student account to access your classes, attendance, grades and College announcements in one place.';
$auth_points = [
    ['bi-person-check', 'Register with your personal and academic details'],
    ['bi-envelope-check', 'Activate your account from the email we send you'],
    ['bi-box-arrow-in-right', 'Sign in and join your classes'],
];
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Create an Account | Don Pablo Lorenzo Memorial High School – Stand-Alone Senior High School</title>
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
    <link href="../external/css/site.css" rel="stylesheet">
    <link href="../external/css/auth.css" rel="stylesheet">
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
                <a href="index.php"><i class="bi bi-arrow-left me-1"></i> Back to sign in</a>
                <span class="au-auth-hide-mobile text-muted">Already registered? <a href="index.php">Sign in</a></span>
            </nav>

            <div class="au-auth-center">
                <div class="au-auth-card is-wide">
                    <span class="au-eyebrow">Student Registration</span>
                    <h2>Create your account</h2>
                    <p class="au-lead">Fill in the details below. We will email you a link to activate your account.</p>

                    <div class="au-auth-panel">
                        <form action="processes/register.php" method="POST" id="registrationForm" novalidate>
                            <input type="hidden" name="role" value="student">

                            <fieldset class="au-fieldset">
                                <legend class="au-fieldset-title"><span>1</span> Personal information</legend>
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label for="firstName" class="form-label">First name</label>
                                        <input type="text" class="form-control" id="firstName" name="firstName" autocomplete="given-name" required>
                                    </div>
                                    <div class="col-md-4">
                                        <label for="middleName" class="form-label">Middle name <span class="optional">(optional)</span></label>
                                        <input type="text" class="form-control" id="middleName" name="middleName" autocomplete="additional-name">
                                    </div>
                                    <div class="col-md-4">
                                        <label for="lastName" class="form-label">Last name</label>
                                        <input type="text" class="form-control" id="lastName" name="lastName" autocomplete="family-name" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="gender" class="form-label">Gender</label>
                                        <select class="form-select" id="gender" name="gender" required>
                                            <option value="" disabled selected>Select gender</option>
                                            <option value="Male">Male</option>
                                            <option value="Female">Female</option>
                                            <option value="Others">Others</option>
                                        </select>
                                    </div>
                                </div>
                            </fieldset>

                            <fieldset class="au-fieldset">
                                <legend class="au-fieldset-title"><span>2</span> Academic information</legend>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label for="student_id" class="form-label">Student ID number</label>
                                        <input type="text" class="form-control" id="student_id" name="student_id" inputmode="numeric" pattern="\d+" placeholder="e.g. 202312345" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="year_level" class="form-label">Year level</label>
                                        <select class="form-select" id="year_level" name="year_level" required>
                                            <option value="" disabled selected>Select year level</option>
                                            <option value="1st Year">1st Year</option>
                                            <option value="2nd Year">2nd Year</option>
                                            <option value="3rd Year">3rd Year</option>
                                            <option value="4th Year">4th Year</option>
                                        </select>
                                    </div>
                                    <div class="col-12">
                                        <label for="course" class="form-label">Program</label>
                                        <select class="form-select" id="course" name="course" required>
                                            <option value="" disabled selected>Select your program</option>
                                            <option value="BSCS">Bachelor of Science in Computer Science</option>
                                            <option value="BSIT">Bachelor of Science in Information Technology</option>
                                        </select>
                                    </div>
                                </div>
                            </fieldset>

                            <fieldset class="au-fieldset">
                                <legend class="au-fieldset-title"><span>3</span> Account security</legend>
                                <div class="row g-3">
                                    <div class="col-12">
                                        <label for="email" class="form-label">Email address</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                                            <input type="email" class="form-control" id="email" name="email" placeholder="you@dplmhs-shs.edu.ph" autocomplete="email" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="password" class="form-label">Password</label>
                                        <div class="input-group">
                                            <input type="password" class="form-control" id="password" name="password" minlength="8" autocomplete="new-password" required>
                                            <button type="button" class="btn btn-toggle-pw" data-toggle-password="password" aria-label="Show password"><i class="bi bi-eye"></i></button>
                                        </div>
                                        <div class="au-strength" id="strengthMeter" data-score="0" aria-hidden="true"><span></span><span></span><span></span><span></span></div>
                                        <small class="au-field-hint" id="strengthText">At least 8 characters, mixing letters, numbers and symbols.</small>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="confirm_password" class="form-label">Confirm password</label>
                                        <div class="input-group">
                                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" autocomplete="new-password" required>
                                            <button type="button" class="btn btn-toggle-pw" data-toggle-password="confirm_password" aria-label="Show password"><i class="bi bi-eye"></i></button>
                                        </div>
                                        <small class="au-field-hint" id="matchText" aria-live="polite"></small>
                                    </div>
                                </div>
                            </fieldset>

                            <div class="form-check mt-4">
                                <input class="form-check-input" type="checkbox" id="agree" required>
                                <label class="form-check-label" for="agree">
                                    I confirm the information above is accurate and agree to the College's <a href="../help.php" target="_blank">terms of use and privacy notice</a>.
                                </label>
                            </div>

                            <button type="submit" class="btn btn-au-navy btn-submit mt-4" id="registerSubmit">
                                <i class="bi bi-person-plus me-1"></i> Create Account
                            </button>
                        </form>
                    </div>

                    <div class="au-auth-note mt-4">
                        <i class="bi bi-info-circle"></i>
                        <span>Faculty and staff accounts are issued by the Office of the Principal. Already have an account? <a href="index.php">Sign in</a>.</span>
                    </div>
                </div>
            </div>

            <p class="au-auth-legal mb-0">&copy; <?= date('Y') ?> Don Pablo Lorenzo Memorial High School – Stand-Alone Senior High School</p>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <script>
        (function () {
            const form = document.getElementById('registrationForm');
            const password = document.getElementById('password');
            const confirm = document.getElementById('confirm_password');
            const meter = document.getElementById('strengthMeter');
            const strengthText = document.getElementById('strengthText');
            const matchText = document.getElementById('matchText');
            const labels = ['', 'Weak password', 'Fair password', 'Good password', 'Strong password'];

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

            function scorePassword(value) {
                if (!value) return 0;
                let score = 0;
                if (value.length >= 8) score++;
                if (/[a-z]/.test(value) && /[A-Z]/.test(value)) score++;
                if (/\d/.test(value)) score++;
                if (/[^A-Za-z0-9]/.test(value) || value.length >= 12) score++;
                return value.length < 8 ? Math.min(score, 1) : Math.max(score, 1);
            }

            function updateStrength() {
                const score = scorePassword(password.value);
                meter.dataset.score = score;
                strengthText.textContent = score ? labels[score] + (password.value.length < 8 ? ' — use at least 8 characters' : '') : 'At least 8 characters, mixing letters, numbers and symbols.';
                strengthText.className = 'au-field-hint' + (score >= 3 ? ' is-ok' : score ? ' is-bad' : '');
            }

            function updateMatch() {
                if (!confirm.value) {
                    matchText.textContent = '';
                    confirm.setCustomValidity('');
                    return;
                }
                const ok = confirm.value === password.value;
                matchText.innerHTML = ok ? '<i class="bi bi-check-circle"></i> Passwords match' : '<i class="bi bi-x-circle"></i> Passwords do not match';
                matchText.className = 'au-field-hint ' + (ok ? 'is-ok' : 'is-bad');
                confirm.setCustomValidity(ok ? '' : 'Passwords do not match');
            }

            password.addEventListener('input', function () { updateStrength(); updateMatch(); });
            confirm.addEventListener('input', updateMatch);

            form.querySelectorAll('.form-control, .form-select, .form-check-input').forEach(function (field) {
                field.addEventListener('input', function () {
                    if (field.classList.contains('is-invalid') && field.checkValidity()) field.classList.remove('is-invalid');
                });
                field.addEventListener('change', function () {
                    if (field.classList.contains('is-invalid') && field.checkValidity()) field.classList.remove('is-invalid');
                });
            });

            form.addEventListener('submit', function (e) {
                e.preventDefault();
                updateMatch();

                const invalid = Array.from(form.querySelectorAll('input, select')).filter(function (field) {
                    const bad = !field.checkValidity();
                    field.classList.toggle('is-invalid', bad);
                    return bad;
                });
                if (invalid.length) {
                    invalid[0].focus();
                    Swal.fire({ icon: 'warning', title: 'Please check the form', text: invalid[0].id === 'agree' ? 'Please confirm the declaration to continue.' : (invalid[0].validationMessage || 'Some fields need your attention.') });
                    return;
                }

                const btn = document.getElementById('registerSubmit');
                btn.disabled = true;
                btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Creating account…';

                fetch(form.action, { method: 'POST', body: new FormData(form) })
                    .then(function (response) { return response.json(); })
                    .then(function (data) {
                        if (data.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Account created!',
                                html: 'We sent an activation link to <strong>' + document.getElementById('email').value.replace(/[<>&"]/g, '') + '</strong>. Activate your account, then sign in.',
                                confirmButtonText: 'Go to sign in',
                                confirmButtonColor: '#061E4E'
                            }).then(function () {
                                window.location.href = 'index.php';
                            });
                        } else {
                            Swal.fire({ icon: 'error', title: 'Registration failed', text: data.message || 'Something went wrong.', confirmButtonColor: '#061E4E' });
                        }
                    })
                    .catch(function (error) {
                        console.error(error);
                        Swal.fire({ icon: 'error', title: 'Request failed', text: 'We could not reach the server. Please try again.', confirmButtonColor: '#061E4E' });
                    })
                    .finally(function () {
                        btn.disabled = false;
                        btn.innerHTML = '<i class="bi bi-person-plus me-1"></i> Create Account';
                    });
            });
        })();
    </script>

    <?php include('processes/alerts.php'); ?>
</body>

</html>
