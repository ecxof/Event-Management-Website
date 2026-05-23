<?php

declare(strict_types=1);

session_start();

header('Content-Type: application/json');

require __DIR__ . '/../../connect_db/db.php';
require __DIR__ . '/helpers.php';

requireMethod('GET');

$posts = $db->selectCollection('posts');
$currentUserId = isset($_SESSION['user_id']) ? (string) $_SESSION['user_id'] : null;

$cursor = $posts->find(
    [
        'status' => 'active',
    ],
    [
        'sort' => [
            'created_at' => -1,
        ],
    ]
);

$postList = [];

foreach ($cursor as $post) {
    $postList[] = postToArray($db, $post, $currentUserId);
}

respond(200, [
    'success' => true,
    'posts' => $postList,
]);
