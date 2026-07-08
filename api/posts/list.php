<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/session.php';

header('Content-Type: application/json');

require __DIR__ . '/../../connect_db/db.php';
require __DIR__ . '/../pagination.php';
require __DIR__ . '/helpers.php';

// Post-list endpoint: returns paginated active community posts newest first.

requireMethod('GET');

$posts = $db->selectCollection('posts');
$currentUserId = isset($_SESSION['user_id']) ? (string) $_SESSION['user_id'] : null;
$pagination = readPaginationParams();

// Only active posts are visible in public feeds.
$query = [
    'status' => 'active',
];
$total = $posts->countDocuments($query);
$pagination = clampPagination($pagination, $total);

// Query only the requested page so large feeds do not load all posts at once.
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

// postToArray adds author data, counts, and current user's like state.
foreach ($cursor as $post) {
    $postList[] = postToArray($db, $post, $currentUserId);
}

respond(200, [
    'success' => true,
    'posts' => $postList,
    'pagination' => paginationMeta($pagination['page'], $pagination['limit'], $total),
]);
