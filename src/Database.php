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

        $driver = $_ENV['DB_CONNECTION'] ?? 'sqlite';
        if ($driver === 'sqlite') {
            $dsn = 'sqlite:' . __DIR__ . '/../' . $_ENV['DB_DATABASE'];
        } else {
            // For MySQL if needed
            $dsn = 'mysql:host=' . $_ENV['DB_HOST'] . ';dbname=' . $_ENV['DB_NAME'] . ';charset=' . ($_ENV['DB_CHARSET'] ?? 'utf8mb4');
        }

        try {
            $this->pdo = new PDO($dsn, $_ENV['DB_USER'] ?? null, $_ENV['DB_PASS'] ?? null, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
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
