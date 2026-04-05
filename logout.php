<?php 
session_start();  // Always start the session before working with it

// Unset all session variables
session_unset();

// Destroy the session
session_destroy();

// Redirect to the index page
header('Location: index.php');
exit();  // Ensure no further code is executed after the redirect
?>
