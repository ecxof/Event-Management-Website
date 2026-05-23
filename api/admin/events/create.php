<?php

declare(strict_types=1);

session_start();

header('Content-Type: application/json');

require __DIR__ . '/../../../connect_db/db.php';
require __DIR__ . '/helpers.php';

requireMethod('POST');
requireAdmin();

$data = readRequestData();

$title = getRequiredString($data, 'title');
$category = getRequiredString($data, 'category');
$description = getRequiredString($data, 'description');
$eventDate = getRequiredString($data, 'event_date');
$eventTime = getRequiredString($data, 'event_time');
$location = getRequiredString($data, 'location');
$capacity = getCapacity($data, true);
$imageUrl = getOptionalString($data, 'image_url');
$status = getStatus($data, false) ?? 'Upcoming';

if ($status === 'Deleted') {
    respond(422, [
        'success' => false,
        'message' => 'New events cannot start as Deleted',
    ]);
}

$events = $db->selectCollection('events');
$eventObjectId = new MongoDB\BSON\ObjectId();
$eventId = (string) $eventObjectId;

$eventDocument = [
    '_id' => $eventObjectId,
    'event_id' => $eventId,
    'title' => $title,
    'category' => $category,
    'description' => $description,
    'event_date' => $eventDate,
    'event_time' => $eventTime,
    'location' => $location,
    'capacity' => $capacity,
    'image_url' => $imageUrl,
    'status' => $status,
    'created_by' => (string) $_SESSION['user_id'],
    'created_at' => new MongoDB\BSON\UTCDateTime(),
];

$events->insertOne($eventDocument);

respond(201, [
    'success' => true,
    'message' => 'Event created successfully',
    'event' => eventToArray($eventDocument),
]);
