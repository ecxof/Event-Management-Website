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

$username = trim((string) ($data['username'] ?? ''));
$email = strtolower(trim((string) ($data['email'] ?? '')));
$password = (string) ($data['password'] ?? '');
$animeInterest = $data['anime_interest'] ?? '';
$telephone = trim((string) ($data['telephone'] ?? ''));

if ($username === '' || $email === '' || $password === '') {
    respond(422, [
        'success' => false,
        'message' => 'Username, email, and password are required',
    ]);
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    respond(422, [
        'success' => false,
        'message' => 'Invalid email address',
    ]);
}

if (strlen($password) < 8) {
    respond(422, [
        'success' => false,
        'message' => 'Password must be at least 8 characters',
    ]);
}

if ($telephone !== '' && !ctype_digit($telephone)) {
    respond(422, [
        'success' => false,
        'message' => 'Telephone must contain numbers only',
    ]);
}

$users = $db->selectCollection('users');
$users->createIndex(['email' => 1], ['unique' => true]);

$existingUser = $users->findOne(['email' => $email]);

if ($existingUser !== null) {
    respond(409, [
        'success' => false,
        'message' => 'Email already registered',
    ]);
}

$userObjectId = new MongoDB\BSON\ObjectId();
$userId = (string) $userObjectId;

$userDocument = [
    '_id' => $userObjectId,
    'user_id' => $userId,
    'username' => $username,
    'email' => $email,
    'password' => password_hash($password, PASSWORD_DEFAULT),
    'role' => 'user',
    'anime_interest' => $animeInterest,
    'telephone' => $telephone === '' ? null : (int) $telephone,
    'created_at' => new MongoDB\BSON\UTCDateTime(),
];

$users->insertOne($userDocument);

session_regenerate_id(true);

$_SESSION['user_id'] = $userId;
$_SESSION['username'] = $username;
$_SESSION['role'] = 'user';

respond(201, [
    'success' => true,
    'message' => 'Registered successfully',
    'user' => [
        'user_id' => $userId,
        'username' => $username,
        'email' => $email,
        'role' => 'user',
        'anime_interest' => $animeInterest,
        'telephone' => $telephone === '' ? null : (int) $telephone,
    ],
]);
