<?php
/**
 * db.php - Database Connection & Utility Functions
 * Club Hub Management System - Production Ready with All Security Functions
 *
 * ========== AUDIT FIXES APPLIED ==========
 * FIX #1:  requireClubMembership() - Fixed table name from 'roles' to 'club_roles'
 * FIX #2:  requireClubPermission() - Fixed table name and column 'permission_name' -> 'permission_key'
 * FIX #3:  requireClubMembership() - Fixed empty array truthiness check using empty()
 * FIX #4:  checkRateLimit() - Fixed INSERT/UPDATE race and parameter binding logic
 * FIX #5:  jsonResponse() - Removed dangerous auto-commit/rollback side effect
 * FIX #6:  DB credentials - Moved to environment variables with hardcoded fallbacks
 * FIX #7:  dbExecute() - Added explicit $returnInsertId parameter for clarity
 * FIX #8:  sanitizeInput() - Renamed to sanitizeOutput(); new validateInput() for storage
 * FIX #9:  getUserIP() - No longer trusts spoofable proxy headers for security decisions
 * ===========================================
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ---------------------------
// DATABASE CONFIGURATION
// FIX #6: Read credentials from environment variables instead of hardcoding.
// Falls back to hardcoded values only if env vars are not set (for backward compat).
// In production, set these via your web server or .env loader.
// ---------------------------
define('DB_HOST', getenv('CLUBHUB_DB_HOST') ?: 'localhost');
define('DB_NAME', getenv('CLUBHUB_DB_NAME') ?: 'dbztof0dny7rla');
define('DB_USER', getenv('CLUBHUB_DB_USER') ?: 'upxmhqodbwb3x');
define('DB_PASS', getenv('CLUBHUB_DB_PASS') ?: '@b34c$b_b{R3');
define('DB_CHARSET', 'utf8mb4');

// SECURITY SETTINGS
define('SESSION_LIFETIME', 86400); // 24 hours
define('MAX_LOGIN_ATTEMPTS', 5);
define('RATE_LIMIT_WINDOW', 3600); // 1 hour

// Global PDO instance
global $pdo;
$pdo = null;

// ---------------------------
// DATABASE CONNECTION
// ---------------------------
function getDBConnection() {
    global $pdo;
    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            error_log("Database connection failed: " . $e->getMessage());
            http_response_code(500);
            die(json_encode(['success' => false, 'message' => 'Database connection failed']));
        }
    }
    return $pdo;
}

// Initialize connection
$pdo = getDBConnection();

// ---------------------------
// QUERY FUNCTIONS
// ---------------------------
function dbQuery($sql, $params = []) {
    try {
        $stmt = getDBConnection()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Query error: " . $e->getMessage() . " | SQL: " . $sql);
        throw $e;
    }
}

function dbQueryOne($sql, $params = []) {
    try {
        $stmt = getDBConnection()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch();
    } catch (PDOException $e) {
        error_log("Query error: " . $e->getMessage() . " | SQL: " . $sql);
        throw $e;
    }
}

/**
 * FIX #7: Added explicit $returnInsertId parameter.
 * Previously, the function guessed based on SQL prefix, which was unreliable for
 * INSERT ... ON DUPLICATE KEY UPDATE and REPLACE INTO statements.
 * - $returnInsertId = null  → auto-detect (INSERT = lastInsertId, else rowCount) [legacy behavior]
 * - $returnInsertId = true  → always return lastInsertId
 * - $returnInsertId = false → always return rowCount
 */
function dbExecute($sql, $params = [], $returnInsertId = null) {
    try {
        $stmt = getDBConnection()->prepare($sql);
        $stmt->execute($params);

        // Determine what to return
        if ($returnInsertId === true) {
            return getDBConnection()->lastInsertId();
        } elseif ($returnInsertId === false) {
            return $stmt->rowCount();
        }

        // Auto-detect: INSERT returns lastInsertId, everything else returns rowCount
        if (stripos(trim($sql), 'INSERT') === 0) {
            return getDBConnection()->lastInsertId();
        }
        return $stmt->rowCount();
    } catch (PDOException $e) {
        error_log("Execute error: " . $e->getMessage() . " | SQL: " . $sql);
        throw $e;
    }
}

