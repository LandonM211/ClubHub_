<?php
/**
 * manage-role-permissions.php
 * Complete role and permission management system with LIVE PREVIEW
 * Accessible ONLY to Club Presidents and Vice Presidents
 */

session_start();
require_once __DIR__ . '/database/db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Get user and club data
try {
    $pdo = getDBConnection();
    
    $stmt = $pdo->prepare("SELECT id, first_name, last_name FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        session_destroy();
        header('Location: login.php');
        exit;
    }
    
    // Get active club
    $clubId = $_SESSION['active_club_id'] ?? null;
    if (!$clubId) {
        header('Location: index.php');
        exit;
    }
    
    // Check if user is President or Vice President
    $stmt = $pdo->prepare("
        SELECT cm.is_president, cr.role_name 
        FROM club_members cm 
        JOIN club_roles cr ON cm.role_id = cr.id
        WHERE cm.club_id = ? AND cm.user_id = ? AND cm.status = 'active'
    ");
    $stmt->execute([$clubId, $user['id']]);
    $userRole = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Only presidents and vice presidents can access this page
    $isPresident = $userRole['is_president'] == 1;
    $isVicePresident = stripos($userRole['role_name'], 'vice') !== false || stripos($userRole['role_name'], 'vp') !== false;
    
    if (!$isPresident && !$isVicePresident) {
        die('Access Denied: Only Club Presidents and Vice Presidents can manage role permissions.');
    }
    
    // Get club info
    $stmt = $pdo->prepare("SELECT name FROM clubs WHERE id = ?");
    $stmt->execute([$clubId]);
    $club = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Generate CSRF token
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    die("An error occurred. Please try again later.");
}

$message = '';
$messageType = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die('Invalid request');
    }
    
    $action = $_POST['action'] ?? '';
    
    try {
        if ($action === 'update_permission') {
            $roleId = (int)$_POST['role_id'];
            $permissionKey = $_POST['permission_key'];
            $permissionValue = (int)$_POST['permission_value'];
            
            // Verify role belongs to this club
            $stmt = $pdo->prepare("SELECT id FROM club_roles WHERE id = ? AND club_id = ?");
            $stmt->execute([$roleId, $clubId]);
            if (!$stmt->fetch()) {
                throw new Exception("Invalid role");
            }
            
            // Update or insert permission
            $stmt = $pdo->prepare("
                INSERT INTO role_permissions (role_id, permission_key, permission_value)
                VALUES (?, ?, ?)
                ON DUPLICATE KEY UPDATE permission_value = ?
            ");
            $stmt->execute([$roleId, $permissionKey, $permissionValue, $permissionValue]);
            
            $message = "Permission updated successfully!";
            $messageType = 'success';
            
            // Log activity
            logActivity($user['id'], 'update_role_permission', [
                'club_id' => $clubId,
                'role_id' => $roleId,
                'permission_key' => $permissionKey,
                'permission_value' => $permissionValue
            ]);
            
        } elseif ($action === 'create_role') {
            $roleName = trim($_POST['role_name']);
            $description = trim($_POST['description']);
            
            if (empty($roleName)) {
                throw new Exception("Role name is required");
            }
            
            // Create role
            $stmt = $pdo->prepare("
                INSERT INTO club_roles (club_id, role_name, role_description, is_system_role)
                VALUES (?, ?, ?, 0)
            ");
            $stmt->execute([$clubId, $roleName, $description]);
            
            $message = "Role created successfully!";
            $messageType = 'success';
            
            logActivity($user['id'], 'create_role', [
                'club_id' => $clubId,
                'role_name' => $roleName
            ]);
            
        } elseif ($action === 'delete_role') {
            $roleId = (int)$_POST['role_id'];
            
            // Can't delete system roles or roles assigned to presidents
            $stmt = $pdo->prepare("
                SELECT cr.is_system_role, 
                       (SELECT COUNT(*) FROM club_members WHERE role_id = ? AND is_president = 1) as president_count
                FROM club_roles cr 
                WHERE cr.id = ? AND cr.club_id = ?
            ");
            $stmt->execute([$roleId, $roleId, $clubId]);
            $role = $stmt->fetch();
            
            if (!$role) {
                throw new Exception("Invalid role");
            }
            
            if ($role['is_system_role']) {
                throw new Exception("Cannot delete system role");
            }
            
            if ($role['president_count'] > 0) {
                throw new Exception("Cannot delete role assigned to club president");
            }
            
            // Check if role has members
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM club_members WHERE role_id = ? AND status = 'active'");
            $stmt->execute([$roleId]);
            $memberCount = $stmt->fetchColumn();
            
            if ($memberCount > 0) {
                throw new Exception("Cannot delete role with active members. Reassign members first.");
            }
            
            // Delete role and permissions
            dbBeginTransaction();
            try {
                $stmt = $pdo->prepare("DELETE FROM role_permissions WHERE role_id = ?");
                $stmt->execute([$roleId]);
                
                $stmt = $pdo->prepare("DELETE FROM club_roles WHERE id = ? AND club_id = ?");
                $stmt->execute([$roleId, $clubId]);
                
                dbCommit();
                
                $message = "Role deleted successfully!";
                $messageType = 'success';
                
                logActivity($user['id'], 'delete_role', [
                    'club_id' => $clubId,
                    'role_id' => $roleId
                ]);
            } catch (Exception $e) {
                dbRollback();
                throw $e;
            }
        }
    } catch (Exception $e) {
        $message = "Error: " . $e->getMessage();
        $messageType = 'error';
    }
}

// Get all roles for this club with president status
$stmt = $pdo->prepare("
    SELECT cr.id, cr.role_name, cr.role_description, cr.is_system_role, cr.created_at,
           (SELECT COUNT(*) FROM club_members WHERE role_id = cr.id AND is_president = 1) > 0 as has_president
    FROM club_roles cr
    WHERE cr.club_id = ?
    ORDER BY cr.is_system_role DESC, cr.role_name ASC
");
$stmt->execute([$clubId]);
$roles = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Selected role
$selectedRoleId = (int)($_GET['role_id'] ?? ($roles[0]['id'] ?? 0));
$selectedRole = null;
$rolePermissions = [];

if ($selectedRoleId > 0) {
    foreach ($roles as $role) {
        if ($role['id'] == $selectedRoleId) {
            $selectedRole = $role;
            break;
        }
    }
    
    if ($selectedRole) {
        $stmt = $pdo->prepare("SELECT permission_key, permission_value FROM role_permissions WHERE role_id = ?");
        $stmt->execute([$selectedRoleId]);
        $perms = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($perms as $perm) {
            $rolePermissions[$perm['permission_key']] = (bool)$perm['permission_value'];
        }
    }
}

// Define all available permissions with categories
$permissionCategories = [
    'Core Access' => [
        ['key' => 'view_announcements', 'name' => 'View Announcements', 'desc' => 'Can see club announcements'],
        ['key' => 'create_announcements', 'name' => 'Create Announcements', 'desc' => 'Can post new announcements'],
        ['key' => 'edit_announcements', 'name' => 'Edit Announcements', 'desc' => 'Can modify existing announcements'],
        ['key' => 'delete_announcements', 'name' => 'Delete Announcements', 'desc' => 'Can remove announcements'],
    ],
    'Events' => [
        ['key' => 'view_events', 'name' => 'View Events', 'desc' => 'Can see club events'],
        ['key' => 'create_events', 'name' => 'Create Events', 'desc' => 'Can create new events'],
        ['key' => 'edit_events', 'name' => 'Edit Events', 'desc' => 'Can modify events'],
        ['key' => 'delete_events', 'name' => 'Delete Events', 'desc' => 'Can remove events'],
    ],
    'Members' => [
        ['key' => 'view_members', 'name' => 'View Members', 'desc' => 'Can see member list'],
        ['key' => 'manage_members', 'name' => 'Manage Members', 'desc' => 'Can add/remove members and approve join requests'],
        ['key' => 'edit_member_roles', 'name' => 'Edit Member Roles', 'desc' => 'Can change member roles'],
    ],
    'Attendance' => [
        ['key' => 'view_attendance', 'name' => 'View Attendance', 'desc' => 'Can see attendance records'],
        ['key' => 'take_attendance', 'name' => 'Take Attendance', 'desc' => 'Can mark attendance'],
        ['key' => 'edit_attendance', 'name' => 'Edit Attendance', 'desc' => 'Can modify attendance records'],
    ],
    'Communication' => [
        ['key' => 'access_chat', 'name' => 'Access Chat', 'desc' => 'Can use club chat'],
        ['key' => 'create_chat_rooms', 'name' => 'Create Chat Rooms', 'desc' => 'Can create new chat rooms'],
        ['key' => 'manage_chat_rooms', 'name' => 'Manage Chat Rooms', 'desc' => 'Can edit/delete chat rooms'],
    ],
    'Administration' => [
        ['key' => 'modify_club_settings', 'name' => 'Modify Club Settings', 'desc' => 'Can change club information'],
        ['key' => 'manage_roles', 'name' => 'Manage Roles', 'desc' => 'Can create and manage roles'],
        ['key' => 'view_analytics', 'name' => 'View Analytics', 'desc' => 'Can see club statistics and reports'],
    ],
];

// Get statistics for preview
$stats = [
    'total_members' => 0,
    'upcoming_events' => 0,
    'attendance_rate' => 85,
    'messages_today' => 24,
];

$stmt = $pdo->prepare("SELECT COUNT(*) FROM club_members WHERE club_id = ? AND status = 'active'");
$stmt->execute([$clubId]);
$stats['total_members'] = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM events WHERE club_id = ? AND event_date >= CURDATE()");
$stmt->execute([$clubId]);
$stats['upcoming_events'] = $stmt->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Roles - <?php echo htmlspecialchars($club['name']); ?></title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="styles.css">
    
    <?php require_once __DIR__ . "/includes/role_permissions/role_permissions-css.php" ?>
</head>
<body style="overflow-y: auto; height: 100vh;">
    <div class="app-container" style="overflow-y: auto;">
        <!-- Include your sidebar here -->
        <main class="main-content" style="margin-left: 0; width: 100%; overflow-y: auto; min-height: 100vh;">
            <div style="max-width: 1400px; margin: 0 auto; padding: 2rem; overflow-y: visible;">
                <div class="page-header">
                    <a href="index.php" class="back-link">
                        <i class="fas fa-arrow-left"></i> Back to Dashboard
                    </a>
                    <h1 class="page-title">🔐 Manage Roles & Permissions</h1>
                    <p class="page-subtitle">Configure what each role can do in your club</p>
                </div>
                
                <?php if ($message): ?>
                    <div class="alert alert-<?php echo $messageType; ?>">
                        <i class="fas fa-<?php echo $messageType === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                        <?php echo htmlspecialchars($message); ?>
                    </div>
                <?php endif; ?>
                
                <div class="permissions-container">
                    <!-- Left: Main Content -->
                    <div>
                        <!-- Role Selector -->
                        <div class="role-selector-card">
                            <div class="role-selector-header">
                                <h3>Select Role to Manage</h3>
                                <button class="btn-create-role" onclick="openCreateRoleModal()">
                                    <i class="fas fa-plus"></i> Create New Role
                                </button>
                            </div>
                            <div class="role-tabs">
                                <?php foreach ($roles as $role): ?>
                                    <div class="role-tab <?php echo $role['id'] == $selectedRoleId ? 'active' : ''; ?> <?php echo $role['has_president'] ? 'president' : ''; ?>"
                                         onclick="window.location.href='?role_id=<?php echo $role['id']; ?>'">
                                        <?php if ($role['has_president']): ?>
                                            <i class="fas fa-crown"></i>
                                        <?php endif; ?>
                                        <?php echo htmlspecialchars($role['role_name']); ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        
                        <?php if ($selectedRole): ?>
                            <!-- Role Info -->
                            <div class="role-info-banner">
                                <h4>
                                    <?php if ($selectedRole['has_president']): ?>
                                        <i class="fas fa-crown" style="color: var(--danger);"></i>
                                    <?php endif; ?>
                                    <?php echo htmlspecialchars($selectedRole['role_name']); ?>
                                </h4>
                                <p><?php echo htmlspecialchars($selectedRole['role_description'] ?: 'No description provided'); ?></p>
                                <?php if ($selectedRole['has_president']): ?>
                                    <p style="color: var(--danger); margin-top: 0.5rem; font-weight: 600;">
                                        <i class="fas fa-lock"></i> This role is assigned to the club president
                                    </p>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Permissions Matrix -->
                            <div class="permissions-card">
                                <form method="POST" id="permissionsForm">
                                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                    <input type="hidden" name="action" value="update_permission">
                                    <input type="hidden" name="role_id" value="<?php echo $selectedRoleId; ?>">
                                    
                                    <?php foreach ($permissionCategories as $category => $permissions): ?>
                                        <div class="category-section">
                                            <div class="category-header">
                                                <i class="fas fa-folder"></i>
                                                <?php echo htmlspecialchars($category); ?>
                                            </div>
                                            
                                            <?php foreach ($permissions as $perm): ?>
                                                <div class="permission-row">
                                                    <div class="permission-info">
                                                        <div class="permission-name"><?php echo htmlspecialchars($perm['name']); ?></div>
                                                        <div class="permission-desc"><?php echo htmlspecialchars($perm['desc']); ?></div>
                                                    </div>
                                                    <label class="permission-toggle">
                                                        <input type="checkbox" 
                                                               data-permission="<?php echo $perm['key']; ?>"
                                                               <?php echo isset($rolePermissions[$perm['key']]) && $rolePermissions[$perm['key']] ? 'checked' : ''; ?>
                                                               onchange="togglePermission('<?php echo $perm['key']; ?>', this.checked)">
                                                        <span class="toggle-slider"></span>
                                                    </label>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endforeach; ?>
                                </form>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Right: Live Preview -->
                    <?php if ($selectedRole): ?>
                    <div class="preview-sidebar">
                        <div class="preview-container">
                            <div class="preview-header">
                                <h3>
                                    <i class="fas fa-eye"></i>
                                    Live Preview
                                </h3>
                                <span class="preview-badge">LIVE</span>
                            </div>
                            
                            <div class="preview-dashboard">
                                <!-- Stats Preview -->
                                <div class="preview-section-title">Dashboard Statistics</div>
                                <div class="preview-stats">
                                    <div class="preview-stat" id="preview-stat-members">
                                        <div class="preview-stat-icon">👥</div>
                                        <div class="preview-stat-value"><?php echo $stats['total_members']; ?></div>
                                        <div class="preview-stat-label">Members</div>
                                    </div>
                                    
                                    <div class="preview-stat" id="preview-stat-events">
                                        <div class="preview-stat-icon">📅</div>
                                        <div class="preview-stat-value"><?php echo $stats['upcoming_events']; ?></div>
                                        <div class="preview-stat-label">Events</div>
                                    </div>
                                    
                                    <div class="preview-stat" id="preview-stat-attendance">
                                        <div class="preview-stat-icon">📊</div>
                                        <div class="preview-stat-value"><?php echo $stats['attendance_rate']; ?>%</div>
                                        <div class="preview-stat-label">Attendance</div>
                                    </div>
                                    
                                    <div class="preview-stat" id="preview-stat-messages">
                                        <div class="preview-stat-icon">💬</div>
                                        <div class="preview-stat-value"><?php echo $stats['messages_today']; ?></div>
                                        <div class="preview-stat-label">Messages</div>
                                    </div>
                                </div>
                                
                                <!-- Navigation Preview -->
                                <div class="preview-section-title">Sidebar Navigation</div>
                                <div class="preview-nav">
                                    <div class="preview-nav-item" id="preview-nav-dashboard">
                                        <i class="fas fa-chart-line"></i>
                                        <span>Dashboard</span>
                                    </div>
                                    <div class="preview-nav-item" id="preview-nav-announcements">
                                        <i class="fas fa-bullhorn"></i>
                                        <span>Announcements</span>
                                    </div>
                                    <div class="preview-nav-item" id="preview-nav-events">
                                        <i class="fas fa-calendar-alt"></i>
                                        <span>Events</span>
                                    </div>
                                    <div class="preview-nav-item" id="preview-nav-members">
                                        <i class="fas fa-user-friends"></i>
                                        <span>Members</span>
                                    </div>
                                    <div class="preview-nav-item" id="preview-nav-signin">
                                        <i class="fas fa-id-card"></i>
                                        <span>Sign-In</span>
                                    </div>
                                    <div class="preview-nav-item" id="preview-nav-attendance">
                                        <i class="fas fa-check-circle"></i>
                                        <span>Attendance</span>
                                    </div>
                                    <div class="preview-nav-item" id="preview-nav-chat">
                                        <i class="fas fa-comments"></i>
                                        <span>Chat</span>
                                    </div>
                                </div>
                                
                                <!-- Modules Preview -->
                                <div class="preview-section-title">Admin Tools</div>
                                <div class="preview-modules">
                                    <div class="preview-module" id="preview-module-settings">
                                        <div class="preview-module-icon">⚙️</div>
                                        <div class="preview-module-name">Club Settings</div>
                                    </div>
                                    <div class="preview-module" id="preview-module-roles">
                                        <div class="preview-module-icon">🔐</div>
                                        <div class="preview-module-name">Roles</div>
                                    </div>
                                    <div class="preview-module" id="preview-module-analytics">
                                        <div class="preview-module-icon">📈</div>
                                        <div class="preview-module-name">Analytics</div>
                                    </div>
                                    <div class="preview-module" id="preview-module-chat-manage">
                                        <div class="preview-module-icon">💬</div>
                                        <div class="preview-module-name">Chat Rooms</div>
                                    </div>
                                </div>
                                
                                <div class="preview-note">
                                    💡 Preview updates in real-time as you toggle permissions
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
    
    <!-- Create Role Modal -->
    <div id="createRoleModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">Create New Role</div>
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                <input type="hidden" name="action" value="create_role">
                
                <div class="form-group">
                    <label class="form-label">Role Name *</label>
                    <input type="text" name="role_name" class="form-input" placeholder="e.g., Treasurer" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Description</label>
                    <textarea name="description" class="form-textarea" placeholder="What does this role do?"></textarea>
                </div>
                
                <div class="modal-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeCreateRoleModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Role</button>
                </div>
            </form>
        </div>
    </div>
    
    <?php require_once __DIR__ . "/includes/role_permissions/role_permissions-js.php" ?>
</body>
</html>