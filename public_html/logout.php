<?php
/**
 * logout.php - Session Logout Handler
 * Club Hub Management System
 *
 * FIXES APPLIED:
 * - Removed mangled double-if with mismatched braces on line 14
 * - Fixed CSRF header key: HTTP_CSRF_TOKEN → HTTP_X_CSRF_TOKEN
 *   (JS sends X-CSRF-Token, PHP maps that to HTTP_X_CSRF_TOKEN)
 * - Replaced unsafe !== comparison with hash_equals() via requireCSRFForMutation()
 * - Now uses db.php helpers (requireCSRFForMutation, jsonResponse) instead of
 *   duplicating CSRF logic and manual json_encode output
 * - Removed redundant session_start() (db.php handles it)
 * - Removed unused json_decode of request body (action is implicit — this
 *   endpoint only does one thing)
 */

require_once __DIR__ . '/database/db.php';

// Only allow POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, null, 'Invalid request method');
}

// Validate CSRF token using the shared helper from db.php
// Reads $_SERVER['HTTP_X_CSRF_TOKEN'] and uses hash_equals() internally
requireCSRFForMutation();

// Destroy session safely
if (isset($_SESSION['user_id'])) {
    session_unset();
    session_destroy();

    // Clear session cookie
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }

    // Start a fresh session so jsonResponse/generateCSRFToken can access $_SESSION
    session_start();
}

jsonResponse(true, null, 'Logged out successfully');