<?php
session_start(); // Start the session

// Check if the user is logged in
if (isset($_SESSION['user_id']) || isset($_SESSION['admin_id'])) {
    // Destroy all session data
    $_SESSION = array();  // Clear session variables

    // If you want to destroy the session completely (including session cookie)
    session_destroy();  // Destroy the session

    // Optional: Redirect to a login page or homepage
    header("Location: ../admin_login_page.php");  
    exit();
} else {
    // If the user is not logged in, redirect them to the login page
    header("Location: ../admin_login_page.php"); 
    exit();
}
?>
