<?php

// index.php - COMPLETE VERSION WITH MEMBERS SECTION

session_start();
require_once __DIR__ . '/database/db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Load user data
require_once __DIR__ . "/includes/index/loadUserData.php";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Club Hub - <?php echo htmlspecialchars($activeClub['name']); ?></title>
    
    <!-- Fonts & Icons -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Styles -->
    <link rel="stylesheet" href="styles.css">
    
    <?php require_once __DIR__ . '/includes/index/index-css.php' ?>
    
    <!-- App Context -->
    <script>
        window.APP_CONTEXT = {
            user: {
                id: <?php echo $user['id']; ?>,
                firstName: <?php echo json_encode($user['first_name']); ?>,
                lastName: <?php echo json_encode($user['last_name']); ?>,
                fullName: <?php echo json_encode($user['first_name'] . ' ' . $user['last_name']); ?>
            },
            activeClub: {
                id: <?php echo $activeClub['id']; ?>,
                name: <?php echo json_encode($activeClub['name']); ?>,
                roleName: <?php echo json_encode($activeClub['role_name']); ?>,
                isPresident: <?php echo $activeClub['is_president'] ? 'true' : 'false'; ?>
            },
            clubs: <?php echo json_encode($clubs); ?>,
            permissions: <?php echo json_encode($permissions); ?>,
            csrfToken: <?php echo json_encode(generateCSRFToken()); ?>,
            unreadNotifications: <?php echo $unreadCount; ?>
        };
    </script>
</head>
<body>
    <div class="app-container">
        <!-- Sidebar -->
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <div class="logo">
                    <i class="fas fa-users-cog"></i>
                    <span class="logo-text">Club Hub</span>
                </div>
            </div>

            <!-- Club Selector -->
            <div class="club-selector" onclick="openClubSwitcher()">
                <div class="club-logo">
                    <i class="fas fa-users"></i>
                </div>
                <div class="club-info">
                    <h3 id="clubName"><?php echo htmlspecialchars($activeClub['name']); ?></h3>
                    <p id="clubRole"><?php echo htmlspecialchars($activeClub['role_name']); ?></p>
                </div>
                <i class="fas fa-chevron-down"></i>
            </div>

            <?php require_once __DIR__ . "/includes/index/navigation.php" ?>

            <div class="sidebar-footer">
                <button class="theme-btn" onclick="toggleTheme()" id="themeBtn">
                    <i class="fas fa-moon" id="themeIcon"></i>
                </button>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="main-content">

        <?php require_once __DIR__ . '/includes/index/header.php' ?>
            
            <!-- Content Area -->
            <div class="content-area" id="contentArea">
                <!-- Dashboard View -->
                <div id="dashboard-view" class="view-content">
                    <div class="page-header">
                        <div>
                            <h1 class="page-title">Dashboard</h1>
                            <p class="page-subtitle">Welcome back, <?php echo htmlspecialchars($user['first_name']); ?>!</p>
                        </div>
                    </div>

                    <div class="stats-grid">
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-users"></i>
                            </div>
                            <div class="stat-content">
                                <div class="stat-value" id="totalMembers">—</div>
                                <div class="stat-label">Total Members</div>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon accent">
                                <i class="fas fa-calendar-alt"></i>
                            </div>
                            <div class="stat-content">
                                <div class="stat-value" id="upcomingEvents">—</div>
                                <div class="stat-label">Upcoming Events</div>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon success">
                                <i class="fas fa-chart-bar"></i>
                            </div>
                            <div class="stat-content">
                                <div class="stat-value" id="attendanceRate">—</div>
                                <div class="stat-label">Attendance Rate</div>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon secondary">
                                <i class="fas fa-comments"></i>
                            </div>
                            <div class="stat-content">
                                <div class="stat-value" id="messagesToday">—</div>
                                <div class="stat-label">Messages Today</div>
                            </div>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header">
                            <h2 class="card-title">Recent Announcements</h2>
                        </div>
                        <div id="dashboardAnnouncements">
                            <div class="loading">Loading announcements...</div>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header">
                            <h2 class="card-title">Upcoming Events</h2>
                        </div>
                        <div id="dashboardEvents">
                            <div class="loading">Loading events...</div>
                        </div>
                    </div>
                </div>

                <!-- Other views will be loaded dynamically -->
                <div id="announcements-view" class="view-content hidden"></div>
                <div id="events-view" class="view-content hidden"></div>
                
                <!-- MEMBERS VIEW - COMPLETE IMPLEMENTATION -->
                <div id="members-view" class="view-content hidden">
                    <div class="page-header">
                        <div>
                            <h1 class="page-title">Members</h1>
                            <p class="page-subtitle">Manage club members and join requests</p>
                        </div>
                        <?php if ($permissions['manage_members'] ?? false): ?>
                        <button class="btn btn-primary" onclick="showJoinRequestsSection()">
                            <i class="fas fa-user-plus"></i> 
                            <span>Join Requests</span>
                            <span id="joinRequestBadge" class="badge" style="display: none;">0</span>
                        </button>
                        <?php endif; ?>
                    </div>

                    <!-- Tab Navigation -->
                    <div class="tabs-container" style="margin-bottom: 2rem;">
                        <div class="tabs">
                            <button class="tab-btn active" onclick="switchMemberTab('active-members')">
                                <i class="fas fa-users"></i> Active Members
                            </button>
                            <?php if ($permissions['manage_members'] ?? false): ?>
                            <button class="tab-btn" onclick="switchMemberTab('join-requests')" id="joinRequestsTab">
                                <i class="fas fa-user-clock"></i> Join Requests
                                <span id="joinRequestTabBadge" class="badge" style="display: none; margin-left: 0.5rem;">0</span>
                            </button>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Active Members Tab -->
                    <?php require_once __DIR__ . "/includes/index/members.php" ?>

                    <!-- Join Requests Tab -->
                    <?php if ($permissions['manage_members'] ?? false): ?>
                    <div id="join-requests-tab" class="tab-content">
                        <div class="card">
                            <div class="card-header">
                                <h2 class="card-title">
                                    <i class="fas fa-user-clock"></i> Pending Join Requests
                                </h2>
                            </div>
                            <div id="joinRequestsList">
                                <div class="loading">Loading join requests...</div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                
                <div id="signin-view" class="view-content hidden"></div>
                <div id="attendance-view" class="view-content hidden"></div>
                <div id="chat-view" class="view-content hidden"></div>
                <div id="club-settings-view" class="view-content hidden"></div>
                <div id="roles-view" class="view-content hidden">
                    <iframe id="rolesIframe" 
                            src="manage-role-permissions.php" 
                            style="width: 100%; height: calc(100vh - 100px); border: none; border-radius: var(--radius-lg); opacity: 0; transition: opacity 0.3s;"
                            frameborder="0"
                            onload="this.style.opacity = 1;"></iframe>
                </div>
                <div id="theme-view" class="view-content hidden"></div>
            </div>
        </main>
    </div>

    <?php require_once __DIR__ . '/includes/index/models.php' ?>

    <script src="app.js"></script>
    
    <?php require_once __DIR__ . "/includes/index/index-js.php" ?>
</body>
</html>