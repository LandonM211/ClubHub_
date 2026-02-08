<!-- Navigation -->
            <nav class="nav-menu">
                <div class="nav-section">
                    <div class="nav-section-title">Main</div>
                    <a href="#" class="nav-item active" data-view="dashboard">
                        <i class="fas fa-chart-line"></i>
                        <span>Dashboard</span>
                    </a>
                    <?php if ($permissions['view_announcements'] ?? false): ?>
                    <a href="#" class="nav-item" data-view="announcements">
                        <i class="fas fa-bullhorn"></i>
                        <span>Announcements</span>
                    </a>
                    <?php endif; ?>
                    <?php if ($permissions['view_events'] ?? false): ?>
                    <a href="#" class="nav-item" data-view="events">
                        <i class="fas fa-calendar-alt"></i>
                        <span>Events</span>
                    </a>
                    <?php endif; ?>
                    <?php if ($permissions['view_members'] ?? false): ?>
                    <a href="#" class="nav-item" data-view="members">
                        <i class="fas fa-user-friends"></i>
                        <span>Members</span>
                        <?php if ($permissions['manage_members'] ?? false): ?>
                        <span id="membersNavBadge" class="badge" style="display: none; margin-left: auto;">0</span>
                        <?php endif; ?>
                    </a>
                    <?php endif; ?>
                    <a href="#" class="nav-item" data-view="signin">
                        <i class="fas fa-id-card"></i>
                        <span>Sign-In</span>
                    </a>
                </div>

                <div class="nav-section">
                    <div class="nav-section-title">Tools</div>
                    <?php if ($permissions['view_attendance'] ?? false): ?>
                    <a href="#" class="nav-item" data-view="attendance">
                        <i class="fas fa-check-circle"></i>
                        <span>Attendance</span>
                    </a>
                    <?php endif; ?>
                    <?php if ($permissions['access_chat'] ?? false): ?>
                    <a href="#" class="nav-item" data-view="chat">
                        <i class="fas fa-comments"></i>
                        <span>Chat</span>
                    </a>
                    <?php endif; ?>
                </div>

                <?php if (($permissions['modify_club_settings'] ?? false) || ($permissions['manage_roles'] ?? false)): ?>
                <div class="nav-section">
                    <div class="nav-section-title">Settings</div>
                    <?php if ($permissions['modify_club_settings'] ?? false): ?>
                    <a href="#" class="nav-item" data-view="club-settings">
                        <i class="fas fa-cog"></i>
                        <span>Club Settings</span>
                    </a>
                    <?php endif; ?>
                    <?php if ($permissions['manage_roles'] ?? false): ?>
                    <a href="#" class="nav-item" data-view="roles">
                        <i class="fas fa-user-shield"></i>
                        <span>Roles</span>
                    </a>
                    <?php endif; ?>
                    <a href="#" class="nav-item" data-view="theme">
                        <i class="fas fa-palette"></i>
                        <span>Theme</span>
                    </a>
                </div>
                <?php endif; ?>
            </nav>