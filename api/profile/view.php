<?php

declare(strict_types=1);

session_start();

header('Content-Type: application/json');

require __DIR__ . '/../../connect_db/db.php';
require __DIR__ . '/../posts/helpers.php';

requireMethod('GET');
requireLogin();

$userId = trim((string) ($_GET['user_id'] ?? ''));

if ($userId === '') {
    respond(422, [
        'success' => false,
        'message' => 'user_id is required',
    ]);
}

$users = $db->selectCollection('users');
$posts = $db->selectCollection('posts');
$user = $users->findOne(['user_id' => $userId]);

if ($user === null) {
    respond(404, [
        'success' => false,
        'message' => 'User not found',
    ]);
}

$cursor = $posts->find(
    [
        'user_id' => $userId,
        'status' => 'active',
    ],
    [
        'sort' => [
            'created_at' => -1,
        ],
    ]
);

$postList = [];
$currentUserId = (string) $_SESSION['user_id'];

foreach ($cursor as $post) {
    $postList[] = postToArray($db, $post, $currentUserId);
}

respond(200, [
    'success' => true,
    'profile' => [
        'user_id' => (string) ($user['user_id'] ?? $user['_id']),
        'username' => (string) ($user['username'] ?? ''),
        'role' => (string) ($user['role'] ?? 'user'),
        'anime_interest' => $user['anime_interest'] ?? '',
        'created_at' => valueToString($user['created_at'] ?? null),
    ],
    'posts' => $postList,
]);
