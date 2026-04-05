<?php



// $servername = "151.106.112.223";
// $username = "remote_user";
// $password = "your_remote_password";
// $dbname = "csms_system";

?>

<?php

$servername = 'localhost';  // Your VPS’s public IP
$dbname = 'csms_system';    // Your database name
$username = 'root';  // Remote user we created
$password = ''; // Password for remote_user

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?>

