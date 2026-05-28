<?php

declare(strict_types=1);

session_start();

header('Content-Type: application/json');

require __DIR__ . '/../../connect_db/db.php';

// Login endpoint: verifies email/password and stores the authenticated user in the PHP session.

// Read JSON login data from the request body, or fall back to normal form data.
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

// Send a JSON response and stop execution.
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

// Normalize login credentials before checking them against MongoDB.
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

// Password hashes are verified server-side; the stored password is never returned.
if ($user === null || !isset($user['password']) || !password_verify($password, (string) $user['password'])) {
    respond(401, [
        'success' => false,
        'message' => 'Invalid email or password',
    ]);
}

// Regenerate the session id after login to reduce session fixation risk.
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
