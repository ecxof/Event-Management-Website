<?php

declare(strict_types=1);

// Database seed script.
//
// Rebuilds every collection this project reads and writes, using the exact
// document shape the API endpoints expect, then fills them with the sample
// content in seed_data.json so the site is usable straight after a fresh
// MongoDB setup.
//
// Run it from the project root with the CLI PHP that has the mongodb extension:
//
//     php scripts/seed_database.php
//
// The connection string and database name come from connect_db/config.php, the
// same file the website itself uses.

if (PHP_SAPI !== 'cli') {
    die('This script can only be run from the command line.');
}

require __DIR__ . '/../connect_db/db.php';

$force = in_array('--force', $argv, true);

// Every collection touched by the API endpoints.
$collectionNames = [
    'users',
    'events',
    'registrations',
    'posts',
    'postLikes',
    'postComments',
    'postShares',
];

// Load the seed content. Keeping it in JSON leaves this file as logic only.
$seedFile = __DIR__ . '/seed_data.json';
$seedJson = @file_get_contents($seedFile);

if ($seedJson === false) {
    die("Cannot read seed data file: {$seedFile}\n");
}

$seedData = json_decode($seedJson, true);

if (!is_array($seedData)) {
    die("Seed data file is not valid JSON: {$seedFile}\n");
}

// Never overwrite a database that already holds records unless it was asked for.
$existingCounts = [];

foreach ($collectionNames as $collectionName) {
    $count = $db->selectCollection($collectionName)->countDocuments([]);

    if ($count > 0) {
        $existingCounts[$collectionName] = $count;
    }
}

if ($existingCounts !== [] && !$force) {
    echo "The target database already contains data:\n";

    foreach ($existingCounts as $collectionName => $count) {
        echo "  - {$collectionName}: {$count} documents\n";
    }

    echo "\nRe-run with --force to drop these collections and seed from scratch.\n";
    exit(1);
}

foreach ($collectionNames as $collectionName) {
    $db->selectCollection($collectionName)->drop();
}

$now = new DateTimeImmutable('now', new DateTimeZone('UTC'));

// The seed data stores ages as whole days back from now, so every run produces
// timestamps that stay sensibly spaced relative to the current date.
function daysAgo(DateTimeImmutable $base, int $days, int $extraHours = 0): MongoDB\BSON\UTCDateTime
{
    $moment = $base->modify('-' . max(0, $days) . ' days');

    if ($extraHours !== 0) {
        $moment = $moment->modify('+' . $extraHours . ' hours');
    }

    return new MongoDB\BSON\UTCDateTime($moment);
}

// ---------------------------------------------------------------------------
// users
// ---------------------------------------------------------------------------

$users = $db->selectCollection('users');
$userIds = [];
$userDocuments = [];

foreach ($seedData['users'] as $seed) {
    $objectId = new MongoDB\BSON\ObjectId();
    $userId = (string) $objectId;
    $userIds[$seed['key']] = $userId;
    $createdAt = daysAgo($now, $seed['registered_days_ago']);

    // Passwords go through password_hash() exactly like api/auth/register.php,
    // so api/auth/login.php can verify them with password_verify().
    // avatar_url stays empty because the frontend falls back to its own default image.
    $userDocuments[] = [
        '_id' => $objectId,
        'user_id' => $userId,
        'username' => $seed['username'],
        'email' => $seed['email'],
        'password' => password_hash($seed['password'], PASSWORD_DEFAULT),
        'role' => $seed['role'],
        'anime_interest' => $seed['anime_interest'],
        'telephone' => $seed['telephone'],
        'avatar_url' => '',
        'created_at' => $createdAt,
        'updated_at' => $createdAt,
    ];
}

$users->insertMany($userDocuments);

// ---------------------------------------------------------------------------
// events and registrations
// ---------------------------------------------------------------------------

// joined/cancelled hold user keys, and the registration documents are generated
// from them, so the joined counts shown on event cards always match the
// registrations collection.
$events = $db->selectCollection('events');
$registrations = $db->selectCollection('registrations');
$eventDocuments = [];
$registrationDocuments = [];

