<?php

declare(strict_types=1);

session_start();

header('Content-Type: application/json');

require __DIR__ . '/../../../connect_db/db.php';
require __DIR__ . '/helpers.php';

requireMethod('POST');
requireAdmin();

$data = readRequestData();
$eventId = getRequiredString($data, 'event_id');

$events = $db->selectCollection('events');
$result = $events->updateOne(
    buildEventQuery($eventId),
    [
        '$set' => [
            'status' => 'Deleted',
            'deleted_at' => new MongoDB\BSON\UTCDateTime(),
            'deleted_by' => (string) $_SESSION['user_id'],
        ],
    ]
);

if ($result->getMatchedCount() === 0) {
    respond(404, [
        'success' => false,
        'message' => 'Event not found',
    ]);
}

respond(200, [
    'success' => true,
    'message' => 'Event deleted successfully',
    'event_id' => $eventId,
]);
