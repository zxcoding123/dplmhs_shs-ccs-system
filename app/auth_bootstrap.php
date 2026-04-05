<?php
require_once __DIR__ . '/../login/processes/conn.php';
require_once __DIR__ . '/services/AuthService.php';
$pdo = Database::getConnection();
$auth = new AuthService($pdo);
