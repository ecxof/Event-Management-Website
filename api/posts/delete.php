<?php

declare(strict_types=1);

session_start();

header('Content-Type: application/json');

require __DIR__ . '/../../connect_db/db.php';
require __DIR__ . '/helpers.php';

requireMethod('POST');

$userId = requireLogin();
$data = readRequestData();
$postId = getRequiredString($data, 'post_id');

$post = requirePost($db, $postId);
requirePostOwnerOrAdmin($post, $userId);

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
