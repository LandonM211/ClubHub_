<?php
/**
 * api/roles.php
 * API endpoint for role management
 *
 * FIX: Removed duplicate session_start() (db.php handles it).
 * FIX: Replaced all raw $pdo / echo json_encode() with db.php helpers
 *      (jsonResponse, dbQuery, dbQueryOne) to prevent malformed
 *      double-JSON output when helpers call jsonResponse+exit.
 * FIX: Replaced raw $_SESSION checks with isLoggedIn()/getCurrentUserId().
 */

require_once __DIR__ . '/../database/db.php';

header('Content-Type: application/json');

// Check authentication
if (!isLoggedIn()) {
    jsonResponse(false, null, 'Not authenticated');
}

$action = $_GET['action'] ?? '';

try {
    // ============================================
    // LIST ROLES FOR A CLUB
    // ============================================
    if ($action === 'list') {
        $clubId = $_GET['club_id'] ?? null;

        if (!$clubId) {
            jsonResponse(false, null, 'Club ID is required');
        }

        // Verify user is a member of this club
        $isMember = dbQueryOne(
            "SELECT id FROM club_members 
             WHERE club_id = ? AND user_id = ? AND status = 'active'",
            [$clubId, getCurrentUserId()]
        );

        if (!$isMember) {
            jsonResponse(false, null, 'You are not a member of this club');
        }

        // Get all roles for this club
        $roles = dbQuery(
            "SELECT 
                id,
                role_name,
                role_description as description,
                is_system_role,
                created_at
            FROM club_roles
            WHERE club_id = ?
            ORDER BY 
                CASE 
                    WHEN role_name = 'President' THEN 0
                    WHEN role_name LIKE '%Vice%' OR role_name LIKE '%VP%' THEN 1
                    ELSE 2
                END,
                role_name ASC",
            [$clubId]
        );

        // Add is_president flag based on role name
        foreach ($roles as &$role) {
            $role['is_president'] = (stripos($role['role_name'], 'president') !== false &&
                                     stripos($role['role_name'], 'vice') === false);
        }
        unset($role);

        jsonResponse(true, $roles);
    }

    // ============================================
    // GET ROLE DETAILS
    // ============================================
    elseif ($action === 'get') {
        $roleId = $_GET['role_id'] ?? null;

        if (!$roleId) {
            jsonResponse(false, null, 'Role ID is required');
        }

        // Get role details
        $role = dbQueryOne(
            "SELECT 
                cr.id,
                cr.club_id,
                cr.role_name,
                cr.role_description as description,
                cr.is_system_role,
                cr.created_at
            FROM club_roles cr
            WHERE cr.id = ?",
            [$roleId]
        );

        if (!$role) {
            jsonResponse(false, null, 'Role not found');
        }

        // Verify user has access to this club
        $isMember = dbQueryOne(
            "SELECT id FROM club_members 
             WHERE club_id = ? AND user_id = ? AND status = 'active'",
            [$role['club_id'], getCurrentUserId()]
        );

        if (!$isMember) {
            jsonResponse(false, null, 'You do not have access to this role');
        }

        // Get permissions for this role
        $permissions = dbQuery(
            "SELECT permission_key, permission_value
             FROM role_permissions
             WHERE role_id = ?",
            [$roleId]
        );

        $role['permissions'] = $permissions;
        $role['is_president'] = (stripos($role['role_name'], 'president') !== false &&
                                 stripos($role['role_name'], 'vice') === false);

        jsonResponse(true, $role);
    }

    // ============================================
    // INVALID ACTION
    // ============================================
    else {
        jsonResponse(false, null, 'Invalid action');
    }

} catch (Exception $e) {
    error_log('Roles API Error: ' . $e->getMessage());
    jsonResponse(false, null, 'Error processing request');
}