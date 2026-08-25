<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/session.php';

header('Content-Type: application/json');

require __DIR__ . '/../../connect_db/db.php';
require __DIR__ . '/../pagination.php';

// Event-list endpoint: returns paginated visible events with joined counts.

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

// Convert an event document into the compact card object used by list views.
function eventToListItem(array|object $event, int $joinedCount): array
{
    $capacity = isset($event['capacity']) ? (int) $event['capacity'] : 0;

    return [
        'event_id' => (string) ($event['event_id'] ?? $event['_id']),
        'title' => (string) ($event['title'] ?? ''),
        'category' => (string) ($event['category'] ?? ''),
        'event_date' => valueToString($event['event_date'] ?? null),
        'event_time' => valueToString($event['event_time'] ?? null),
        'location' => (string) ($event['location'] ?? ''),
        'capacity' => $capacity,
        'joined_count' => $joinedCount,
        'available_slots' => max(0, $capacity - $joinedCount),
        'image_url' => (string) ($event['image_url'] ?? ''),
        'status' => (string) ($event['status'] ?? 'Upcoming'),
    ];
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    respond(405, [
        'success' => false,
        'message' => 'Method not allowed',
    ]);
}

$events = $db->selectCollection('events');
$registrations = $db->selectCollection('registrations');
$pagination = readPaginationParams();

// Hide soft-deleted events from user and admin list pages.
$query = [
    'status' => [
        '$nin' => ['Deleted', 'deleted', 'DELETED'],
    ],
    'deleted_at' => [
        '$exists' => false,
    ],
];
$total = $events->countDocuments($query);
$pagination = clampPagination($pagination, $total);

// Events that already happened are pushed below the ones still to come, so the first
// page opens on upcoming events instead of the oldest archived ones. Within each of
// those two groups the order is still by date/time, with newer created records used as
// a tie breaker.
//
// event_date is stored as a YYYY-MM-DD string, so comparing it against today's date as
// a string is enough to tell a past event from an upcoming one. Comparing dates only,
// without the time, keeps an event that is running today at the top for the whole day.
$today = (new DateTimeImmutable('now'))->format('Y-m-d');

$cursor = $events->aggregate([
    [
        '$match' => $query,
    ],
    [
        '$addFields' => [
            'is_past' => [
                '$lt' => ['$event_date', $today],
            ],
        ],
    ],
    [
        '$sort' => [
            'is_past' => 1,
            'event_date' => 1,
            'event_time' => 1,
            'created_at' => -1,
        ],
    ],
    [
        '$skip' => $pagination['skip'],
    ],
    [
        '$limit' => $pagination['limit'],
    ],
]);

$eventList = [];

// Count joined registrations per event so the UI can show slots and full status.
foreach ($cursor as $event) {
    $eventId = (string) ($event['event_id'] ?? $event['_id']);
    $joinedCount = $registrations->countDocuments([
        'event_id' => $eventId,
        'status' => 'joined',
    ]);

    $eventList[] = eventToListItem($event, $joinedCount);
}

respond(200, [
    'success' => true,
    'events' => $eventList,
    'pagination' => paginationMeta($pagination['page'], $pagination['limit'], $total),
]);