foreach ($seedData['events'] as $seed) {
    $objectId = new MongoDB\BSON\ObjectId();
    $eventId = (string) $objectId;
    $createdDaysAgo = $seed['created_days_ago'];

    // An event seeded at capacity has to carry the same Full status that
    // api/registrations/join.php would have written.
    $status = $seed['status'] ?? (count($seed['joined']) >= $seed['capacity'] ? 'Full' : 'Upcoming');

    $eventDocument = [
        '_id' => $objectId,
        'event_id' => $eventId,
        'title' => $seed['title'],
        'category' => $seed['category'],
        'description' => $seed['description'],
        'event_date' => $seed['event_date'],
        'event_time' => $seed['event_time'],
        'location' => $seed['location'],
        'capacity' => $seed['capacity'],
        'image_url' => '',
        'status' => $status,
        'created_by' => $userIds['admin'],
        'created_at' => daysAgo($now, $createdDaysAgo),
    ];

    // Soft-deleted events keep the audit fields written by api/admin/events/delete.php.
    if ($status === 'Deleted') {
        $eventDocument['deleted_at'] = daysAgo($now, $createdDaysAgo - 7);
        $eventDocument['deleted_by'] = $userIds['admin'];
    }

    $eventDocuments[] = $eventDocument;

    $offset = 1;

    foreach ($seed['joined'] as $userKey) {
        $registrationObjectId = new MongoDB\BSON\ObjectId();

        $registrationDocuments[] = [
            '_id' => $registrationObjectId,
            'registration_id' => (string) $registrationObjectId,
            'user_id' => $userIds[$userKey],
            'event_id' => $eventId,
            'registration_date' => daysAgo($now, $createdDaysAgo - $offset),
            'status' => 'joined',
        ];

        $offset++;
    }

    // Cancelled registrations stay in the collection so the join endpoint can
    // reactivate them instead of inserting a duplicate.
    foreach ($seed['cancelled'] as $userKey) {
        $registrationObjectId = new MongoDB\BSON\ObjectId();
        $cancelledAt = daysAgo($now, $createdDaysAgo - $offset - 2);

        $registrationDocuments[] = [
            '_id' => $registrationObjectId,
            'registration_id' => (string) $registrationObjectId,
            'user_id' => $userIds[$userKey],
            'event_id' => $eventId,
            'registration_date' => daysAgo($now, $createdDaysAgo - $offset),
            'status' => 'cancelled',
            'cancelled_at' => $cancelledAt,
            'updated_at' => $cancelledAt,
        ];

        $offset++;
    }
}

$events->insertMany($eventDocuments);
$registrations->insertMany($registrationDocuments);

// ---------------------------------------------------------------------------
// posts, likes, comments, and shares
// ---------------------------------------------------------------------------

$posts = $db->selectCollection('posts');
$postLikes = $db->selectCollection('postLikes');
$postComments = $db->selectCollection('postComments');
$postShares = $db->selectCollection('postShares');

$postIds = [];
$postDocuments = [];
$likeDocuments = [];
$shareDocuments = [];
$commentDocuments = [];

