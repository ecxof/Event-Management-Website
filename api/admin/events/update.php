<?php

declare(strict_types=1);

session_start();

header('Content-Type: application/json');

require __DIR__ . '/../../../connect_db/db.php';
require __DIR__ . '/helpers.php';

// Admin update-event endpoint: edits event fields for an existing event.

requireMethod('POST');
requireAdmin();

$data = readRequestData();
$eventId = getRequiredString($data, 'event_id');

$updates = [];

// Text fields are optional for partial updates, but non-image fields cannot be empty when provided.
$stringFields = [
    'title',
    'category',
    'description',
    'event_date',
    'event_time',
    'location',
    'image_url',
];

foreach ($stringFields as $field) {
    if (array_key_exists($field, $data)) {
        $value = trim((string) $data[$field]);

        if ($field !== 'image_url' && $value === '') {
            respond(422, [
                'success' => false,
                'message' => "{$field} cannot be empty",
            ]);
        }

        $updates[$field] = $value;
    }
}

// Capacity and status have stricter validation handled by helper functions.
if (array_key_exists('capacity', $data)) {
    $updates['capacity'] = getCapacity($data, true);
}

if (array_key_exists('status', $data)) {
    $updates['status'] = getStatus($data, true);
}

if ($updates === []) {
    respond(422, [
        'success' => false,
        'message' => 'No event fields provided to update',
    ]);
}

// updated_at records when the admin last changed this event.
$updates['updated_at'] = new MongoDB\BSON\UTCDateTime();

$events = $db->selectCollection('events');
$result = $events->updateOne(
    buildEventQuery($eventId),
    [
        '$set' => $updates,
    ]
);

if ($result->getMatchedCount() === 0) {
    respond(404, [
        'success' => false,
        'message' => 'Event not found',
    ]);
}

$updatedEvent = $events->findOne(buildEventQuery($eventId));

respond(200, [
    'success' => true,
    'message' => 'Event updated successfully',
    'event' => eventToArray($updatedEvent),
]);
