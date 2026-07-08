<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/session.php';

header('Content-Type: application/json');

require __DIR__ . '/../../connect_db/db.php';
require __DIR__ . '/../pagination.php';
require __DIR__ . '/../posts/helpers.php';

// My-posts endpoint: returns paginated active posts created by the logged-in user.

requireMethod('GET');

$userId = requireLogin();
$posts = $db->selectCollection('posts');
$pagination = readPaginationParams();

// Scope the feed to this user's active posts only.
$query = [
    'user_id' => $userId,
    'status' => 'active',
];
$total = $posts->countDocuments($query);
$pagination = clampPagination($pagination, $total);

// Load only the requested page of the user's posts.
$cursor = $posts->find(
    $query,
    [
        'sort' => [
            'created_at' => -1,
        ],
        'skip' => $pagination['skip'],
        'limit' => $pagination['limit'],
    ]
);

$postList = [];

foreach ($cursor as $post) {
    $postList[] = postToArray($db, $post, $userId);
}

respond(200, [
    'success' => true,
    'posts' => $postList,
    'pagination' => paginationMeta($pagination['page'], $pagination['limit'], $total),
]);
