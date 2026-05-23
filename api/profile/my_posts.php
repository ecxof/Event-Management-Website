<?php

declare(strict_types=1);

session_start();

header('Content-Type: application/json');

require __DIR__ . '/../../connect_db/db.php';
require __DIR__ . '/../posts/helpers.php';

requireMethod('GET');

$userId = requireLogin();
$posts = $db->selectCollection('posts');

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

foreach ($cursor as $post) {
    $postList[] = postToArray($db, $post, $userId);
}

respond(200, [
    'success' => true,
    'posts' => $postList,
]);
