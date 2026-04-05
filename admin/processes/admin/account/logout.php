<?php
session_start();

$_SESSION = array();

session_destroy();

session_start();
$_SESSION['STATUS'] = "LOG_OUT_SUCCESFUL";
header("Location: ../../../../login/index.php");
exit();
?>
