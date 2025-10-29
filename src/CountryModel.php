<?php

namespace App;

use PDO;
use GuzzleHttp\Client;
use Exception;

class CountryModel
{
    private $pdo;

    public function __construct()
    {
        $this->pdo = Database::getInstance()->getConnection();
        $this->createTableIfNotExists();
    }

    private function createTableIfNotExists()
    {
        $sql = "
            CREATE TABLE IF NOT EXISTS countries (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(255) NOT NULL,
                capital VARCHAR(255),
                region VARCHAR(255),
                population BIGINT,
                currency_code VARCHAR(10),
                exchange_rate DECIMAL(15,4),
                estimated_gdp DECIMAL(20,2),
                flag_url TEXT,
                last_refreshed_at TIMESTAMP
            ) ENGINE=InnoDB
        ";
        $this->pdo->exec($sql);
    }

    public function fetchAll($filters = [], $sort = null)
    {
        $query = "SELECT * FROM countries WHERE 1=1";
        $params = [];

        if (isset($filters['region'])) {
            $query .= " AND region LIKE ?";
            $params[] = '%' . $filters['region'] . '%';
        }

        if (isset($filters['currency'])) {
            $query .= " AND currency_code LIKE ?";
            $params[] = '%' . $filters['currency'] . '%';
        }

        if ($sort === 'gdp_desc') {
            $query .= " ORDER BY estimated_gdp IS NULL ASC, estimated_gdp DESC";
        } elseif ($sort === 'gdp_asc') {
            $query .= " ORDER BY estimated_gdp IS NULL ASC, estimated_gdp ASC";
        }

        $stmt = $this->pdo->prepare($query);
        $stmt->execute($params);
        $data = $stmt->fetchAll();

        return array_map(fn($row) => new Country($row), $data);
    }

    public function findByName($name)
    {
        $stmt = $this->pdo->prepare("SELECT * FROM countries WHERE name = ?");
        $stmt->execute([$name]);
        $data = $stmt->fetch();
        return $data ? new Country($data) : null;
    }

    public function deleteByName($name)
    {
        $stmt = $this->pdo->prepare("DELETE FROM countries WHERE name = ?");
        $stmt->execute([$name]);
        return $stmt->rowCount();
    }

    public function getStatus()
    {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) as total FROM countries");
        $stmt->execute();
        $total = $stmt->fetch()['total'];

        $stmt = $this->pdo->prepare("SELECT MAX(last_refreshed_at) as last_refreshed FROM countries");
        $stmt->execute();
        $last = $stmt->fetch()['last_refreshed'];

        return [
            'total_countries' => $total,
            'last_refreshed_at' => $last ? date('c', strtotime($last)) : null,
        ];
    }

    public function refreshData()
    {
        try {
            $client = new Client(['timeout' => 30]);

            // Fetch countries
            $response = $client->get('https://restcountries.com/v2/all?fields=name,capital,region,population,flag,currencies');
            $countries = json_decode($response->getBody(), true);

            // Fetch exchange rates
            $exchangeResponse = $client->get('https://open.er-api.com/v6/latest/USD');
            $exchangeData = json_decode($exchangeResponse->getBody(), true);
            $rates = $exchangeData['rates'];

            $updated = [];

            foreach ($countries as $country) {
                $name = $country['name'];
                $capital = $country['capital'] ?? null;
                $region = $country['region'] ?? null;
                $population = $country['population'];
                $flag_url = $country['flag'] ?? null;

                $currencies = $country['currencies'] ?? [];
                if (empty($currencies)) {
                    $currency_code = null;
                    $exchange_rate = null;
                } else {
                    $currency_code = $currencies[0]['code'] ?? null;
                    $exchange_rate = isset($rates[$currency_code]) ? $rates[$currency_code] : null;
                }

                if ($currency_code && $exchange_rate) {
                    $multiplier = rand(1000, 2000);
                    $estimated_gdp = $population * $multiplier / $exchange_rate;
                } elseif ($currency_code) {
                    $estimated_gdp = null; // Rate not found
                } else {
                    $estimated_gdp = 0; // No currency
                }

                // Update or insert
                $existing = $this->findByName($name);
                if ($existing) {
                    $stmt = $this->pdo->prepare(
                        "UPDATE countries SET capital=?, region=?, population=?, currency_code=?, exchange_rate=?, estimated_gdp=?, flag_url=?, last_refreshed_at=NOW() WHERE name=?"
                    );
                    $stmt->execute([$capital, $region, $population, $currency_code, $exchange_rate, $estimated_gdp, $flag_url, $name]);
                } else {
                    $stmt = $this->pdo->prepare(
                        "INSERT INTO countries (name, capital, region, population, currency_code, exchange_rate, estimated_gdp, flag_url, last_refreshed_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())"
                    );
                    $stmt->execute([$name, $capital, $region, $population, $currency_code, $exchange_rate, $estimated_gdp, $flag_url]);
                }

                $updated[] = $name;
            }

            // Generate image after refresh
            $this->generateSummaryImage();

            return true;
        } catch (Exception $e) {
            throw new Exception("External data source unavailable: " . $e->getMessage());
        }
    }

    private function generateSummaryImage()
    {
        if (!extension_loaded('gd')) {
            return; // Skip if GD not available
        }

        // Get data
        $stmt = $this->pdo->prepare("SELECT COUNT(*) as total FROM countries");
        $stmt->execute();
        $total_countries = $stmt->fetch()['total'];

        $stmt = $this->pdo->prepare("SELECT name, estimated_gdp FROM countries WHERE estimated_gdp IS NOT NULL ORDER BY estimated_gdp DESC LIMIT 5");
        $stmt->execute();
        $top5 = $stmt->fetchAll();

        $stmt = $this->pdo->prepare("SELECT MAX(last_refreshed_at) as last FROM countries");
        $stmt->execute();
        $last_refresh = $stmt->fetch()['last'];

        // Ensure cache directory exists
        $cacheDir = __DIR__ . '/../cache';
        if (!is_dir($cacheDir)) {
            mkdir($cacheDir, 0777, true);
        }

        // Create image
        $width = 600;
        $height = 400;
        $image = imagecreatetruecolor($width, $height);
        if (!$image) {
            return; // Failed to create image
        }
        $white = imagecolorallocate($image, 255, 255, 255);
        $black = imagecolorallocate($image, 0, 0, 0);
        imagefill($image, 0, 0, $white);

        $font = 5; // Built-in font
        $y = 20;
        imagestring($image, 3, 10, $y, "Total Countries: $total_countries", $black);
        $y += 30;
        imagestring($image, 3, 10, $y, "Last Refresh: $last_refresh", $black);
        $y += 40;
        imagestring($image, 3, 10, $y, "Top 5 Countries by GDP:", $black);
        $y += 20;

        foreach ($top5 as $country) {
            imagestring($image, 2, 20, $y, "{$country['name']}: " . number_format($country['estimated_gdp'], 0), $black);
            $y += 15;
        }

        // Save to cache/summary.png
        imagepng($image, $cacheDir . '/summary.png');
        imagedestroy($image);
    }

    public function getSummaryImagePath()
    {
        return __DIR__ . '/../cache/summary.png';
    }
}
