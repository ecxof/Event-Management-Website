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
$storedPostId = (string) ($post['post_id'] ?? $post['_id']);

$db->selectCollection('postLikes')->deleteOne([
    'post_id' => $storedPostId,
    'user_id' => $userId,
]);

respond(200, [
    'success' => true,
    'message' => 'Post unliked successfully',
    'post' => postToArray($db, $post, $userId),
]);
