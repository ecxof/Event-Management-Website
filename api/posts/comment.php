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
$content = getRequiredString($data, 'content');

$post = requirePost($db, $postId);
$storedPostId = (string) ($post['post_id'] ?? $post['_id']);
$commentObjectId = new MongoDB\BSON\ObjectId();
$now = new MongoDB\BSON\UTCDateTime();

$commentDocument = [
    '_id' => $commentObjectId,
    'comment_id' => (string) $commentObjectId,
    'post_id' => $storedPostId,
    'user_id' => $userId,
    'content' => $content,
    'created_at' => $now,
    'updated_at' => $now,
    'status' => 'active',
];

$db->selectCollection('postComments')->insertOne($commentDocument);

respond(201, [
    'success' => true,
    'message' => 'Comment added successfully',
    'comment' => commentToArray($db, $commentDocument),
]);
