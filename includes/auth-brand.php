<?php
/**
 * Left-hand brand panel for the login and registration pages (pages live in /login).
 * Optional: $auth_heading (HTML), $auth_text, $auth_points (list of [icon, text]).
 */
$auth_heading = $auth_heading ?? 'Welcome to the <em>CCS</em> Student Management System';
$auth_text = $auth_text ?? 'One portal for classes, attendance, grades and announcements across the College of Computer Studies.';
$auth_points = $auth_points ?? [
    ['bi-journal-check', 'View your classes, lectures and activities'],
    ['bi-qr-code-scan', 'Record attendance with QR check-in'],
    ['bi-bar-chart-line', 'Track grades and academic standing'],
    ['bi-megaphone', 'Receive announcements from the Dean and faculty'],
];
?>
<aside class="au-auth-brand" aria-label="Aurelius University">
    <a class="au-brand" href="../index.php">
        <img src="../external/img/AU_Logo.png" alt="Aurelius University seal">
        <span class="au-brand-text">
            <span class="au-brand-university">Aurelius University</span>
            <span class="au-brand-college">College of Computer Studies</span>
        </span>
    </a>

    <div class="au-auth-brand-body">
        <img src="../external/img/AU_CCS_Logo.png" class="au-auth-crest" alt="College of Computer Studies seal">
        <h1><?= $auth_heading ?></h1>
        <p><?= htmlspecialchars($auth_text) ?></p>
        <ul class="au-auth-points">
            <?php foreach ($auth_points as [$icon, $text]): ?>
                <li><i class="bi <?= $icon ?>"></i><span><?= htmlspecialchars($text) ?></span></li>
            <?php endforeach; ?>
        </ul>
    </div>

    <div class="au-auth-brand-foot">
        <span class="au-auth-motto">“Scientia et Virtus”</span>
        <span><a href="../help.php">Help Center</a> · <a href="../contact_us.php">Contact</a></span>
    </div>
</aside>
