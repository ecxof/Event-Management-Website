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

$updates = [];

foreach (['title', 'content', 'image_url'] as $field) {
    if (!array_key_exists($field, $data)) {
        continue;
    }

    $value = trim((string) $data[$field]);

    if ($field !== 'image_url' && $value === '') {
        respond(422, [
            'success' => false,
            'message' => "{$field} cannot be empty",
        ]);
    }

    $updates[$field] = $value;
}

if ($updates === []) {
    respond(422, [
        'success' => false,
        'message' => 'No post fields provided to update',
    ]);
}

$updates['updated_at'] = new MongoDB\BSON\UTCDateTime();
$posts = $db->selectCollection('posts');

$posts->updateOne(
    buildPostQuery($postId),
    [
        '$set' => $updates,
    ]
);

$updatedPost = requirePost($db, $postId);

respond(200, [
    'success' => true,
    'message' => 'Post updated successfully',
    'post' => postToArray($db, $updatedPost, $userId),
]);
