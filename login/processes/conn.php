<?php
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../app/bootstrap.php';
class Database
{
    private static $instance = null;


    public static function getConnection()
    {
        if (self::$instance === null) {

            $dbname = $_ENV['DB_NAME'] ?? 'csms_system';
            $username = $_ENV['DB_USER'] ?? 'root';
            $password = $_ENV['DB_PASS'] ?? '';
            $host = $_ENV['DB_HOST'] ?? 'localhost';
            $dbname = $_ENV['DB_NAME'] ?? 'csms_system';

            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];

            $dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";
            $PDO = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";
            self::$instance = new PDO($dsn, $username, $password, $options);
        }

        return self::$instance;
    }
}
