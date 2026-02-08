<?php
/**
 * api/members.php
 * API endpoint for member management including role updates
 *
 * FIX: Removed duplicate session_start() (db.php handles it).
 * FIX: Replaced all raw $pdo / echo json_encode() with db.php helpers
 *      (jsonResponse, dbQuery, dbQueryOne, dbExecute) to prevent
 *      malformed double-JSON output when helpers call jsonResponse+exit.
 * FIX: Replaced inline CSRF check with requireCSRFForMutation().
 * FIX: Replaced raw $_SESSION checks with isLoggedIn()/getCurrentUserId().
 */

require_once __DIR__ . '/../database/db.php';

header('Content-Type: application/json');

// Check authentication
if (!isLoggedIn()) {
    jsonResponse(false, null, 'Not authenticated');
}

$action = $_GET['action'] ?? '';

// Validate CSRF for all mutation requests
requireCSRFForMutation();

try {
    // ============================================
    // UPDATE MEMBER ROLE
    // ============================================
    if ($action === 'update-role') {
        $input = json_decode(file_get_contents('php://input'), true);
        $clubId = $input['club_id'] ?? null;
        $userId = $input['user_id'] ?? null;
        $roleId = $input['role_id'] ?? null;

        if (!$clubId || !$userId || !$roleId) {
            jsonResponse(false, null, 'Missing required parameters');
        }

        // Verify requester has permission to edit member roles
        $hasPermission = dbQueryOne(
            "SELECT rp.permission_value 
             FROM club_members cm
             JOIN role_permissions rp ON cm.role_id = rp.role_id
             WHERE cm.club_id = ? AND cm.user_id = ? AND cm.status = 'active'
             AND rp.permission_key = 'edit_member_roles'",
            [$clubId, getCurrentUserId()]
        );

        if (!$hasPermission || !$hasPermission['permission_value']) {
            jsonResponse(false, null, 'You do not have permission to edit member roles');
        }

        // Verify target user is actually a member of this club
        $member = dbQueryOne(
            "SELECT id FROM club_members 
             WHERE club_id = ? AND user_id = ? AND status = 'active'",
            [$clubId, $userId]
        );

        if (!$member) {
            jsonResponse(false, null, 'User is not a member of this club');
        }

        // Verify target role exists and belongs to this club
        $role = dbQueryOne(
            "SELECT is_president FROM club_roles WHERE id = ? AND club_id = ?",
            [$roleId, $clubId]
        );

        if (!$role) {
            jsonResponse(false, null, 'Invalid role');
        }

        // Cannot assign president role through this method
        if ($role['is_president']) {
            jsonResponse(false, null, 'Cannot assign president role through this method');
        }

        // Update member role
        dbExecute(
            "UPDATE club_members 
             SET role_id = ?, is_president = 0
             WHERE club_id = ? AND user_id = ? AND status = 'active'",
            [$roleId, $clubId, $userId]
        );

        // Log the activity
        logActivity(getCurrentUserId(), 'update_member_role', [
            'club_id' => $clubId,
            'target_user_id' => $userId,
            'new_role_id' => $roleId
        ]);

        jsonResponse(true, null, 'Role updated successfully');
    }

    // ============================================
    // LIST MEMBERS
    // ============================================
    elseif ($action === 'list') {
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

        // Get all active members
        $members = dbQuery(
            "SELECT 
                cm.id,
                cm.user_id,
                cm.is_president,
                cm.joined_at,
                u.first_name,
                u.last_name,
                u.email,
                cr.id as role_id,
                cr.role_name,
                cr.role_description
            FROM club_members cm
            JOIN users u ON cm.user_id = u.id
            JOIN club_roles cr ON cm.role_id = cr.id
            WHERE cm.club_id = ? AND cm.status = 'active'
            ORDER BY cm.is_president DESC, u.last_name ASC, u.first_name ASC",
            [$clubId]
        );

        jsonResponse(true, $members);
    }

    // ============================================
    // REMOVE MEMBER
    // ============================================
    elseif ($action === 'remove') {
        $input = json_decode(file_get_contents('php://input'), true);
        $clubId = $input['club_id'] ?? null;
        $userId = $input['user_id'] ?? null;

        if (!$clubId || !$userId) {
            jsonResponse(false, null, 'Missing required parameters');
        }

        // Verify requester has permission to manage members
        $hasPermission = dbQueryOne(
            "SELECT rp.permission_value 
             FROM club_members cm
             JOIN role_permissions rp ON cm.role_id = rp.role_id
             WHERE cm.club_id = ? AND cm.user_id = ? AND cm.status = 'active'
             AND rp.permission_key = 'manage_members'",
            [$clubId, getCurrentUserId()]
        );

        if (!$hasPermission || !$hasPermission['permission_value']) {
            jsonResponse(false, null, 'You do not have permission to remove members');
        }

        // Cannot remove yourself
        if ($userId == getCurrentUserId()) {
            jsonResponse(false, null, 'You cannot remove yourself from the club');
        }

        // Check if target user is president
        $targetMember = dbQueryOne(
            "SELECT is_president FROM club_members 
             WHERE club_id = ? AND user_id = ? AND status = 'active'",
            [$clubId, $userId]
        );

        if ($targetMember && $targetMember['is_president']) {
            jsonResponse(false, null, 'Cannot remove the club president');
        }

        // Remove member (set status to removed)
        dbExecute(
            "UPDATE club_members 
             SET status = 'removed'
             WHERE club_id = ? AND user_id = ? AND status = 'active'",
            [$clubId, $userId]
        );

        // Log the activity
        logActivity(getCurrentUserId(), 'remove_member', [
            'club_id' => $clubId,
            'removed_user_id' => $userId
        ]);

        jsonResponse(true, null, 'Member removed successfully');
    }

    // ============================================
    // INVALID ACTION
    // ============================================
    else {
        jsonResponse(false, null, 'Invalid action');
    }

} catch (Exception $e) {
    error_log('Members API Error: ' . $e->getMessage());
    jsonResponse(false, null, 'Error processing request');
}