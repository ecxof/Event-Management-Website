<?php

declare(strict_types=1);

session_start();

header('Content-Type: application/json');

require __DIR__ . '/../../connect_db/db.php';
require __DIR__ . '/helpers.php';

// Delete-post endpoint: soft-deletes a post owned by the user or managed by an admin.

requireMethod('POST');

$userId = requireLogin();
$data = readRequestData();
$postId = getRequiredString($data, 'post_id');

$post = requirePost($db, $postId);
requirePostOwnerOrAdmin($post, $userId);

// Soft delete keeps the record for history while hiding it from normal queries.
$db->selectCollection('posts')->updateOne(
    buildPostQuery($postId),
    [
        '$set' => [
            'status' => 'deleted',
            'updated_at' => new MongoDB\BSON\UTCDateTime(),
            'deleted_at' => new MongoDB\BSON\UTCDateTime(),
            'deleted_by' => $userId,
        ],
    ]
);

respond(200, [
    'success' => true,
    'message' => 'Post deleted successfully',
    'post_id' => $postId,
]);