foreach ($seedData['posts'] as $seed) {
    $objectId = new MongoDB\BSON\ObjectId();
    $postId = (string) $objectId;
    $postIds[$seed['key']] = $postId;
    $createdDaysAgo = $seed['created_days_ago'];
    $createdAt = daysAgo($now, $createdDaysAgo);
    $status = $seed['status'] ?? 'active';

    $postDocument = [
        '_id' => $objectId,
        'post_id' => $postId,
        'user_id' => $userIds[$seed['author']],
        'title' => $seed['title'],
        'content' => $seed['content'],
        'image_url' => '',
        'created_at' => $createdAt,
        'updated_at' => $createdAt,
        'status' => $status,
    ];

    // Soft-deleted posts keep the audit fields written by api/posts/delete.php.
    if ($status === 'deleted') {
        $deletedAt = daysAgo($now, $createdDaysAgo - 1);
        $postDocument['updated_at'] = $deletedAt;
        $postDocument['deleted_at'] = $deletedAt;
        $postDocument['deleted_by'] = $userIds[$seed['author']];
    }

    $postDocuments[] = $postDocument;

    $offset = 1;

    foreach ($seed['likes'] as $userKey) {
        $likeObjectId = new MongoDB\BSON\ObjectId();

        $likeDocuments[] = [
            '_id' => $likeObjectId,
            'like_id' => (string) $likeObjectId,
            'post_id' => $postId,
            'user_id' => $userIds[$userKey],
            'created_at' => daysAgo($now, $createdDaysAgo, $offset),
        ];

        $offset++;
    }

    foreach ($seed['shares'] as $userKey) {
        $shareObjectId = new MongoDB\BSON\ObjectId();

        $shareDocuments[] = [
            '_id' => $shareObjectId,
            'share_id' => (string) $shareObjectId,
            'post_id' => $postId,
            'user_id' => $userIds[$userKey],
            'created_at' => daysAgo($now, $createdDaysAgo, $offset),
        ];

        $offset++;
    }
}

foreach ($seedData['comments'] as $seed) {
    $commentObjectId = new MongoDB\BSON\ObjectId();
    $createdAt = daysAgo($now, $seed['created_days_ago']);

    $commentDocuments[] = [
        '_id' => $commentObjectId,
        'comment_id' => (string) $commentObjectId,
        'post_id' => $postIds[$seed['post']],
        'user_id' => $userIds[$seed['author']],
        'content' => $seed['content'],
        'created_at' => $createdAt,
        'updated_at' => $createdAt,
        'status' => 'active',
    ];
}

// insertMany() rejects an empty array, so skip anything the seed data left empty.
foreach ([[$posts, $postDocuments], [$postLikes, $likeDocuments], [$postComments, $commentDocuments], [$postShares, $shareDocuments]] as [$collection, $documents]) {
    if ($documents !== []) {
        $collection->insertMany($documents);
    }
}

// ---------------------------------------------------------------------------
// indexes
// ---------------------------------------------------------------------------

// The unique email index matches the one api/auth/register.php creates; the rest
// support the lookups and sorts the list endpoints run on every page load.
$users->createIndex(['email' => 1], ['unique' => true]);
$users->createIndex(['user_id' => 1], ['unique' => true]);

$events->createIndex(['event_id' => 1], ['unique' => true]);
$events->createIndex(['status' => 1, 'event_date' => 1, 'event_time' => 1]);

$registrations->createIndex(['registration_id' => 1], ['unique' => true]);
$registrations->createIndex(['event_id' => 1, 'status' => 1]);
$registrations->createIndex(['user_id' => 1, 'event_id' => 1]);

$posts->createIndex(['post_id' => 1], ['unique' => true]);
$posts->createIndex(['status' => 1, 'created_at' => -1]);
$posts->createIndex(['user_id' => 1, 'status' => 1, 'created_at' => -1]);

$postLikes->createIndex(['post_id' => 1, 'user_id' => 1], ['unique' => true]);
$postLikes->createIndex(['user_id' => 1, 'created_at' => -1]);

$postComments->createIndex(['post_id' => 1, 'status' => 1, 'created_at' => 1]);

$postShares->createIndex(['post_id' => 1]);

// ---------------------------------------------------------------------------
// summary
// ---------------------------------------------------------------------------

echo "Database seeded successfully.\n\n";

foreach ($collectionNames as $collectionName) {
    printf("  %-14s %d documents\n", $collectionName, $db->selectCollection($collectionName)->countDocuments([]));
}

echo "\nSample accounts:\n";

foreach ($seedData['users'] as $seed) {
    printf("  %-5s  %-36s  %s\n", $seed['role'], $seed['email'], $seed['password']);
}

echo "\nChange these passwords before showing the site to anyone outside the group.\n";
