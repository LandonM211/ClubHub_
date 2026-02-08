<header class="top-bar">
                <button class="mobile-menu-toggle" onclick="toggleSidebar()">
                    <i class="fas fa-bars"></i>
                </button>

                <div class="search-bar">
                    <i class="fas fa-search"></i>
                    <input type="text" placeholder="Search..." id="searchInput" />
                </div>

                <div class="top-bar-actions">
                    <div class="dropdown">
                        <button class="icon-btn" onclick="toggleNotifications()" id="notificationBtn">
                            <i class="fas fa-bell"></i>
                            <?php if ($unreadCount > 0): ?>
                            <span class="notification-dot"></span>
                            <?php endif; ?>
                        </button>
                        <div class="dropdown-menu notification-panel" id="notificationPanel">
                            <div style="padding: 1rem; border-bottom: 1px solid var(--border);">
                                <h3 style="font-weight: 600; margin-bottom: 0.5rem;">Notifications</h3>
                                <p style="font-size: 0.75rem; color: var(--text-secondary);"><?php echo $unreadCount; ?> unread</p>
                            </div>
                            <div id="notificationList" style="padding: 1rem; max-height: 400px; overflow-y: auto;">
                                <?php if (empty($notifications)): ?>
                                <div class="empty-state" style="padding: 2rem;">
                                    <i class="fas fa-bell-slash"></i>
                                    <p>No new notifications</p>
                                </div>
                                <?php else: ?>
                                    <?php foreach ($notifications as $notif): ?>
                                    <div class="notification-item <?php echo htmlspecialchars($notif['type']); ?>">
                                        <div class="notification-title"><?php echo htmlspecialchars($notif['title']); ?></div>
                                        <div class="notification-message"><?php echo htmlspecialchars($notif['message']); ?></div>
                                        <div class="notification-time">
                                            <i class="fas fa-clock"></i> 
                                            <?php 
                                            $time = strtotime($notif['created_at']);
                                            echo date('M j, g:i A', $time);
                                            ?>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                            <div style="padding: 1rem; border-top: 1px solid var(--border); text-align: center;">
                                <button class="btn btn-secondary btn-full" onclick="markAllRead()">
                                    <i class="fas fa-check-double"></i> Mark All Read
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="dropdown">
                        <div class="user-menu" onclick="toggleUserMenu()">
                            <div class="user-avatar" id="userAvatar">
                                <?php echo strtoupper($user['first_name'][0] . $user['last_name'][0]); ?>
                            </div>
                            <div class="user-info">
                                <div class="user-name" id="userName"><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></div>
                                <div class="user-role"><?php echo htmlspecialchars($activeClub['role_name']); ?></div>
                            </div>
                        </div>
                        <div class="dropdown-menu" id="userMenu">
                            <a href="#" class="dropdown-item" onclick="openProfile(); return false;">
                                <i class="fas fa-user"></i>
                                <span>My Profile</span>
                            </a>
                            <a href="#" class="dropdown-item" onclick="openSettings(); return false;">
                                <i class="fas fa-cog"></i>
                                <span>Settings</span>
                            </a>
                            <a href="#" class="dropdown-item" onclick="openMyClubs(); return false;">
                                <i class="fas fa-users"></i>
                                <span>My Clubs</span>
                            </a>
                            <a href="#" class="dropdown-item" style="color: var(--danger);" onclick="logout(); return false;">
                                <i class="fas fa-sign-out-alt"></i>
                                <span>Logout</span>
                            </a>
                        </div>
                    </div>
                </div>
            </header>