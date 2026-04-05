<?php

// $host = '151.106.112.223'; 
// $dbname = 'csms_system';   
// $username = 'remote_user';  
// $password = 'your_remote_password'; 

?>

<?php
date_default_timezone_set('Asia/Manila');

$host = 'localhost';  // Your VPS’s public IP
$dbname = 'csms_system';    // Your database name
$username = 'root';  // Remote user we created
$password = ''; // Password for remote_user

// $host = 'sql309.hstn.me';  // Your VPS’s public IP
// $dbname = 'mseet_41353142_csms_system';    // Your database name
// $username = 'mseet_41353142';  // Remote user we created
// $password = 'dk6ExcNH3nel'; // Password for remote_user

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);

    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);  // Ensures exceptions are thrown


    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
}
?>

