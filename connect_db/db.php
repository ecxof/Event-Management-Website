<?php

require __DIR__ . '/../vendor/autoload.php';

$config = require __DIR__ . '/config.php';

$mongodbUri = strtolower($config['mongodb_uri']);

if (str_contains($mongodbUri, 'username') || str_contains($mongodbUri, 'password')) {
    die('Please update mongodb_uri in config.php first.');
}

try {
    $client = new MongoDB\Client($config['mongodb_uri']);
    $db = $client->selectDatabase($config['database']);
} catch (Throwable $e) {
    die('MongoDB connection error: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8'));
}
