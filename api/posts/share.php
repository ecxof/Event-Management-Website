<?php

declare(strict_types=1);

session_start();

header('Content-Type: application/json');

require __DIR__ . '/../../connect_db/db.php';
require __DIR__ . '/helpers.php';

// Share endpoint: records one share action for analytics/counting.

requireMethod('POST');

$userId = requireLogin();
$data = readRequestData();
$postId = getRequiredString($data, 'post_id');

$post = requirePost($db, $postId);
$storedPostId = (string) ($post['post_id'] ?? $post['_id']);
$shareObjectId = new MongoDB\BSON\ObjectId();

// Each share click is stored as a separate document so share_count can be calculated.
$shareDocument = [
    '_id' => $shareObjectId,
    'share_id' => (string) $shareObjectId,
    'post_id' => $storedPostId,
    'user_id' => $userId,
    'created_at' => new MongoDB\BSON\UTCDateTime(),
];

$db->selectCollection('postShares')->insertOne($shareDocument);

respond(201, [
    'success' => true,
    'message' => 'Post shared successfully',
    'share' => [
        'share_id' => (string) $shareDocument['share_id'],
        'post_id' => $storedPostId,
        'user_id' => $userId,
        'created_at' => valueToString($shareDocument['created_at']),
    ],
    'post' => postToArray($db, $post, $userId),
]);
