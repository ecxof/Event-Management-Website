<?php

declare(strict_types=1);

session_start();

header('Content-Type: application/json');

require __DIR__ . '/../../connect_db/db.php';
require __DIR__ . '/helpers.php';

requireMethod('POST');

$userId = requireLogin();
$data = readRequestData();
$users = $db->selectCollection('users');

requireCurrentUser($db, $userId);

$updates = [];

if (array_key_exists('username', $data)) {
    $username = trim((string) $data['username']);

    if ($username === '') {
        respond(422, [
            'success' => false,
            'message' => 'username cannot be empty',
        ]);
    }

    $updates['username'] = $username;
}

if (array_key_exists('anime_interest', $data)) {
    $updates['anime_interest'] = trim((string) $data['anime_interest']);
}

if (array_key_exists('telephone', $data)) {
    $telephone = trim((string) $data['telephone']);

    if ($telephone !== '' && !ctype_digit($telephone)) {
        respond(422, [
            'success' => false,
            'message' => 'telephone must contain numbers only',
        ]);
    }

    $updates['telephone'] = $telephone === '' ? null : (int) $telephone;
}

if (array_key_exists('email', $data)) {
    $email = strtolower(trim((string) $data['email']));

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        respond(422, [
            'success' => false,
            'message' => 'Invalid email address',
        ]);
    }

    $existingUser = $users->findOne([
        'email' => $email,
        'user_id' => [
            '$ne' => $userId,
        ],
    ]);

    if ($existingUser !== null) {
        respond(409, [
            'success' => false,
            'message' => 'Email already registered',
        ]);
    }

    $updates['email'] = $email;
}

if (array_key_exists('password', $data) && trim((string) $data['password']) !== '') {
    $password = (string) $data['password'];

    if (strlen($password) < 8) {
        respond(422, [
            'success' => false,
            'message' => 'Password must be at least 8 characters',
        ]);
    }

    $updates['password'] = password_hash($password, PASSWORD_DEFAULT);
}

if ($updates === []) {
    respond(422, [
        'success' => false,
        'message' => 'No profile fields provided to update',
    ]);
}

$updates['updated_at'] = new MongoDB\BSON\UTCDateTime();

$users->updateOne(
    [
        'user_id' => $userId,
    ],
    [
        '$set' => $updates,
    ]
);

$updatedUser = requireCurrentUser($db, $userId);

$_SESSION['username'] = (string) ($updatedUser['username'] ?? '');
$_SESSION['role'] = (string) ($updatedUser['role'] ?? 'user');

respond(200, [
    'success' => true,
    'message' => 'Profile updated successfully',
    'profile' => userToProfile($updatedUser),
]);