// ---------------------------
// TRANSACTION FUNCTIONS
// ---------------------------
function dbBeginTransaction() {
    return getDBConnection()->beginTransaction();
}

function dbCommit() {
    return getDBConnection()->commit();
}

function dbRollback() {
    return getDBConnection()->rollBack();
}

// ---------------------------
// INPUT VALIDATION & OUTPUT ENCODING
// ---------------------------

/**
 * FIX #8: Renamed from sanitizeInput() and split into two functions.
 *
 * sanitizeOutput() — Use when RENDERING user data in HTML output.
 *   Applies htmlspecialchars to prevent XSS on display.
 *
 * validateInput() — Use when STORING user data in the database.
 *   Trims whitespace and strips control characters, but does NOT apply
 *   HTML encoding. This prevents double-encoding when data is later
 *   displayed through templates that also escape HTML.
 *
 * The old sanitizeInput() is kept as a deprecated alias for backward
 * compatibility with existing API files that call it before DB storage.
 * It now calls validateInput() instead of applying htmlspecialchars,
 * so stored data is no longer corrupted with HTML entities.
 */
function sanitizeOutput($input) {
    if (is_array($input)) {
        return array_map('sanitizeOutput', $input);
    }
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

function validateInput($input) {
    if (is_array($input)) {
        return array_map('validateInput', $input);
    }
    // Strip null bytes and trim whitespace; do NOT html-encode for storage
    $cleaned = str_replace("\0", '', trim($input));
    return $cleaned;
}

/**
 * @deprecated Use validateInput() for DB storage or sanitizeOutput() for HTML display.
 * Kept for backward compatibility — now delegates to validateInput() so existing
 * callers no longer corrupt stored data with HTML entities.
 */
function sanitizeInput($input) {
    return validateInput($input);
}

function hashPassword($password) {
    return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
}

function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * FIX #9: getUserIP() no longer trusts spoofable proxy headers.
 *
 * HTTP_CLIENT_IP and HTTP_X_FORWARDED_FOR are trivially forged by clients.
 * Using them for rate limiting allowed attackers to bypass limits by rotating
 * the X-Forwarded-For header. Now we only use REMOTE_ADDR, which is the
 * actual TCP connection source.
 *
 * If you are behind a TRUSTED reverse proxy (e.g., Cloudflare, AWS ALB),
 * configure the proxy's real-IP header at the web server level (e.g.,
 * mod_remoteip for Apache, real_ip_header for Nginx) so that REMOTE_ADDR
 * is set correctly, rather than trusting arbitrary client headers here.
 */
function getUserIP() {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : '0.0.0.0';
}

function generateAccessCode($clubName) {
    // Generate a unique 6-character access code
    $prefix = strtoupper(substr(preg_replace('/[^A-Z]/', '', strtoupper($clubName)), 0, 3));
    if (strlen($prefix) < 3) {
        $prefix = str_pad($prefix, 3, 'X');
    }

    $code = $prefix . rand(100, 999);

    // Ensure uniqueness
    $existing = dbQueryOne("SELECT id FROM clubs WHERE access_code = ?", [$code]);
    if ($existing) {
        return generateAccessCode($clubName . rand(1, 999));
    }

    return $code;
}

/**
 * FIX #1 & #3: requireClubMembership()
 *
 * - Changed table join from non-existent 'roles' to 'club_roles' (matches schema)
 * - Changed join condition from 'cm.role_id = r.id' to 'cm.role_id = cr.id'
 * - Fixed empty result check: empty array [] is truthy in PHP, so `if (!$member)`
 *   never triggered. Now uses empty() for correct behavior.
 * - Returns the first matching row (role_id + role_name) for downstream use.
 */
