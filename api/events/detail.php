<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/session.php';

header('Content-Type: application/json');

require __DIR__ . '/../../connect_db/db.php';

// Event-detail endpoint: returns one visible event with capacity and current join state.

// Send a JSON response and stop this API script.
function respond(int $statusCode, array $payload): void
{
    http_response_code($statusCode);
    echo json_encode($payload);
    exit;
}

// Convert MongoDB date values into frontend-safe strings.
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

// Find an event by event_id or ObjectId while excluding deleted events.
function buildEventQuery(string $eventId): array
{
    $query = [
        '$or' => [
            ['event_id' => $eventId],
        ],
        'status' => [
            '$nin' => ['Deleted', 'deleted', 'DELETED'],
        ],
        'deleted_at' => [
            '$exists' => false,
        ],
    ];

    if (preg_match('/^[a-f\d]{24}$/i', $eventId) === 1) {
        $query['$or'][] = ['_id' => new MongoDB\BSON\ObjectId($eventId)];
    }

    return $query;
}

// Convert an event document into the detail object used by the event detail view.
function eventToDetail(array|object $event, int $joinedCount, bool $isJoined): array
{
    $capacity = isset($event['capacity']) ? (int) $event['capacity'] : 0;

    return [
        'event_id' => (string) ($event['event_id'] ?? $event['_id']),
        'title' => (string) ($event['title'] ?? ''),
        'category' => (string) ($event['category'] ?? ''),
        'description' => (string) ($event['description'] ?? ''),
        'event_date' => valueToString($event['event_date'] ?? null),
        'event_time' => valueToString($event['event_time'] ?? null),
        'location' => (string) ($event['location'] ?? ''),
        'capacity' => $capacity,
        'joined_count' => $joinedCount,
        'available_slots' => max(0, $capacity - $joinedCount),
        'image_url' => (string) ($event['image_url'] ?? ''),
        'status' => (string) ($event['status'] ?? 'Upcoming'),
        'created_by' => (string) ($event['created_by'] ?? ''),
        'created_at' => valueToString($event['created_at'] ?? null),
        'is_joined' => $isJoined,
    ];
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    respond(405, [
        'success' => false,
        'message' => 'Method not allowed',
    ]);
}

$eventId = trim((string) ($_GET['event_id'] ?? ''));

if ($eventId === '') {
    respond(422, [
        'success' => false,
        'message' => 'event_id is required',
    ]);
}

$events = $db->selectCollection('events');
$registrations = $db->selectCollection('registrations');

$event = $events->findOne(buildEventQuery($eventId));

if ($event === null) {
    respond(404, [
        'success' => false,
        'message' => 'Event not found',
    ]);
}

$storedEventId = (string) ($event['event_id'] ?? $event['_id']);

// Count active registrations to show available capacity.
$joinedCount = $registrations->countDocuments([
    'event_id' => $storedEventId,
    'status' => 'joined',
]);

$isJoined = false;

// Logged-in users see whether they have already joined this event.
if (isset($_SESSION['user_id'])) {
    $isJoined = $registrations->countDocuments([
        'event_id' => $storedEventId,
        'user_id' => (string) $_SESSION['user_id'],
        'status' => 'joined',
    ]) > 0;
}

respond(200, [
    'success' => true,
    'event' => eventToDetail($event, $joinedCount, $isJoined),
]);
