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

// Events are listed in three groups, so the first page always opens on the events a
// student can still act on:
//
//   0. still to come and open for registration (Upcoming or Full), soonest first
//   1. still to come but Closed, soonest first
//   2. already happened, newest first
//
// event_date is stored as a YYYY-MM-DD string, so comparing it against today's date as
// a string is enough to tell a past event from an upcoming one. Comparing dates only,
// without the time, keeps an event that is running today out of the past group for the
// whole day.
$today = (new DateTimeImmutable('now'))->format('Y-m-d');

$cursor = $events->aggregate([
    [
        '$match' => $query,
    ],
    [
        // The stored date and time are fixed-width strings, so joining them produces a
        // valid ISO 8601 value that can be turned into a real timestamp. Anything
        // malformed falls back to the epoch and sorts to the end of its own group.
        '$addFields' => [
            'event_moment' => [
                '$dateFromString' => [
                    'dateString' => [
                        '$concat' => [
                            '$event_date',
                            'T',
                            ['$ifNull' => ['$event_time', '00:00']],
                            ':00Z',
                        ],
                    ],
                    'onError' => new MongoDB\BSON\UTCDateTime(0),
                    'onNull' => new MongoDB\BSON\UTCDateTime(0),
                ],
            ],
        ],
    ],
    [
        '$addFields' => [
            'sort_rank' => [
                '$switch' => [
                    'branches' => [
                        [
                            'case' => ['$lt' => ['$event_date', $today]],
                            'then' => 2,
                        ],
                        [
                            'case' => ['$eq' => ['$status', 'Closed']],
                            'then' => 1,
                        ],
                    ],
                    'default' => 0,
                ],
            ],
            // Past events are negated so that one ascending sort still reads newest
            // first for them, while the two upcoming groups stay soonest first.
            'sort_key' => [
                '$cond' => [
                    'if' => ['$lt' => ['$event_date', $today]],
                    'then' => ['$multiply' => [['$toLong' => '$event_moment'], -1]],
                    'else' => ['$toLong' => '$event_moment'],
                ],
            ],
        ],
    ],
    [
        '$sort' => [
            'sort_rank' => 1,
            'sort_key' => 1,
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