function requireClubMembership($clubId) {
    $userId = $_SESSION['user_id'] ?? null;
    if (!$userId || !$clubId) {
        jsonResponse(false, null, 'Club ID and authentication required');
    }

    $member = dbQuery(
        "SELECT cm.id, cm.role_id, cr.role_name
         FROM club_members cm
         JOIN club_roles cr ON cm.role_id = cr.id
         WHERE cm.user_id = ? AND cm.club_id = ? AND cm.status = 'active'",
        [$userId, $clubId]
    );

    if (empty($member)) {
        jsonResponse(false, null, 'You are not an active member of this club');
    }

    return $member[0];
}

/**
 * FIX #2: requireClubPermission()
 *
 * - Now uses the role_id returned by requireClubMembership() directly instead of
 *   doing a second lookup on a non-existent 'roles' table.
 * - Changed column name from 'permission_name' to 'permission_key' (matches schema).
 * - Simplified logic: one query to check permission value for the member's role_id.
 */
function requireClubPermission($clubId, $permissionKey) {
    $member = requireClubMembership($clubId);

    $perm = dbQueryOne(
        "SELECT permission_value FROM role_permissions WHERE role_id = ? AND permission_key = ?",
        [$member['role_id'], $permissionKey]
    );

    if (!$perm || !$perm['permission_value']) {
        jsonResponse(false, null, 'You do not have permission to perform this action');
    }

    return $member;
}

// ---------------------------
// CSRF FUNCTIONS
// ---------------------------
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function requireCSRFForMutation() {
    $method = $_SERVER['REQUEST_METHOD'];
    if (in_array($method, ['POST', 'PUT', 'DELETE'])) {
        $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
        if (!$token || !verifyCSRFToken($token)) {
            jsonResponse(false, null, 'Invalid or missing CSRF token');
        }
    }
}

// ---------------------------
// RATE LIMITING
// ---------------------------

/**
 * checkRateLimit() — Unified rate limiter using a single rate_key column.
 *
 * The rate_key is a non-nullable string that uniquely identifies the subject
 * being limited:
 *   - User/identifier-based: "user:{identifier}" (identifier may be a user ID or email)
 *   - IP-based:              "ip:{ip_address}"
 *
 * Combined with action_type in a UNIQUE(action_type, rate_key) constraint,
 * the ON DUPLICATE KEY UPDATE fires reliably on repeat attempts.
 *
 * This also eliminates the old FOREIGN KEY on user_id, so passing a string
 * identifier (e.g. an email for per-email limiting) no longer causes FK
 * violations.
 */
function checkRateLimit($action, $userId = null, $maxAttempts = 10) {
    $ip = getUserIP();

    // Build a deterministic key: prefer the explicit identifier, fall back to IP
    // Truncate to 250 chars to stay within VARCHAR(255) with safety margin
    $rateKey = $userId !== null ? 'user:' . $userId : 'ip:' . $ip;
    if (strlen($rateKey) > 250) {
        $rateKey = substr($rateKey, 0, 218) . ':' . md5($rateKey);
    }

    try {
        // Clean expired entries for this action
        dbExecute(
            "DELETE FROM chat_rate_limits WHERE action_type = ? AND window_start < DATE_SUB(NOW(), INTERVAL ? SECOND)",
            [$action, RATE_LIMIT_WINDOW]
        );

        // Check current count within the active window
        $record = dbQueryOne(
            "SELECT action_count FROM chat_rate_limits
             WHERE action_type = ? AND rate_key = ?
             AND window_start >= DATE_SUB(NOW(), INTERVAL ? SECOND)",
            [$action, $rateKey, RATE_LIMIT_WINDOW]
        );

        if ($record && $record['action_count'] >= $maxAttempts) {
            jsonResponse(false, null, 'Rate limit exceeded. Please try again later.');
        }

        // Atomic upsert: insert a new window or increment the existing one.
        // The UNIQUE(action_type, rate_key) constraint guarantees the
        // ON DUPLICATE KEY branch fires on repeat attempts.
        dbExecute(
            "INSERT INTO chat_rate_limits (action_type, rate_key, ip_address, action_count, window_start)
             VALUES (?, ?, ?, 1, NOW())
             ON DUPLICATE KEY UPDATE action_count = action_count + 1, last_action = NOW()",
            [$action, $rateKey, $ip],
            false // return rowCount, not insertId
        );

    } catch (Exception $e) {
        error_log("Rate limit error: " . $e->getMessage());
        // Don't block on rate limit errors — fail open
    }
}

