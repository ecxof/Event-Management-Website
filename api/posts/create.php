<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/session.php';

header('Content-Type: application/json');

require __DIR__ . '/../../connect_db/db.php';
require __DIR__ . '/helpers.php';

// Create-post endpoint: saves a logged-in user's new community post.

requireMethod('POST');

$userId = requireLogin();
$data = readRequestData();

// image_url is already produced by the upload endpoint when the user uploads a photo.
$title = getRequiredString($data, 'title');
$content = getRequiredString($data, 'content');
$imageUrl = getOptionalString($data, 'image_url');

$postObjectId = new MongoDB\BSON\ObjectId();
$postId = (string) $postObjectId;
$now = new MongoDB\BSON\UTCDateTime();

// Posts are soft-deleted later by changing status, so new posts start as active.
$postDocument = [
    '_id' => $postObjectId,
    'post_id' => $postId,
    'user_id' => $userId,
    'title' => $title,
    'content' => $content,
    'image_url' => $imageUrl,
    'created_at' => $now,
    'updated_at' => $now,
    'status' => 'active',
];

$db->selectCollection('posts')->insertOne($postDocument);

respond(201, [
    'success' => true,
    'message' => 'Post created successfully',
    'post' => postToArray($db, $postDocument, $userId),
]);
