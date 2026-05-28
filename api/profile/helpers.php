<?php

declare(strict_types=1);

// Read JSON request bodies when present, otherwise support regular form data.
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

// Send one JSON response and stop the current API script.
function respond(int $statusCode, array $payload): void
{
    http_response_code($statusCode);
    echo json_encode($payload);
    exit;
}

// Ensure an endpoint is called with the expected HTTP method.
function requireMethod(string $method): void
{
    if ($_SERVER['REQUEST_METHOD'] !== $method) {
        respond(405, [
            'success' => false,
            'message' => 'Method not allowed',
        ]);
    }
}

// Require a logged-in user and return their user_id.
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

// Convert MongoDB date values into strings for JSON responses.
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

// Convert a user document into the profile object used by Profile pages.
function userToProfile(array|object $user): array
{
    return [
        'user_id' => (string) ($user['user_id'] ?? $user['_id']),
        'username' => (string) ($user['username'] ?? ''),
        'email' => (string) ($user['email'] ?? ''),
        'role' => (string) ($user['role'] ?? 'user'),
        'anime_interest' => $user['anime_interest'] ?? '',
        'telephone' => $user['telephone'] ?? null,
        'avatar_url' => (string) ($user['avatar_url'] ?? ''),
        'created_at' => valueToString($user['created_at'] ?? null),
        'updated_at' => valueToString($user['updated_at'] ?? null),
    ];
}

// Load the current user record or return a 404 response if it no longer exists.
function requireCurrentUser(MongoDB\Database $db, string $userId): array|object
{
    $user = $db->selectCollection('users')->findOne(['user_id' => $userId]);

    if ($user === null) {
        respond(404, [
            'success' => false,
            'message' => 'User not found',
        ]);
    }

    return $user;
}

// Build a query to find an active post by custom post_id or ObjectId.
function buildPostQuery(string $postId): array
{
    $query = [
        '$or' => [
            ['post_id' => $postId],
        ],
        'status' => 'active',
    ];

    if (preg_match('/^[a-f\d]{24}$/i', $postId) === 1) {
        $query['$or'][] = ['_id' => new MongoDB\BSON\ObjectId($postId)];
    }

    return $query;
}