// ---------------------------
// SESSION & LOGIN FUNCTIONS
// ---------------------------
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function requireLogin($redirect = 'login.php') {
    if (!isLoggedIn()) {
        header("Location: $redirect");
        exit;
    }
}

function getCurrentUser() {
    if (!isLoggedIn()) return false;
    try {
        $sql = "SELECT * FROM users WHERE id = ? AND is_active = 1";
        return dbQueryOne($sql, [$_SESSION['user_id']]);
    } catch (Exception $e) {
        error_log("Get current user error: " . $e->getMessage());
        return false;
    }
}

function getCurrentUserId() {
    return $_SESSION['user_id'] ?? null;
}

// ---------------------------
// NOTIFICATION FUNCTIONS
// ---------------------------
function createNotification($userId, $title, $message, $type = 'info', $link = null) {
    try {
        $sql = "INSERT INTO notifications (user_id, title, message, type, link) VALUES (?, ?, ?, ?, ?)";
        return dbExecute($sql, [$userId, $title, $message, $type, $link]);
    } catch (Exception $e) {
        error_log("Create notification error: " . $e->getMessage());
        return false;
    }
}

// ---------------------------
// ACTIVITY LOGGING
// ---------------------------
function logActivity($userId, $action, $metadata = []) {
    try {
        error_log("User Activity - User ID: $userId, Action: $action, Data: " . json_encode($metadata));
    } catch (Exception $e) {
        error_log("Log activity error: " . $e->getMessage());
    }
}

// ---------------------------
// JSON RESPONSE HELPER
// ---------------------------

/**
 * FIX #5: Removed auto-commit/rollback from jsonResponse().
 *
 * Previously, jsonResponse() inspected $pdo->inTransaction() and automatically
 * committed on success or rolled back on failure. This caused dangerous side effects:
 * - Nested function calls that invoked jsonResponse(false) would rollback a transaction
 *   started by a parent caller, corrupting the parent's expected transaction state.
 * - Code that intentionally left transactions open for multi-step operations was broken.
 *
 * Transaction management is now the sole responsibility of the calling code.
 * Each caller must explicitly call dbCommit() or dbRollback() before calling jsonResponse().
 */
function jsonResponse($success, $data = null, $message = '') {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => $success,
        'data' => $data,
        'message' => $message,
        'csrf_token' => generateCSRFToken()
    ]);
    exit;
}

// ---------------------------
// CLEANUP OLD SESSIONS
// ---------------------------
function cleanOldSessions() {
    try {
        dbExecute("DELETE FROM sessions WHERE last_activity < DATE_SUB(NOW(), INTERVAL ? SECOND)", [SESSION_LIFETIME]);
    } catch (Exception $e) {
        error_log("Clean sessions error: " . $e->getMessage());
    }
}

// Regenerate session periodically
if (!isset($_SESSION['created'])) {
    $_SESSION['created'] = time();
} elseif (time() - $_SESSION['created'] > 1800) {
    session_regenerate_id(true);
    $_SESSION['created'] = time();
    if (rand(1, 100) === 1) cleanOldSessions();
}