<?php

declare(strict_types=1);

session_start();

header('Content-Type: application/json');

require __DIR__ . '/../../connect_db/db.php';
require __DIR__ . '/helpers.php';

requireMethod('POST');

$userId = requireLogin();
$data = readRequestData();
$eventId = getRequiredEventId($data);

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
$eventStatus = (string) ($event['status'] ?? 'Upcoming');
$capacity = isset($event['capacity']) ? (int) $event['capacity'] : 0;

if ($eventStatus !== 'Upcoming') {
    respond(409, [
        'success' => false,
        'message' => 'This event is not open for registration',
    ]);
}

$joinedCount = $registrations->countDocuments([
    'event_id' => $storedEventId,
    'status' => 'joined',
]);

if ($capacity > 0 && $joinedCount >= $capacity) {
    $events->updateOne(
        ['event_id' => $storedEventId],
        [
            '$set' => [
                'status' => 'Full',
                'updated_at' => new MongoDB\BSON\UTCDateTime(),
            ],
        ]
    );

    respond(409, [
        'success' => false,
        'message' => 'This event is already full',
    ]);
}

$existingRegistration = $registrations->findOne([
    'user_id' => $userId,
    'event_id' => $storedEventId,
]);

if ($existingRegistration !== null && ($existingRegistration['status'] ?? '') === 'joined') {
    respond(409, [
        'success' => false,
        'message' => 'You have already joined this event',
    ]);
}

$now = new MongoDB\BSON\UTCDateTime();

if ($existingRegistration !== null) {
    $registrations->updateOne(
        [
            'registration_id' => (string) ($existingRegistration['registration_id'] ?? $existingRegistration['_id']),
        ],
        [
            '$set' => [
                'registration_date' => $now,
                'status' => 'joined',
                'updated_at' => $now,
            ],
            '$unset' => [
                'cancelled_at' => '',
            ],
        ]
    );

    $registration = $registrations->findOne([
        'registration_id' => (string) ($existingRegistration['registration_id'] ?? $existingRegistration['_id']),
    ]);
} else {
    $registrationObjectId = new MongoDB\BSON\ObjectId();
    $registrationId = (string) $registrationObjectId;

    $registration = [
        '_id' => $registrationObjectId,
        'registration_id' => $registrationId,
        'user_id' => $userId,
        'event_id' => $storedEventId,
        'registration_date' => $now,
        'status' => 'joined',
    ];

    $registrations->insertOne($registration);
}

$newJoinedCount = $registrations->countDocuments([
    'event_id' => $storedEventId,
    'status' => 'joined',
]);

if ($capacity > 0 && $newJoinedCount >= $capacity) {
    $events->updateOne(
        ['event_id' => $storedEventId],
        [
            '$set' => [
                'status' => 'Full',
                'updated_at' => new MongoDB\BSON\UTCDateTime(),
            ],
        ]
    );
}

respond(201, [
    'success' => true,
    'message' => 'Joined event successfully',
    'registration' => registrationToArray($registration),
    'event' => eventToSummary($event),
    'joined_count' => $newJoinedCount,
    'available_slots' => max(0, $capacity - $newJoinedCount),
]);
