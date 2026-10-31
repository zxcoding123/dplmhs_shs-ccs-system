<?php
/**
 * One-time "login successful" message shown on the dashboard right after sign-in.
 * AuthService sets STATUS to <ROLE>_LOGIN_SUCCESSFUL; the older misspelled
 * codes from the legacy login scripts are accepted too.
 * Requires SweetAlert2 to be loaded on the page.
 */

$loginSuccessRoles = [
    'ADMIN_LOGIN_SUCCESSFUL'   => 'admin',
    'ADMIN_LOGIN_SUCCESFUL'    => 'admin',
    'STAFF_LOGIN_SUCCESSFUL'   => 'teacher',
    'TEACHER_LOGIN_SUCCESSFUL' => 'teacher',
    'TEACHER_LOGIN_SUCCESFUL'  => 'teacher',
    'STUDENT_LOGIN_SUCCESSFUL' => 'student',
    'STUDENT_LOGIN_SUCCESFUL'  => 'student',
    'REGISTRAR_LOGIN_SUCCESSFUL' => 'registrar',
];

$loginStatus = $_SESSION['STATUS'] ?? '';

if (isset($loginSuccessRoles[$loginStatus])) {
    $loginRole = $loginSuccessRoles[$loginStatus];
    $loginName = trim((string) ($_SESSION['name'] ?? ''));
    $loginTitle = $loginName !== '' ? 'Welcome, ' . $loginName . '!' : 'Login successful!';
    $loginText = 'You have successfully logged in as ' . ($loginRole === 'admin' ? 'an admin' : 'a ' . $loginRole) . '.';

    $loginFlags = JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT;
    ?>
    <script>
        Swal.fire({
            icon: 'success',
            title: <?= json_encode($loginTitle, $loginFlags) ?>,
            text: <?= json_encode($loginText, $loginFlags) ?>,
            timer: 2500,
            timerProgressBar: true,
            showConfirmButton: false
        });
    </script>
    <?php
    unset($_SESSION['STATUS']);
}

unset($loginSuccessRoles, $loginStatus, $loginRole, $loginName, $loginTitle, $loginText, $loginFlags);
