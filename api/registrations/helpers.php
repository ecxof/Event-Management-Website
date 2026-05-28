<?php

declare(strict_types=1);

// Read JSON request bodies when present, otherwise support regular form posts.
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

// Send a JSON response and stop this API script.
function respond(int $statusCode, array $payload): void
{
    http_response_code($statusCode);
    echo json_encode($payload);
    exit;
}

// Ensure the endpoint uses the expected HTTP method.
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

// Read and validate the event_id needed by registration endpoints.
function getRequiredEventId(array $data): string
{
    $eventId = trim((string) ($data['event_id'] ?? ''));

    if ($eventId === '') {
        respond(422, [
            'success' => false,
            'message' => 'event_id is required',
        ]);
    }

    return $eventId;
}

// Build an event lookup query, optionally including soft-deleted events.
function buildEventQuery(string $eventId, bool $includeDeleted = false): array
{
    $query = [
        '$or' => [
            ['event_id' => $eventId],
        ],
    ];

    if (preg_match('/^[a-f\d]{24}$/i', $eventId) === 1) {
        $query['$or'][] = ['_id' => new MongoDB\BSON\ObjectId($eventId)];
    }

    if (!$includeDeleted) {
        $query['status'] = [
            '$nin' => ['Deleted', 'deleted', 'DELETED'],
        ];
        $query['deleted_at'] = [
            '$exists' => false,
        ];
    }

    return $query;
}

// Convert MongoDB date values into API-safe strings.
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

// Convert an event document into the compact event object used in registration lists.
function eventToSummary(array|object|null $event): ?array
{
    if ($event === null) {
        return null;
    }

    return [
        'event_id' => (string) ($event['event_id'] ?? $event['_id']),
        'title' => (string) ($event['title'] ?? ''),
        'category' => (string) ($event['category'] ?? ''),
        'event_date' => valueToString($event['event_date'] ?? null),
        'event_time' => valueToString($event['event_time'] ?? null),
        'location' => (string) ($event['location'] ?? ''),
        'capacity' => isset($event['capacity']) ? (int) $event['capacity'] : 0,
        'image_url' => (string) ($event['image_url'] ?? ''),
        'status' => (string) ($event['status'] ?? 'Upcoming'),
    ];
}

// Convert a registration document into the API response shape.
function registrationToArray(array|object $registration): array
{
    return [
        'registration_id' => (string) ($registration['registration_id'] ?? $registration['_id']),
        'user_id' => (string) ($registration['user_id'] ?? ''),
        'event_id' => (string) ($registration['event_id'] ?? ''),
        'registration_date' => valueToString($registration['registration_date'] ?? null),
        'status' => (string) ($registration['status'] ?? ''),
    ];
}
