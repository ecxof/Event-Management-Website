<?php

declare(strict_types=1);

session_start();

header('Content-Type: application/json');

require __DIR__ . '/../../connect_db/db.php';
require __DIR__ . '/helpers.php';

// Unlike-post endpoint: removes the current user's like from a post.

requireMethod('POST');

$userId = requireLogin();
$data = readRequestData();
$postId = getRequiredString($data, 'post_id');

$post = requirePost($db, $postId);
$storedPostId = (string) ($post['post_id'] ?? $post['_id']);

// Deleting a missing like is still a successful no-op for the frontend.
$db->selectCollection('postLikes')->deleteOne([
    'post_id' => $storedPostId,
    'user_id' => $userId,
]);

respond(200, [
    'success' => true,
    'message' => 'Post unliked successfully',
    'post' => postToArray($db, $post, $userId),
]);
