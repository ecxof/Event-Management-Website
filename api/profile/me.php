<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/session.php';

header('Content-Type: application/json');

require __DIR__ . '/../../connect_db/db.php';
require __DIR__ . '/helpers.php';

// Current-profile endpoint: returns the logged-in user's profile data.

requireMethod('GET');

$userId = requireLogin();
$user = requireCurrentUser($db, $userId);

respond(200, [
    'success' => true,
    'profile' => userToProfile($user),
]);
