<?php
require 'conn.php';
require '../../app/services/AuthService.php';

$pdo = Database::getConnection();
$auth = new AuthService($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    $auth->login($email, $password);
}
