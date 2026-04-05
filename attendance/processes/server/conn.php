<?php

// $host = '151.106.112.223'; 
// $dbname = 'csms_system';   
// $username = 'remote_user';  
// $password = 'your_remote_password'; 

?>


<?php
$host = 'localhost';  // Your VPS’s public IP
$dbname = 'csms_system';    // Your database name
$username = 'root';  // Remote user we created
$password = ''; // Password for remote_user

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);

    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);  // Ensures exceptions are thrown


    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
}
?>

