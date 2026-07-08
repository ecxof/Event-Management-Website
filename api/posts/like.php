<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/session.php';

header('Content-Type: application/json');

require __DIR__ . '/../../connect_db/db.php';
require __DIR__ . '/helpers.php';

// Like-post endpoint: records a user's like for a post.

requireMethod('POST');

$userId = requireLogin();
$data = readRequestData();
$postId = getRequiredString($data, 'post_id');

$post = requirePost($db, $postId);
$storedPostId = (string) ($post['post_id'] ?? $post['_id']);
$likes = $db->selectCollection('postLikes');

$existingLike = $likes->findOne([
    'post_id' => $storedPostId,
    'user_id' => $userId,
]);

// Likes are idempotent: liking an already-liked post does not create duplicates.
if ($existingLike === null) {
    $likeObjectId = new MongoDB\BSON\ObjectId();

    $likes->insertOne([
        '_id' => $likeObjectId,
        'like_id' => (string) $likeObjectId,
        'post_id' => $storedPostId,
        'user_id' => $userId,
        'created_at' => new MongoDB\BSON\UTCDateTime(),
    ]);
}

respond(200, [
    'success' => true,
    'message' => 'Post liked successfully',
    'post' => postToArray($db, $post, $userId),
]);
