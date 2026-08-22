<?php

declare(strict_types=1);

// Database seed script.
//
// Rebuilds every collection this project reads and writes, using the exact
// document shape the API endpoints expect, then fills them with sample data so
// the site is usable straight after a fresh MongoDB setup.
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

// Build a BSON timestamp relative to the moment the script runs.
function stamp(DateTimeImmutable $base, string $modify): MongoDB\BSON\UTCDateTime
{
    return new MongoDB\BSON\UTCDateTime($base->modify($modify));
}

// ---------------------------------------------------------------------------
// users
// ---------------------------------------------------------------------------

// Passwords are written through password_hash(), exactly like api/auth/register.php,
// so api/auth/login.php can verify them with password_verify().
$userSeeds = [
    'admin' => [
        'username' => 'Event Admin',
        'email' => 'admin@taylors.edu.my',
        'password' => 'Admin1234',
        'role' => 'admin',
        'anime_interest' => 'Event Management',
        'telephone' => 60123456789,
        'registered' => '-150 days',
    ],
    'amir' => [
        'username' => 'Amir Reza',
        'email' => 'amir.reza@sd.taylors.edu.my',
        'password' => 'Student1234',
        'role' => 'user',
        'anime_interest' => 'Attack on Titan',
        'telephone' => 60129876543,
        'registered' => '-140 days',
    ],
    'junyi' => [
        'username' => 'Zhang JunYi',
        'email' => 'zhang.junyi@sd.taylors.edu.my',
        'password' => 'Student1234',
        'role' => 'user',
        'anime_interest' => 'Jujutsu Kaisen',
        'telephone' => 60125550111,
        'registered' => '-138 days',
    ],
    'shuhao' => [
        'username' => 'Yang Shuhao',
        'email' => 'yang.shuhao@sd.taylors.edu.my',
        'password' => 'Student1234',
        'role' => 'user',
        'anime_interest' => 'One Piece',
        'telephone' => 60125550222,
        'registered' => '-135 days',
    ],
    'shurui' => [
        'username' => 'Yang Shurui',
        'email' => 'yang.shurui@sd.taylors.edu.my',
        'password' => 'Student1234',
        'role' => 'user',
        'anime_interest' => 'Demon Slayer',
        'telephone' => 60125550333,
        'registered' => '-133 days',
    ],
    'meiling' => [
        'username' => 'Tan Mei Ling',
        'email' => 'tan.meiling@sd.taylors.edu.my',
        'password' => 'Student1234',
        'role' => 'user',
        'anime_interest' => 'Spy x Family',
        'telephone' => 60125550444,
        'registered' => '-96 days',
    ],
    'arif' => [
        'username' => 'Muhammad Arif',
        'email' => 'muhammad.arif@sd.taylors.edu.my',
        'password' => 'Student1234',
        'role' => 'user',
        'anime_interest' => 'Naruto',
        'telephone' => 60125550555,
        'registered' => '-74 days',
    ],
    'priya' => [
        'username' => 'Priya Nair',
        'email' => 'priya.nair@sd.taylors.edu.my',
        'password' => 'Student1234',
        'role' => 'user',
        'anime_interest' => 'Your Name',
        'telephone' => 60125550666,
        'registered' => '-52 days',
    ],
];

$users = $db->selectCollection('users');
$userIds = [];
$userDocuments = [];

