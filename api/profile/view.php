<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/session.php';

header('Content-Type: application/json');

require __DIR__ . '/../../connect_db/db.php';
require __DIR__ . '/../posts/helpers.php';

// Public-profile endpoint: returns another user's public profile and active posts.

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

// Public profile view shows the selected user's active posts newest first.
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

// The current user id is passed in so each post can include is_liked for the viewer.
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
        'avatar_url' => (string) ($user['avatar_url'] ?? ''),
        'created_at' => valueToString($user['created_at'] ?? null),
    ],
    'posts' => $postList,
]);
