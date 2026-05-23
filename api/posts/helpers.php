<?php

declare(strict_types=1);

function readRequestData(): array
{
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';

    if (str_contains($contentType, 'application/json')) {
        $rawBody = file_get_contents('php://input');
        $jsonData = json_decode($rawBody ?: '', true);

        if (is_array($jsonData)) {
            return $jsonData;
        }
    }

    return $_POST;
}

function respond(int $statusCode, array $payload): void
{
    http_response_code($statusCode);
    echo json_encode($payload);
    exit;
}

function requireMethod(string $method): void
{
    if ($_SERVER['REQUEST_METHOD'] !== $method) {
        respond(405, [
            'success' => false,
            'message' => 'Method not allowed',
        ]);
    }
}

function requireLogin(): string
{
    if (!isset($_SESSION['user_id'])) {
        respond(401, [
            'success' => false,
            'message' => 'Please log in first',
        ]);
    }

    return (string) $_SESSION['user_id'];
}

function isAdmin(): bool
{
    return ($_SESSION['role'] ?? '') === 'admin';
}

function getRequiredString(array $data, string $field): string
{
    $value = trim((string) ($data[$field] ?? ''));

    if ($value === '') {
        respond(422, [
            'success' => false,
            'message' => "{$field} is required",
        ]);
    }

    return $value;
}

function getOptionalString(array $data, string $field): string
{
    return trim((string) ($data[$field] ?? ''));
}

function buildPostQuery(string $postId, bool $includeDeleted = false): array
{
    $query = [
        '$or' => [
            ['post_id' => $postId],
        ],
    ];

    if (preg_match('/^[a-f\d]{24}$/i', $postId) === 1) {
        $query['$or'][] = ['_id' => new MongoDB\BSON\ObjectId($postId)];
    }

    if (!$includeDeleted) {
        $query['status'] = 'active';
    }

    return $query;
}

function valueToString(mixed $value): ?string
{
    if ($value === null) {
        return null;
    }

    if ($value instanceof MongoDB\BSON\UTCDateTime) {
        return $value->toDateTime()->format(DateTimeInterface::ATOM);
    }

    return (string) $value;
}

function userSummary(MongoDB\Database $db, string $userId): array
{
    $user = $db->selectCollection('users')->findOne(['user_id' => $userId]);

    if ($user === null) {
        return [
            'user_id' => $userId,
            'username' => '',
            'email' => '',
        ];
    }

    return [
        'user_id' => (string) ($user['user_id'] ?? $userId),
        'username' => (string) ($user['username'] ?? ''),
        'email' => (string) ($user['email'] ?? ''),
    ];
}

function postToArray(MongoDB\Database $db, array|object $post, ?string $currentUserId = null): array
{
    $postId = (string) ($post['post_id'] ?? $post['_id']);
    $authorId = (string) ($post['user_id'] ?? '');

    $likes = $db->selectCollection('postLikes');
    $comments = $db->selectCollection('postComments');
    $shares = $db->selectCollection('postShares');

    $isLiked = false;

    if ($currentUserId !== null) {
        $isLiked = $likes->countDocuments([
            'post_id' => $postId,
            'user_id' => $currentUserId,
        ]) > 0;
    }

    return [
        'post_id' => $postId,
        'user_id' => $authorId,
        'author' => userSummary($db, $authorId),
        'title' => (string) ($post['title'] ?? ''),
        'content' => (string) ($post['content'] ?? ''),
        'image_url' => (string) ($post['image_url'] ?? ''),
        'created_at' => valueToString($post['created_at'] ?? null),
        'updated_at' => valueToString($post['updated_at'] ?? null),
        'status' => (string) ($post['status'] ?? 'active'),
        'like_count' => $likes->countDocuments(['post_id' => $postId]),
        'comment_count' => $comments->countDocuments([
            'post_id' => $postId,
            'status' => 'active',
        ]),
        'share_count' => $shares->countDocuments(['post_id' => $postId]),
        'is_liked' => $isLiked,
    ];
}

function commentToArray(MongoDB\Database $db, array|object $comment): array
{
    $userId = (string) ($comment['user_id'] ?? '');

    return [
        'comment_id' => (string) ($comment['comment_id'] ?? $comment['_id']),
        'post_id' => (string) ($comment['post_id'] ?? ''),
        'user_id' => $userId,
        'author' => userSummary($db, $userId),
        'content' => (string) ($comment['content'] ?? ''),
        'created_at' => valueToString($comment['created_at'] ?? null),
        'updated_at' => valueToString($comment['updated_at'] ?? null),
        'status' => (string) ($comment['status'] ?? 'active'),
    ];
}

function requirePost(MongoDB\Database $db, string $postId, bool $includeDeleted = false): array|object
{
    $post = $db->selectCollection('posts')->findOne(buildPostQuery($postId, $includeDeleted));

    if ($post === null) {
        respond(404, [
            'success' => false,
            'message' => 'Post not found',
        ]);
    }

    return $post;
}

function requirePostOwnerOrAdmin(array|object $post, string $userId): void
{
    if ((string) ($post['user_id'] ?? '') !== $userId && !isAdmin()) {
        respond(403, [
            'success' => false,
            'message' => 'You can only modify your own post',
        ]);
    }
}