foreach ($userSeeds as $key => $seed) {
    $objectId = new MongoDB\BSON\ObjectId();
    $userId = (string) $objectId;
    $userIds[$key] = $userId;
    $createdAt = stamp($now, $seed['registered']);

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

// joined/cancelled hold user keys; the registration documents are generated from them,
// so the joined counts shown on event cards always match the registrations collection.
$eventSeeds = [
    'cosplay-night' => [
        'title' => 'Anime Cosplay Night 2026',
        'category' => 'Cosplay',
        'description' => 'Dress up as your favourite anime character and walk the runway. Prizes for best costume, best group, and best handmade prop.',
        'event_date' => '2026-09-18',
        'event_time' => '19:00',
        'location' => "Grand Hall, Taylor's Lakeside Campus",
        'capacity' => 100,
        'created' => '-40 days',
        'joined' => ['amir', 'junyi', 'shuhao', 'meiling', 'arif'],
        'cancelled' => [],
    ],
    'manga-workshop' => [
        'title' => 'Manga Drawing Workshop',
        'category' => 'Workshop',
        'description' => 'A hands-on session covering panel layout, inking, and screentones. Materials are provided, no drawing experience needed.',
        'event_date' => '2026-09-26',
        'event_time' => '14:00',
        'location' => 'Design Studio, Block D',
        'capacity' => 30,
        'created' => '-36 days',
        'joined' => ['shurui', 'priya', 'junyi'],
        'cancelled' => ['arif'],
    ],
    'amv-festival' => [
        'title' => 'Anime Music Video Festival',
        'category' => 'Music',
        'description' => "A screening night for student-made AMVs, followed by live voting and a short editing talk from last year's winner.",
        'event_date' => '2026-10-03',
        'event_time' => '18:30',
        'location' => 'Auditorium 1',
        'capacity' => 5,
        'created' => '-30 days',
        'joined' => ['amir', 'junyi', 'shuhao', 'shurui', 'meiling'],
        'cancelled' => [],
    ],
    'culture-day' => [
        'title' => 'Japanese Culture Day',
        'category' => 'Culture',
        'description' => 'Try calligraphy, origami, and a tea ceremony demonstration, with a Japanese street-food corner running all afternoon.',
        'event_date' => '2026-10-11',
        'event_time' => '10:00',
        'location' => 'Student Life Centre',
        'capacity' => 80,
        'created' => '-27 days',
        'joined' => ['priya', 'arif'],
        'cancelled' => [],
    ],
    'movie-marathon' => [
        'title' => 'Anime Movie Marathon',
        'category' => 'Screening',
        'description' => 'An overnight screening of three classic anime films, with free popcorn and a discussion session between each showing.',
        'event_date' => '2026-07-25',
        'event_time' => '20:00',
        'location' => 'Lecture Theatre 3',
        'capacity' => 50,
        'status' => 'Closed',
        'created' => '-70 days',
        'updated' => '-26 days',
        'joined' => ['amir', 'meiling', 'priya'],
        'cancelled' => [],
    ],
    'photography' => [
        'title' => 'Cosplay Photography Session',
        'category' => 'Photography',
        'description' => 'Bring your costume and camera to a guided outdoor shoot around the lake. Lighting gear and a photographer mentor are on site.',
        'event_date' => '2026-10-24',
        'event_time' => '16:00',
        'location' => 'Campus Lakeside Park',
        'capacity' => 25,
        'created' => '-21 days',
        'joined' => ['shuhao'],
        'cancelled' => [],
    ],
    'anime-quiz' => [
        'title' => 'Anime Quiz Championship',
        'category' => 'Competition',
        'description' => 'Teams of three compete across four rounds of anime trivia, from opening themes to obscure side characters.',
        'event_date' => '2026-11-07',
        'event_time' => '15:00',
        'location' => 'Seminar Room 2',
        'capacity' => 40,
        'created' => '-14 days',
        'joined' => ['junyi', 'shurui', 'arif'],
        'cancelled' => ['meiling'],
    ],
    'game-night' => [
        'title' => 'Otaku Game Night',
        'category' => 'Games',
        'description' => 'A casual evening of anime fighting games and rhythm games on the big screen.',
        'event_date' => '2026-11-21',
        'event_time' => '18:00',
        'location' => 'Recreation Room',
        'capacity' => 60,
        'status' => 'Deleted',
        'created' => '-11 days',
        'deleted' => '-4 days',
        'joined' => [],
        'cancelled' => [],
    ],
];

$events = $db->selectCollection('events');
$registrations = $db->selectCollection('registrations');
$eventDocuments = [];
$registrationDocuments = [];

foreach ($eventSeeds as $seed) {
    $objectId = new MongoDB\BSON\ObjectId();
    $eventId = (string) $objectId;

    $joinedCount = count($seed['joined']);
    $capacity = $seed['capacity'];

    // A seeded event that is already at capacity has to carry the same Full status
    // that api/registrations/join.php would have written.
    $status = $seed['status'] ?? ($joinedCount >= $capacity ? 'Full' : 'Upcoming');

    $eventDocument = [
        '_id' => $objectId,
        'event_id' => $eventId,
        'title' => $seed['title'],
        'category' => $seed['category'],
        'description' => $seed['description'],
        'event_date' => $seed['event_date'],
        'event_time' => $seed['event_time'],
        'location' => $seed['location'],
        'capacity' => $capacity,
        'image_url' => '',
        'status' => $status,
        'created_by' => $userIds['admin'],
        'created_at' => stamp($now, $seed['created']),
    ];

    if (isset($seed['updated'])) {
        $eventDocument['updated_at'] = stamp($now, $seed['updated']);
    }

    // Soft-deleted events keep the audit fields written by api/admin/events/delete.php.
    if (isset($seed['deleted'])) {
        $eventDocument['deleted_at'] = stamp($now, $seed['deleted']);
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
            'registration_date' => stamp($now, $seed['created'] . ' +' . $offset . ' days'),
            'status' => 'joined',
        ];

        $offset++;
    }

    // Cancelled registrations stay in the collection so the join endpoint can reactivate them.
    foreach ($seed['cancelled'] as $userKey) {
        $registrationObjectId = new MongoDB\BSON\ObjectId();
        $cancelledAt = stamp($now, $seed['created'] . ' +' . ($offset + 2) . ' days');

        $registrationDocuments[] = [
            '_id' => $registrationObjectId,
            'registration_id' => (string) $registrationObjectId,
            'user_id' => $userIds[$userKey],
            'event_id' => $eventId,
            'registration_date' => stamp($now, $seed['created'] . ' +' . $offset . ' days'),
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

$postSeeds = [
    'countdown' => [
        'author' => 'amir',
        'title' => 'Cosplay Night countdown has started',
        'content' => 'Three weeks left before Cosplay Night and my armour is still half painted. If anyone has spare EVA foam, I will trade snacks for it.',
        'created' => '-25 days',
        'likes' => ['junyi', 'shuhao', 'meiling', 'priya'],
        'shares' => ['junyi', 'arif'],
    ],
    'supplies' => [
        'author' => 'junyi',
        'title' => 'What to bring to the manga workshop',
        'content' => 'The studio provides pens and paper, but bring your own eraser and a reference sheet of the character you want to draw. It saves a lot of time.',
        'created' => '-19 days',
        'likes' => ['shurui', 'priya'],
        'shares' => ['shurui'],
    ],
    'amv-lineup' => [
        'author' => 'shuhao',
        'title' => 'AMV Festival lineup is looking strong',
        'content' => 'Watched a few of the submitted edits during rehearsal. The sync work this year is on another level, definitely worth getting a seat early.',
        'created' => '-12 days',
        'likes' => ['amir', 'junyi', 'shurui', 'meiling', 'arif'],
        'shares' => ['amir', 'meiling', 'priya'],
    ],
    'season' => [
        'author' => 'meiling',
        'title' => 'Best anime of the season?',
        'content' => 'Curious what everyone is watching right now. I have been rewatching Spy x Family between classes and it still holds up.',
        'created' => '-8 days',
        'likes' => ['priya', 'arif'],
        'shares' => [],
    ],
    'marathon-photos' => [
        'author' => 'priya',
        'title' => 'Photos from the movie marathon',
        'content' => 'Finally sorted through the shots from the overnight screening. Everyone looked exhausted by the third film but nobody left early.',
        'created' => '-5 days',
        'likes' => ['amir', 'meiling', 'shuhao'],
        'shares' => ['amir'],
    ],
    'old-notice' => [
        'author' => 'arif',
        'title' => 'Old club notice',
        'content' => 'Posted the wrong venue for the quiz night, please ignore this one.',
        'created' => '-30 days',
        'status' => 'deleted',
        'deleted' => '-29 days',
        'likes' => [],
        'shares' => [],
    ],
];

// Comments are listed separately so each one can carry its own author and timing.
$commentSeeds = [
    ['post' => 'countdown', 'author' => 'junyi', 'content' => 'I have a spare sheet of 10mm foam, it is yours. Bring the snacks.', 'created' => '-24 days'],
    ['post' => 'countdown', 'author' => 'meiling', 'content' => 'Please post progress photos, the helmet looked great last week.', 'created' => '-23 days'],
    ['post' => 'supplies', 'author' => 'priya', 'content' => 'Good call on the reference sheet, I completely forgot mine last time.', 'created' => '-18 days'],
    ['post' => 'amv-lineup', 'author' => 'shurui', 'content' => 'Which one used the Demon Slayer soundtrack? That transition was clean.', 'created' => '-11 days'],
    ['post' => 'amv-lineup', 'author' => 'amir', 'content' => 'Getting there an hour early, seats filled up fast last year.', 'created' => '-10 days'],
    ['post' => 'season', 'author' => 'arif', 'content' => 'Still working through One Piece, so ask me again in about two years.', 'created' => '-7 days'],
    ['post' => 'marathon-photos', 'author' => 'shuhao', 'content' => 'The group shot at 4am is going straight into the club recap.', 'created' => '-4 days'],
];

$posts = $db->selectCollection('posts');
$postLikes = $db->selectCollection('postLikes');
$postComments = $db->selectCollection('postComments');
$postShares = $db->selectCollection('postShares');

$postIds = [];
$postDocuments = [];
$likeDocuments = [];
$shareDocuments = [];
$commentDocuments = [];

foreach ($postSeeds as $key => $seed) {
    $objectId = new MongoDB\BSON\ObjectId();
    $postId = (string) $objectId;
    $postIds[$key] = $postId;
    $createdAt = stamp($now, $seed['created']);

    $postDocument = [
        '_id' => $objectId,
        'post_id' => $postId,
        'user_id' => $userIds[$seed['author']],
        'title' => $seed['title'],
        'content' => $seed['content'],
        'image_url' => '',
        'created_at' => $createdAt,
        'updated_at' => $createdAt,
        'status' => $seed['status'] ?? 'active',
    ];

    // Soft-deleted posts keep the audit fields written by api/posts/delete.php.
    if (isset($seed['deleted'])) {
        $deletedAt = stamp($now, $seed['deleted']);
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
            'created_at' => stamp($now, $seed['created'] . ' +' . $offset . ' hours'),
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
            'created_at' => stamp($now, $seed['created'] . ' +' . $offset . ' hours'),
        ];

        $offset++;
    }
}

foreach ($commentSeeds as $seed) {
    $commentObjectId = new MongoDB\BSON\ObjectId();
    $createdAt = stamp($now, $seed['created']);

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

foreach ($userSeeds as $seed) {
    printf("  %-5s  %-34s  %s\n", $seed['role'], $seed['email'], $seed['password']);
}

echo "\nChange these passwords before showing the site to anyone outside the group.\n";
