<?php
require_once __DIR__ . '/../database/connection.php';
require_once __DIR__ . '/services/AuthService.php';
$pdo = Database::getConnection();
$auth = new AuthService($pdo);
