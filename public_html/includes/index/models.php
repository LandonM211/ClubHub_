
    <!-- Club Switcher Modal -->
    <div id="clubSwitcherModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Switch Club</h2>
                <button class="close-btn" onclick="closeClubSwitcher()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div id="clubList">
                <?php foreach ($clubs as $club): ?>
                <div class="club-list-item <?php echo $club['id'] == $activeClub['id'] ? 'active' : ''; ?>" 
                     onclick="switchClub(<?php echo $club['id']; ?>)">
                    <div class="club-list-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="club-list-info">
                        <div class="club-list-name"><?php echo htmlspecialchars($club['name']); ?></div>
                        <div class="club-list-role"><?php echo htmlspecialchars($club['role_name']); ?></div>
                    </div>
                    <?php if ($club['id'] == $activeClub['id']): ?>
                    <i class="fas fa-check" style="color: var(--success);"></i>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
            <div style="margin-top: 1.5rem; padding-top: 1.5rem; border-top: 1px solid var(--border);">
                <button class="btn btn-primary btn-full" onclick="window.location.href='no-clubs.php'">
                    <i class="fas fa-plus"></i> Join or Create Club
                </button>
            </div>
        </div>
    </div>

    <!-- Profile Modal -->
    <div id="profileModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">My Profile</h2>
                <button class="close-btn" onclick="closeProfile()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div id="profileAlert" class="alert"></div>
            <form id="profileForm">
                <div class="form-group">
                    <label class="form-label">First Name</label>
                    <input type="text" class="form-input" id="profileFirstName" value="<?php echo htmlspecialchars($user['first_name']); ?>" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Last Name</label>
                    <input type="text" class="form-input" id="profileLastName" value="<?php echo htmlspecialchars($user['last_name']); ?>" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Email</label>
                    <input type="email" class="form-input" id="profileEmail" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" required>
                </div>
                <button type="submit" class="btn btn-primary btn-full">
                    <i class="fas fa-save"></i> Save Changes
                </button>
            </form>
            <div style="margin-top: 1.5rem; padding-top: 1.5rem; border-top: 1px solid var(--border);">
                <h3 style="font-weight: 600; margin-bottom: 1rem;">Change Password</h3>
                <form id="passwordForm">
                    <div class="form-group">
                        <label class="form-label">Current Password</label>
                        <input type="password" class="form-input" id="currentPassword" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">New Password</label>
                        <input type="password" class="form-input" id="newPassword" minlength="8" required>
                    </div>
                    <button type="submit" class="btn btn-secondary btn-full">
                        <i class="fas fa-key"></i> Change Password
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Member Details Modal -->
    <div id="memberDetailsModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Member Details</h2>
                <button class="close-btn" onclick="closeMemberDetails()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div id="memberDetailsContent">
                <!-- Content will be loaded dynamically -->
            </div>
        </div>
    </div>

    <!-- Join Request Approval Modal -->
    <div id="joinRequestModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Review Join Request</h2>
                <button class="close-btn" onclick="closeJoinRequestModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div id="joinRequestAlert" class="alert"></div>
            <div id="joinRequestContent">
                <!-- Content will be loaded dynamically -->
            </div>
        </div>
    </div>