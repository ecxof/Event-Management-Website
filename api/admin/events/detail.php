<?php

declare(strict_types=1);

session_start();

header('Content-Type: application/json');

require __DIR__ . '/../../../connect_db/db.php';
require __DIR__ . '/helpers.php';

// Admin event-detail endpoint: returns event details plus the participant list.

requireMethod('GET');
requireAdmin();

$eventId = trim((string) ($_GET['event_id'] ?? ''));

if ($eventId === '') {
    respond(422, [
        'success' => false,
        'message' => 'event_id is required',
    ]);
}

$events = $db->selectCollection('events');
$registrations = $db->selectCollection('registrations');
$users = $db->selectCollection('users');

$event = $events->findOne(buildEventQuery($eventId));

if ($event === null) {
    respond(404, [
        'success' => false,
        'message' => 'Event not found',
    ]);
}

$storedEventId = (string) ($event['event_id'] ?? $event['_id']);
$participants = [];

// Participants are sorted by registration time so the admin sees signup order.
$cursor = $registrations->find(
    [
        'event_id' => $storedEventId,
        'status' => 'joined',
    ],
    [
        'sort' => [
            'registration_date' => 1,
        ],
    ]
);

foreach ($cursor as $registration) {
    $userId = (string) ($registration['user_id'] ?? '');
    $user = $userId !== '' ? $users->findOne(['user_id' => $userId]) : null;

    // Join registration data with the user profile fields needed in the admin table.
    $participants[] = [
        'registration_id' => (string) ($registration['registration_id'] ?? $registration['_id']),
        'user_id' => $userId,
        'username' => $user !== null ? (string) ($user['username'] ?? '') : '',
        'email' => $user !== null ? (string) ($user['email'] ?? '') : '',
        'telephone' => $user !== null && isset($user['telephone']) ? (string) $user['telephone'] : '',
        'registration_date' => valueToString($registration['registration_date'] ?? null),
        'status' => (string) ($registration['status'] ?? ''),
    ];
}

respond(200, [
    'success' => true,
    'event' => [
        ...eventToArray($event),
        'joined_count' => count($participants),
        'participants' => $participants,
    ],
]);
