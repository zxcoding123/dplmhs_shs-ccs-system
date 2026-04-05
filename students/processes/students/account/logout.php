<?php
session_start();
session_destroy();
session_start();
$_SESSION['STATUS'] = "SUCCESSFUL_LOG_OUT";
header("Location: student_login_page.php");
exit();
?>
