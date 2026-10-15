<?php
/**
 * Public site header: <head>, utility bar and main navigation.
 *
 * Set before including:
 *   $page_title       – shown before the institution name in <title>
 *   $page_description – optional meta description
 *   $active_page      – home | about | programs | news | gallery | contact | help
 */
require_once __DIR__ . '/site-helpers.php';

$page_title = $page_title ?? '';
$page_description = $page_description ?? 'The College of Computer Studies of Aurelius University forms competent and compassionate computing professionals through excellence in teaching, research and service.';
$active_page = $active_page ?? '';
$favicon_base_path = './';

$au_nav = [
    'home' => ['Home', 'index.php'],
    'about' => ['About', 'about_us.php'],
    'programs' => ['Academics', 'programs.php'],
    'news' => ['News &amp; Events', 'news.php'],
    'gallery' => ['Gallery', 'gallery.php'],
    'contact' => ['Contact', 'contact_us.php'],
];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title !== '' ? au_e($page_title) . ' | ' : '' ?>College of Computer Studies – Aurelius University</title>
    <meta name="description" content="<?= au_e($page_description) ?>">
    <meta name="theme-color" content="#061E4E">
    <?php include __DIR__ . '/favicon-links.php'; ?>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,600;0,700;1,600&family=Source+Sans+3:wght@400;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <link href="external/css/site.css" rel="stylesheet">
</head>

<body class="au-site">
    <a class="skip-link" href="#main">Skip to main content</a>

    <div class="au-topbar">
        <div class="container">
            <ul class="au-topbar-info">
                <li><i class="bi bi-telephone me-1"></i> (054) 472-2368 loc. 4201</li>
                <li><i class="bi bi-envelope me-1"></i> <a href="mailto:ccs@au.edu.ph">ccs@au.edu.ph</a></li>
            </ul>
            <ul class="au-topbar-links">
                <li><a href="login/index.php">Student Portal</a></li>
                <li><a href="login/index.php">Faculty Portal</a></li>
                <li><a href="help.php">Help Center</a></li>
            </ul>
        </div>
    </div>

    <header class="au-header">
        <nav class="navbar navbar-expand-lg" aria-label="Main navigation">
            <div class="container">
                <a class="au-brand" href="index.php">
                    <img src="external/img/AU_Logo.png" alt="Aurelius University seal">
                    <span class="au-brand-text">
                        <span class="au-brand-university">Aurelius University</span>
                        <span class="au-brand-college">College of Computer Studies</span>
                    </span>
                </a>

                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#auMainNav"
                    aria-controls="auMainNav" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>

                <div class="collapse navbar-collapse" id="auMainNav">
                    <ul class="navbar-nav ms-auto align-items-lg-center">
                        <?php foreach ($au_nav as $key => [$label, $href]): ?>
                            <li class="nav-item">
                                <a class="nav-link<?= $active_page === $key ? ' active' : '' ?>" href="<?= $href ?>"
                                    <?= $active_page === $key ? 'aria-current="page"' : '' ?>><?= $label ?></a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                    <a href="login/index.php" class="btn btn-au-gold ms-lg-3">
                        <i class="bi bi-box-arrow-in-right me-1"></i> Login
                    </a>
                </div>
            </div>
        </nav>
    </header>

    <main id="main">
