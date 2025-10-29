<?php

namespace App;

use PDO;
use PDOException;
use Dotenv\Dotenv;

class Database
{
    private static $instance = null;
    private $pdo;
    private $driver;

    private function __construct()
    {
        $dotenv = Dotenv::createImmutable(__DIR__ . '/../');
        $dotenv->safeLoad();

        $this->driver = 'mysql';
        $host = trim($_ENV['DB_HOST'] ?? '');
        $name = trim($_ENV['DB_NAME'] ?? '');
        $user = trim($_ENV['DB_USER'] ?? '');
        $pass = trim($_ENV['DB_PASS'] ?? '');
        if (!$host || !$name || !$user) {
            throw new PDOException("Database configuration missing: DB_HOST, DB_NAME, DB_USER are required.");
        }
        $dsn = 'mysql:host=' . $host . ';dbname=' . $name . ';charset=' . (trim($_ENV['DB_CHARSET'] ?? 'utf8mb4'));
        $port = isset($_ENV['DB_PORT']) ? trim($_ENV['DB_PORT']) : null;
        if ($port && $port !== '3306') {
            $dsn .= ';port=' . $port;
        }
        $sslMode = trim($_ENV['DB_SSL_MODE'] ?? '');
        $pdoOptions = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ];
        if ($sslMode === 'REQUIRED') {
            $pdoOptions[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = false; // Adjust as needed for security
        }

        try {
            $this->pdo = new PDO($dsn, $_ENV['DB_USER'] ?? null, $_ENV['DB_PASS'] ?? null, $pdoOptions);
        } catch (PDOException $e) {
            throw new PDOException($e->getMessage(), (int)$e->getCode());
        }
    }

    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection()
    {
        return $this->pdo;
    }

    public function getDriver()
    {
        return $this->driver;
    }
}
