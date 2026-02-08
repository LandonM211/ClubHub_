<?php
/**
 * api/auth.php - Authentication API Endpoint (FIXED)
 *
 * Fixes applied:
 *   #12 - Explicit session_start() before any $_SESSION access
 *   #13 - Register rate limiting now also limits per-email, not just IP
 *   #14 - Email is validated AFTER trimming/lowering but stored without
 *         destructive htmlspecialchars(); sanitizeInput() removed from
 *         email/names at the storage layer (output-encode instead)
 *   #15 - Login rate limiting is now per-account AND per-IP
 */

// FIX #12: Explicitly start the session before doing anything with $_SESSION.
// db.php also calls session_start(), but that becomes a safe no-op after this.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../database/db.php';
header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];
$action = $_REQUEST['action'] ?? null;

// Read JSON body once
$input = json_decode(file_get_contents('php://input'), true);

// ---------------------------
// REGISTER
// ---------------------------
if ($action === 'register' && $method === 'POST') {
    try {
        $data = $input;

        // Trim and normalise inputs early so validation runs on clean data
        $email     = isset($data['email'])      ? strtolower(trim($data['email']))  : '';
        $password  = $data['password']  ?? '';
        $firstName = isset($data['first_name']) ? trim($data['first_name'])         : '';
        $lastName  = isset($data['last_name'])  ? trim($data['last_name'])          : '';

        // --- basic presence check ---
        if ($email === '' || $password === '' || $firstName === '' || $lastName === '') {
            jsonResponse(false, null, 'All fields are required');
        }

        // --- validate before any DB/rate-limit work ---
        if (!isValidEmail($email)) {
            jsonResponse(false, null, 'Invalid email address');
        }

        if (strlen($password) < 8) {
            jsonResponse(false, null, 'Password must be at least 8 characters');
        }

        /*
         * FIX #13: Rate-limit registration by BOTH IP and target email.
         *
         * IP-only limiting (original) is trivially bypassed via the spoofable
         * X-Forwarded-For header.  Adding a per-email limit prevents an
         * attacker from flooding registration attempts for one address even
         * when rotating IPs.
         *
         * We keep the IP limit as a broad abuse brake (10 registrations/hr
         * from one source) and add a tight per-email limit (3 attempts/hr).
         */
        checkRateLimit('register_ip',    null,   10);   // per-IP
        checkRateLimit('register_email', $email, 3);    // per-email (user_id param accepts any string identifier)

        // Check for existing account
        if (dbQueryOne("SELECT id FROM users WHERE email = ?", [$email])) {
            jsonResponse(false, null, 'Email already registered');
        }

        /*
         * FIX #14: Do NOT wrap email / names in sanitizeInput() before storage.
         *
         * sanitizeInput() calls htmlspecialchars() + strip_tags(), which is an
         * OUTPUT concern. Applying it at the storage layer permanently corrupts
         * data (e.g. "O'Brien" → "O&#039;Brien" in the DB) and causes
         * double-encoding when the value is later rendered through any template
         * that also escapes HTML.
         *
         * Instead we:
         *   - trim / lowercase email (done above)
         *   - trim names (done above)
         *   - rely on prepared statements for SQL-injection safety
         *   - escape at the output layer (htmlspecialchars in templates, etc.)
         *
         * Names are additionally stripped of control characters for safety.
         */
        $safeFirstName = preg_replace('/[\x00-\x1F\x7F]/u', '', $firstName);
        $safeLastName  = preg_replace('/[\x00-\x1F\x7F]/u', '', $lastName);

        $userId = dbExecute(
            "INSERT INTO users (email, password_hash, first_name, last_name, email_verified) VALUES (?, ?, ?, ?, TRUE)",
            [
                $email,
                hashPassword($password),
                $safeFirstName,
                $safeLastName
            ]
        );

        if (!$userId) {
            jsonResponse(false, null, 'Registration failed');
        }

        dbExecute("INSERT INTO user_preferences (user_id) VALUES (?)", [$userId]);

        // Populate session
        $_SESSION['user_id']         = $userId;
        $_SESSION['email']           = $email;
        $_SESSION['first_name']      = $safeFirstName;
        $_SESSION['last_name']       = $safeLastName;
        $_SESSION['is_system_owner'] = false;

        dbExecute(
            "INSERT INTO sessions (id, user_id, ip_address, user_agent) VALUES (?, ?, ?, ?)",
            [session_id(), $userId, getUserIP(), $_SERVER['HTTP_USER_AGENT'] ?? '']
        );

        dbExecute("UPDATE users SET last_login = NOW() WHERE id = ?", [$userId]);

        createNotification($userId, 'Welcome to Club Hub!', 'Your account has been created successfully.', 'success');
        logActivity($userId, 'register', ['email' => $email]);

        jsonResponse(true, [
            'user_id'         => $userId,
            'email'           => $email,
            'first_name'      => $safeFirstName,
            'last_name'       => $safeLastName,
            'is_system_owner' => false
        ], 'Registration successful');

    } catch (Exception $e) {
        error_log('Registration error: ' . $e->getMessage());
        jsonResponse(false, null, 'Registration failed');
    }
}

