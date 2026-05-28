<?php

declare(strict_types=1);

session_start();

header('Content-Type: application/json');

require __DIR__ . '/../../connect_db/db.php';
require __DIR__ . '/../pagination.php';

function respond(int $statusCode, array $payload): void
{
    http_response_code($statusCode);
    echo json_encode($payload);
    exit;
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

$cursor = $events->find(
    $query,
    [
        'sort' => [
            'event_date' => 1,
            'event_time' => 1,
            'created_at' => -1,
        ],
        'skip' => $pagination['skip'],
        'limit' => $pagination['limit'],
    ]
);

$eventList = [];

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
