<?php

require __DIR__ . '/vendor/autoload.php';

use App\CountryModel;

try {
    $model = new CountryModel();
    echo "Table created successfully";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
