<?php

declare(strict_types=1);

session_start();

header('Content-Type: application/json');

require __DIR__ . '/../../connect_db/db.php';

function readRequestData(): array
{
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';

    if (str_contains($contentType, 'application/json')) {
        $rawBody = file_get_contents('php://input');
        $jsonData = json_decode($rawBody ?: '', true);

        if (is_array($jsonData)) {
            return $jsonData;
        }
    }

    return $_POST;
}

function respond(int $statusCode, array $payload): void
{
    http_response_code($statusCode);
    echo json_encode($payload);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(405, [
        'success' => false,
        'message' => 'Method not allowed',
    ]);
}

$data = readRequestData();

$email = strtolower(trim((string) ($data['email'] ?? '')));
$password = (string) ($data['password'] ?? '');

if ($email === '' || $password === '') {
    respond(422, [
        'success' => false,
        'message' => 'Email and password are required',
    ]);
}

$users = $db->selectCollection('users');
$user = $users->findOne(['email' => $email]);

if ($user === null || !isset($user['password']) || !password_verify($password, (string) $user['password'])) {
    respond(401, [
        'success' => false,
        'message' => 'Invalid email or password',
    ]);
}

session_regenerate_id(true);

$_SESSION['user_id'] = (string) $user['user_id'];
$_SESSION['username'] = (string) $user['username'];
$_SESSION['role'] = (string) $user['role'];

respond(200, [
    'success' => true,
    'message' => 'Logged in successfully',
    'user' => [
        'user_id' => (string) $user['user_id'],
        'username' => (string) $user['username'],
        'email' => (string) $user['email'],
        'role' => (string) $user['role'],
        'anime_interest' => $user['anime_interest'] ?? '',
        'telephone' => $user['telephone'] ?? null,
    ],
]);
