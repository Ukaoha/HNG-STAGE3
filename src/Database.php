<?php

namespace App;

use PDO;
use PDOException;
use Dotenv\Dotenv;

class Database
{
    private static $instance = null;
    private $pdo;

    private function __construct()
    {
        $dotenv = Dotenv::createImmutable(__DIR__ . '/../');
        $dotenv->load();

        $driver = trim($_ENV['DB_CONNECTION'] ?? 'sqlite');
        if ($driver === 'sqlite') {
            $dsn = 'sqlite:' . __DIR__ . '/../' . trim($_ENV['DB_DATABASE']);
        } else {
            // For MySQL if needed
            $dsn = 'mysql:host=' . trim($_ENV['DB_HOST']) . ';dbname=' . trim($_ENV['DB_NAME']) . ';charset=' . (trim($_ENV['DB_CHARSET'] ?? 'utf8mb4'));
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
}
