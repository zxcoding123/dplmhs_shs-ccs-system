<?php
/**
 * Single database connection for the whole system.
 *
 * Every page/process includes this file. Credentials come from .env
 * (DB_HOST, DB_NAME, DB_USER, DB_PASS) with local XAMPP defaults.
 *
 *   require_once __DIR__ . '/../database/connection.php';
 *   $pdo->prepare(...);                 // shared PDO instance
 *   $pdo = Database::getConnection();   // same instance
 */

date_default_timezone_set('Asia/Manila');

require_once __DIR__ . '/../vendor/autoload.php';

if (!class_exists('Database')) {
    class Database
    {
        private static $instance = null;

        public static function getConnection()
        {
            if (self::$instance === null) {
                if (class_exists(\Dotenv\Dotenv::class)) {
                    \Dotenv\Dotenv::createImmutable(dirname(__DIR__))->safeLoad();
                }

                $host     = $_ENV['DB_HOST'] ?? 'localhost';
                $dbname   = $_ENV['DB_NAME'] ?? 'csms_system';
                $username = $_ENV['DB_USER'] ?? 'root';
                $password = $_ENV['DB_PASS'] ?? '';

                $options = [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ];

                $dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";
                self::$instance = new PDO($dsn, $username, $password, $options);
            }

            return self::$instance;
        }
    }
}

try {
    $pdo = Database::getConnection();
    $conn = $pdo;
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
