<?php

declare(strict_types=1);

session_start();

header('Content-Type: application/json');

require __DIR__ . '/../../connect_db/db.php';
require __DIR__ . '/helpers.php';

// Cancel-registration endpoint: marks the logged-in user's active registration as cancelled.

requireMethod('POST');

$userId = requireLogin();
$data = readRequestData();
$eventId = getRequiredEventId($data);

$events = $db->selectCollection('events');
$registrations = $db->selectCollection('registrations');

// Include deleted events here so users can still cancel old registrations safely.
$event = $events->findOne(buildEventQuery($eventId, true));
$storedEventId = $event !== null ? (string) ($event['event_id'] ?? $event['_id']) : $eventId;

$registration = $registrations->findOne([
    'user_id' => $userId,
    'event_id' => $storedEventId,
    'status' => 'joined',
]);

if ($registration === null) {
    respond(404, [
        'success' => false,
        'message' => 'Active registration not found',
    ]);
}

$now = new MongoDB\BSON\UTCDateTime();

// Soft-cancel the registration instead of deleting it so history remains available.
$registrations->updateOne(
    [
        'registration_id' => (string) ($registration['registration_id'] ?? $registration['_id']),
    ],
    [
        '$set' => [
            'status' => 'cancelled',
            'cancelled_at' => $now,
            'updated_at' => $now,
        ],
    ]
);

// If a full event loses a participant, reopen it for future registrations.
if ($event !== null && ($event['status'] ?? '') === 'Full') {
    $events->updateOne(
        ['event_id' => $storedEventId],
        [
            '$set' => [
                'status' => 'Upcoming',
                'updated_at' => new MongoDB\BSON\UTCDateTime(),
            ],
        ]
    );
}

$updatedRegistration = $registrations->findOne([
    'registration_id' => (string) ($registration['registration_id'] ?? $registration['_id']),
]);

respond(200, [
    'success' => true,
    'message' => 'Registration cancelled successfully',
    'registration' => registrationToArray($updatedRegistration),
]);
