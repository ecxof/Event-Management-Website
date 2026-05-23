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

function requireAdmin(): void
{
    if (!isset($_SESSION['user_id'])) {
        respond(401, [
            'success' => false,
            'message' => 'Please log in first',
        ]);
    }

    if (($_SESSION['role'] ?? '') !== 'admin') {
        respond(403, [
            'success' => false,
            'message' => 'Admin access required',
        ]);
    }
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

function getCapacity(array $data, bool $required): ?int
{
    if (!isset($data['capacity']) || trim((string) $data['capacity']) === '') {
        if ($required) {
            respond(422, [
                'success' => false,
                'message' => 'capacity is required',
            ]);
        }

        return null;
    }

    $capacity = filter_var($data['capacity'], FILTER_VALIDATE_INT);

    if ($capacity === false || $capacity < 1) {
        respond(422, [
            'success' => false,
            'message' => 'capacity must be a positive integer',
        ]);
    }

    return $capacity;
}

function getStatus(array $data, bool $required): ?string
{
    $status = trim((string) ($data['status'] ?? ''));

    if ($status === '') {
        if ($required) {
            respond(422, [
                'success' => false,
                'message' => 'status is required',
            ]);
        }

        return null;
    }

    $allowedStatuses = ['Upcoming', 'Full', 'Closed', 'Deleted'];

    if (!in_array($status, $allowedStatuses, true)) {
        respond(422, [
            'success' => false,
            'message' => 'status must be Upcoming, Full, Closed, or Deleted',
        ]);
    }

    return $status;
}

function buildEventQuery(string $eventId): array
{
    $query = [
        '$or' => [
            ['event_id' => $eventId],
        ],
    ];

    if (preg_match('/^[a-f\d]{24}$/i', $eventId) === 1) {
        $query['$or'][] = ['_id' => new MongoDB\BSON\ObjectId($eventId)];
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

function eventToArray(array|object $event): array
{
    return [
        'event_id' => (string) ($event['event_id'] ?? $event['_id']),
        'title' => (string) ($event['title'] ?? ''),
        'category' => (string) ($event['category'] ?? ''),
        'description' => (string) ($event['description'] ?? ''),
        'event_date' => (string) ($event['event_date'] ?? ''),
        'event_time' => (string) ($event['event_time'] ?? ''),
        'location' => (string) ($event['location'] ?? ''),
        'capacity' => isset($event['capacity']) ? (int) $event['capacity'] : 0,
        'image_url' => (string) ($event['image_url'] ?? ''),
        'status' => (string) ($event['status'] ?? 'Upcoming'),
        'created_by' => (string) ($event['created_by'] ?? ''),
    ];
}
