<?php

declare(strict_types=1);

session_start();

header('Content-Type: application/json');

require __DIR__ . '/../../connect_db/db.php';
require __DIR__ . '/../pagination.php';
require __DIR__ . '/helpers.php';

requireMethod('GET');

$userId = requireLogin();

$events = $db->selectCollection('events');
$registrations = $db->selectCollection('registrations');
$pagination = readPaginationParams();
$query = [
    'user_id' => $userId,
    'status' => 'joined',
];

$cursor = $registrations->find(
    $query,
    [
        'sort' => [
            'registration_date' => -1,
        ],
    ]
);

$registrationList = [];

foreach ($cursor as $registration) {
    $eventId = (string) ($registration['event_id'] ?? '');
    $event = $eventId !== '' ? $events->findOne(buildEventQuery($eventId)) : null;

    if ($event === null) {
        continue;
    }

    $registrationList[] = [
        ...registrationToArray($registration),
        'event' => eventToSummary($event),
    ];
}

$total = count($registrationList);
$pagination = clampPagination($pagination, $total);
$registrationList = array_slice($registrationList, $pagination['skip'], $pagination['limit']);

respond(200, [
    'success' => true,
    'registrations' => $registrationList,
    'pagination' => paginationMeta($pagination['page'], $pagination['limit'], $total),
]);
