<?php

require __DIR__ . '/../vendor/autoload.php';

$config = require __DIR__ . '/config.php';

if (str_contains($config['mongodb_uri'], 'USERNAME') || str_contains($config['mongodb_uri'], 'PASSWORD')) {
    die('Please update mongodb_uri in config.php first.');
}

try {
    $client = new MongoDB\Client($config['mongodb_uri']);
    $db = $client->selectDatabase($config['database']);
} catch (Throwable $e) {
    die('MongoDB connection error: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8'));
}