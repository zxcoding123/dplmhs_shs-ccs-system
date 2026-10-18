<?php
/**
 * Favicon Links Include
 * Add this to the <head> section of any page:
 * <?php include(__DIR__ . '/../includes/favicon-links.php'); ?>
 */

// Determine the base path relative to favicon location
$base_path = isset($favicon_base_path) ? $favicon_base_path : '../';
?>
<!-- Favicon (DPLMHS logo) -->
<link rel="icon" href="<?php echo $base_path; ?>favicon.ico" sizes="any">
<link rel="icon" type="image/png" sizes="32x32" href="<?php echo $base_path; ?>favicon-32x32.png">
<link rel="icon" type="image/png" sizes="16x16" href="<?php echo $base_path; ?>favicon-16x16.png">
<link rel="apple-touch-icon" href="<?php echo $base_path; ?>apple-touch-icon.png">
<link rel="manifest" href="<?php echo $base_path; ?>site.webmanifest">
