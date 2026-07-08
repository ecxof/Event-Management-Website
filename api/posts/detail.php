<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/session.php';

header('Content-Type: application/json');

require __DIR__ . '/../../connect_db/db.php';
require __DIR__ . '/helpers.php';

// Post-detail endpoint: returns one post and its active comments.

requireMethod('GET');

$postId = trim((string) ($_GET['post_id'] ?? ''));

if ($postId === '') {
    respond(422, [
        'success' => false,
        'message' => 'post_id is required',
    ]);
}

$post = requirePost($db, $postId);
$currentUserId = isset($_SESSION['user_id']) ? (string) $_SESSION['user_id'] : null;
$comments = $db->selectCollection('postComments');

// Comments are sorted oldest first for a natural conversation order.
$commentCursor = $comments->find(
    [
        'post_id' => (string) ($post['post_id'] ?? $post['_id']),
        'status' => 'active',
    ],
    [
        'sort' => [
            'created_at' => 1,
        ],
    ]
);

$commentList = [];

foreach ($commentCursor as $comment) {
    $commentList[] = commentToArray($db, $comment);
}

respond(200, [
    'success' => true,
    'post' => [
        ...postToArray($db, $post, $currentUserId),
        'comments' => $commentList,
    ],
]);
