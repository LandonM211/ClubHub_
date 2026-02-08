<?php
try {
    $stmt = $pdo->prepare("SELECT id, first_name, last_name, email, is_system_owner FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        session_destroy();
        header('Location: login.php');
        exit;
    }

    // Redirect system owners to their dashboard
    if ($user['is_system_owner']) {
        header('Location: super-owner-dashboard.php');
        exit;
    }

    // Load user's clubs with permissions
    $stmt = $pdo->prepare("
        SELECT 
            c.id,
            c.name,
            c.access_code,
            c.description,
            cm.is_president,
            cr.role_name,
            cr.id as role_id
        FROM club_members cm
        JOIN clubs c ON c.id = cm.club_id
        JOIN club_roles cr ON cr.id = cm.role_id
        WHERE cm.user_id = ? AND cm.status = 'active' AND c.is_active = TRUE
        ORDER BY c.name
    ");
    $stmt->execute([$user['id']]);
    $clubs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($clubs)) {
        header('Location: no-clubs.php');
        exit;
    }

    // Set active club
    if (!isset($_SESSION['active_club_id']) || !in_array($_SESSION['active_club_id'], array_column($clubs, 'id'))) {
        $_SESSION['active_club_id'] = $clubs[0]['id'];
    }

    // Get active club details
    $activeClub = null;
    foreach ($clubs as $club) {
        if ($club['id'] == $_SESSION['active_club_id']) {
            $activeClub = $club;
            break;
        }
    }

    // Load permissions for active club
    $stmt = $pdo->prepare("SELECT permission_key, permission_value FROM role_permissions WHERE role_id = ?");
    $stmt->execute([$activeClub['role_id']]);
    $permissionsArray = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Convert to associative array
    $permissions = [];
    foreach ($permissionsArray as $perm) {
        $permissions[$perm['permission_key']] = (bool)$perm['permission_value'];
    }

    // Load user notifications
    $stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? AND is_read = FALSE ORDER BY created_at DESC LIMIT 10");
    $stmt->execute([$user['id']]);
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $unreadCount = count($notifications);

} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    die("An error occurred. Please try again later.");
}
?>