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
        $host = getenv('DB_HOST') ?: trim($_ENV['DB_HOST'] ?? '');
        $name = getenv('DB_NAME') ?: trim($_ENV['DB_NAME'] ?? '');
        $user = getenv('DB_USER') ?: trim($_ENV['DB_USER'] ?? '');
        $pass = getenv('DB_PASS') ?: trim($_ENV['DB_PASS'] ?? '');
        if (!$host || !$name || !$user) {
            throw new PDOException("Database configuration missing: DB_HOST, DB_NAME, DB_USER are required.");
        }
        $charset = getenv('DB_CHARSET') ?: trim($_ENV['DB_CHARSET'] ?? 'utf8mb4');
        $dsn = 'mysql:host=' . $host . ';dbname=' . $name . ';charset=' . $charset;
        $port = getenv('DB_PORT') ?: trim($_ENV['DB_PORT'] ?? '');
        if ($port && $port !== '3306') {
            $dsn .= ';port=' . $port;
        }
        $sslMode = getenv('DB_SSL_MODE') ?: trim($_ENV['DB_SSL_MODE'] ?? '');
        $pdoOptions = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ];
        if ($sslMode === 'REQUIRED') {
            $pdoOptions[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = false; // Adjust as needed for security
        }

        try {
            $this->pdo = new PDO($dsn, $user, $pass, $pdoOptions);
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
