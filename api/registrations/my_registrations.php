<?php

declare(strict_types=1);

session_start();

header('Content-Type: application/json');

require __DIR__ . '/../../connect_db/db.php';
require __DIR__ . '/helpers.php';

requireMethod('GET');

$userId = requireLogin();

$events = $db->selectCollection('events');
$registrations = $db->selectCollection('registrations');

$cursor = $registrations->find(
    [
        'user_id' => $userId,
    ],
    [
        'sort' => [
            'registration_date' => -1,
        ],
    ]
);

$registrationList = [];

foreach ($cursor as $registration) {
    $eventId = (string) ($registration['event_id'] ?? '');
    $event = $eventId !== '' ? $events->findOne(buildEventQuery($eventId, true)) : null;

    $registrationList[] = [
        ...registrationToArray($registration),
        'event' => eventToSummary($event),
    ];
}

respond(200, [
    'success' => true,
    'registrations' => $registrationList,
]);
