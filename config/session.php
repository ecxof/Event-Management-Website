<?php

// Centralized session bootstrap.
//
// Applies a consistent session-cookie lifetime and secure cookie flags, then
// starts the session. Every endpoint includes this file instead of calling
// session_start() directly, so the login cookie behaves the same everywhere.

if (session_status() === PHP_SESSION_NONE) {
    // The session cookie (and the server-side session data) expires after one day.
    $lifetime = 86400; // 24 hours, in seconds

    session_set_cookie_params([
        'lifetime' => $lifetime,
        'path' => '/',
        'httponly' => true, // hide the cookie from JavaScript to reduce XSS risk
        'samesite' => 'Lax',
    ]);

    // Keep the server-side session data alive for the same duration as the cookie.
    ini_set('session.gc_maxlifetime', (string) $lifetime);

    session_start();
}
