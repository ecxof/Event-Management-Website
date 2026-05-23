<?php

declare(strict_types=1);

session_start();

header('Content-Type: application/json');

require __DIR__ . '/../../connect_db/db.php';
require __DIR__ . '/../posts/helpers.php';

requireMethod('GET');

$userId = requireLogin();
$likes = $db->selectCollection('postLikes');
$posts = $db->selectCollection('posts');

$cursor = $likes->find(
    [
        'user_id' => $userId,
    ],
    [
        'sort' => [
            'created_at' => -1,
        ],
    ]
);

$postList = [];

foreach ($cursor as $like) {
    $postId = (string) ($like['post_id'] ?? '');

    if ($postId === '') {
        continue;
    }

    $post = $posts->findOne(buildPostQuery($postId));

    if ($post === null) {
        continue;
    }

    $postList[] = [
        ...postToArray($db, $post, $userId),
        'liked_at' => valueToString($like['created_at'] ?? null),
    ];
}

respond(200, [
    'success' => true,
    'posts' => $postList,
]);
