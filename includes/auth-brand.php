<?php
/**
 * Left-hand brand panel for the login and registration pages (pages live in /login).
 * Optional: $auth_heading (HTML), $auth_text, $auth_points (list of [icon, text]).
 */
$auth_heading = $auth_heading ?? 'Welcome to the <em>DPLMHS</em> Student Management System';
$auth_text = $auth_text ?? 'One portal for classes, attendance, grades and announcements across the Stand-Alone Senior High School.';
$auth_points = $auth_points ?? [
    ['bi-journal-check', 'View your classes, lessons and activities'],
    ['bi-qr-code-scan', 'Record attendance with QR check-in'],
    ['bi-bar-chart-line', 'Track grades and academic standing'],
    ['bi-megaphone', 'Receive announcements from the Principal and teachers'],
];
?>
<aside class="au-auth-brand" aria-label="Don Pablo Lorenzo Memorial High School">
    <a class="au-brand" href="../index.php">
        <img src="../external/img/dp_logo.avif" alt="Don Pablo Lorenzo Memorial High School seal">
        <span class="au-brand-text">
            <span class="au-brand-university">Don Pablo Lorenzo Memorial High School</span>
            <span class="au-brand-college">Stand-Alone Senior High School</span>
        </span>
    </a>

    <div class="au-auth-brand-body">
        <img src="../external/img/AU_CCS_Logo.png" class="au-auth-crest" alt="Stand-Alone Senior High School seal">
        <h1><?= $auth_heading ?></h1>
        <p><?= htmlspecialchars($auth_text) ?></p>
        <ul class="au-auth-points">
            <?php foreach ($auth_points as [$icon, $text]): ?>
                <li><i class="bi <?= $icon ?>"></i><span><?= htmlspecialchars($text) ?></span></li>
            <?php endforeach; ?>
        </ul>
    </div>

    <div class="au-auth-brand-foot">
        <span class="au-auth-motto">Maka-Diyos · Maka-tao · Makakalikasan · Makabansa</span>
        <span><a href="../help.php">Help Center</a> · <a href="../contact_us.php">Contact</a></span>
    </div>
</aside>
