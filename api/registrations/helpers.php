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
            '$ne' => 'Deleted',
        ];
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