// ---------------------------
// LOGIN
// ---------------------------
if ($action === 'login' && $method === 'POST') {
    try {
        $data = $input;

        $email    = isset($data['email'])    ? strtolower(trim($data['email'])) : '';
        $password = $data['password'] ?? '';

        if ($email === '' || $password === '') {
            jsonResponse(false, null, 'Email and password are required');
        }

        /*
         * FIX #15: Rate-limit login by BOTH IP and target email.
         *
         * The original code only limited by IP, so an attacker rotating IPs
         * could brute-force a single account without restriction.
         *
         * We now apply two independent limits:
         *   - Per-IP   : MAX_LOGIN_ATTEMPTS attempts / window  (broad abuse)
         *   - Per-email : MAX_LOGIN_ATTEMPTS attempts / window  (account lock)
         *
         * Both must pass for the attempt to proceed.
         */
        checkRateLimit('login_ip',    null,   MAX_LOGIN_ATTEMPTS);   // per-IP
        checkRateLimit('login_email', $email, MAX_LOGIN_ATTEMPTS);   // per-email

        $user = dbQueryOne(
            "SELECT id, email, password_hash, first_name, last_name, is_system_owner, is_active FROM users WHERE email = ?",
            [$email]
        );

        if (!$user || !verifyPassword($password, $user['password_hash'])) {
            // Generic message to avoid user-enumeration
            jsonResponse(false, null, 'Invalid email or password');
        }

        if (!$user['is_active']) {
            jsonResponse(false, null, 'Account is deactivated');
        }

        // Regenerate session ID on privilege change (login) to prevent fixation
        session_regenerate_id(true);

        // Populate session
        $_SESSION['user_id']         = $user['id'];
        $_SESSION['email']           = $user['email'];
        $_SESSION['first_name']      = $user['first_name'];
        $_SESSION['last_name']       = $user['last_name'];
        $_SESSION['is_system_owner'] = (bool)$user['is_system_owner'];

        dbExecute(
            "REPLACE INTO sessions (id, user_id, ip_address, user_agent) VALUES (?, ?, ?, ?)",
            [session_id(), $user['id'], getUserIP(), $_SERVER['HTTP_USER_AGENT'] ?? '']
        );

        dbExecute("UPDATE users SET last_login = NOW() WHERE id = ?", [$user['id']]);
        logActivity($user['id'], 'login', ['ip' => getUserIP()]);

        jsonResponse(true, [
            'user_id'         => $user['id'],
            'email'           => $user['email'],
            'first_name'      => $user['first_name'],
            'last_name'       => $user['last_name'],
            'is_system_owner' => (bool)$user['is_system_owner']
        ], 'Login successful');

    } catch (Exception $e) {
        error_log('Login error: ' . $e->getMessage());
        jsonResponse(false, null, 'Login failed');
    }
}

// ---------------------------
// LOGOUT
// ---------------------------
if ($action === 'logout' && $method === 'POST') {
    // Validate CSRF on state-changing request
    $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
    if (!$token || !verifyCSRFToken($token)) {
        jsonResponse(false, null, 'Invalid or missing CSRF token');
    }

    try {
        if (isLoggedIn()) {
            $userId = getCurrentUserId();
            dbExecute("DELETE FROM sessions WHERE id = ?", [session_id()]);
            logActivity($userId, 'logout');
        }
        session_destroy();
        jsonResponse(true, null, 'Logged out successfully');

    } catch (Exception $e) {
        error_log('Logout error: ' . $e->getMessage());
        jsonResponse(false, null, 'Logout failed');
    }
}

// ---------------------------
// CHECK SESSION
// ---------------------------
if ($action === 'check' && $method === 'GET') {
    if (isLoggedIn()) {
        $user = getCurrentUser();
        if ($user) {
            dbExecute("UPDATE sessions SET last_activity = NOW() WHERE id = ?", [session_id()]);
            jsonResponse(true, [
                'user_id'         => $user['id'],
                'email'           => $user['email'],
                'first_name'      => $user['first_name'],
                'last_name'       => $user['last_name'],
                'is_system_owner' => $_SESSION['is_system_owner'] ?? false
            ]);
        }
    }
    jsonResponse(false, null, 'Not logged in');
}

// ---------------------------
// GET MY CLUBS
// ---------------------------
if ($action === 'my-clubs' && $method === 'GET') {
    if (!isLoggedIn()) {
        jsonResponse(false, null, 'Login required');
    }

    try {
        $sql = "SELECT 
                    c.id,
                    c.name,
                    c.description,
                    c.access_code,
                    cm.is_president,
                    cm.status,
                    cr.role_name,
                    cr.id as role_id
                FROM club_members cm
                JOIN clubs c ON c.id = cm.club_id
                JOIN club_roles cr ON cr.id = cm.role_id
                WHERE cm.user_id = ? AND cm.status = 'active' AND c.is_active = TRUE
                ORDER BY c.name ASC";

        $clubs = dbQuery($sql, [getCurrentUserId()]);
        jsonResponse(true, $clubs);

    } catch (Exception $e) {
        error_log('Get my clubs error: ' . $e->getMessage());
        jsonResponse(false, null, 'Error fetching clubs');
    }
}

// ---------------------------
// INVALID ACTION
// ---------------------------
jsonResponse(false, null, 'Invalid action');