<script>
    // ===========================
    // GENERAL FUNCTIONALITY
    // ===========================
    
    // Toggle Notifications
    function toggleNotifications() {
        const panel = document.getElementById('notificationPanel');
        const userMenu = document.getElementById('userMenu');
        userMenu.classList.remove('show');
        panel.classList.toggle('show');
    }
    
    // Toggle User Menu
    function toggleUserMenu() {
        const menu = document.getElementById('userMenu');
        const notifPanel = document.getElementById('notificationPanel');
        notifPanel.classList.remove('show');
        menu.classList.toggle('show');
    }
    
    // Close dropdowns when clicking outside
    document.addEventListener('click', function(event) {
        if (!event.target.closest('.dropdown')) {
            document.querySelectorAll('.dropdown-menu').forEach(menu => {
                menu.classList.remove('show');
            });
        }
    });
    
    // Open Club Switcher
    function openClubSwitcher() {
        document.getElementById('clubSwitcherModal').classList.add('show');
    }
    
    function closeClubSwitcher() {
        document.getElementById('clubSwitcherModal').classList.remove('show');
    }
    
    // Switch Club
    async function switchClub(clubId) {
        try {
            const response = await fetch('api/switch_club.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': CSRF_TOKEN },
                body: JSON.stringify({ club_id: clubId })
            });
            
            const data = await response.json();
            
            if (data.success) {
                window.location.reload();
            } else {
                alert('Error switching club: ' + (data.message || 'Unknown error'));
            }
        } catch (error) {
            console.error('Error switching club:', error);
            alert('Error switching club. Please try again.');
        }
    }
    
    // Open Profile
    function openProfile() {
        document.getElementById('userMenu').classList.remove('show');
        document.getElementById('profileModal').classList.add('show');
    }
    
    function closeProfile() {
        document.getElementById('profileModal').classList.remove('show');
    }
    
    // Open Settings
    function openSettings() {
        document.getElementById('userMenu').classList.remove('show');
        navigateTo('theme');
    }
    
    // Open My Clubs
    function openMyClubs() {
        document.getElementById('userMenu').classList.remove('show');
        openClubSwitcher();
    }
    
    // Profile Form Submission
    document.getElementById('profileForm')?.addEventListener('submit', async (e) => {
        e.preventDefault();
        
        const data = {
            first_name: document.getElementById('profileFirstName').value,
            last_name: document.getElementById('profileLastName').value,
            email: document.getElementById('profileEmail').value
        };
        
        try {
            const response = await fetch('api/user_preferences.php?action=update-profile', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': CSRF_TOKEN },
                body: JSON.stringify(data)
            });
            
            const result = await response.json();
            
            if (result.success) {
                showProfileAlert('success', 'Profile updated successfully!');
                setTimeout(() => window.location.reload(), 1500);
            } else {
                showProfileAlert('error', result.message || 'Failed to update profile');
            }
        } catch (error) {
            console.error('Error updating profile:', error);
            showProfileAlert('error', 'Error updating profile. Please try again.');
        }
    });
    
    // Password Form Submission
    document.getElementById('passwordForm')?.addEventListener('submit', async (e) => {
        e.preventDefault();
        
        const data = {
            current_password: document.getElementById('currentPassword').value,
            new_password: document.getElementById('newPassword').value
        };
        
        try {
            const response = await fetch('api/user_preferences.php?action=change-password', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': CSRF_TOKEN },
                body: JSON.stringify(data)
            });
            
            const result = await response.json();
            
            if (result.success) {
                showProfileAlert('success', 'Password changed successfully!');
                document.getElementById('passwordForm').reset();
            } else {
                showProfileAlert('error', result.message || 'Failed to change password');
            }
        } catch (error) {
            console.error('Error changing password:', error);
            showProfileAlert('error', 'Error changing password. Please try again.');
        }
    });
    
    function showProfileAlert(type, message) {
        const alert = document.getElementById('profileAlert');
        alert.className = `alert alert-${type} show`;
        alert.innerHTML = `<i class="fas fa-${type === 'success' ? 'check' : 'exclamation'}-circle"></i> ${message}`;
        setTimeout(() => alert.classList.remove('show'), 5000);
    }
    
    // Mark All Notifications Read
    async function markAllRead() {
        try {
            const response = await fetch('api/notifications.php?action=mark-all-read', {
            method: 'POST',
            headers: { 'X-CSRF-Token': CSRF_TOKEN }
        });
            
            const data = await response.json();
            
            if (data.success) {
                document.getElementById('notificationList').innerHTML = `
                    <div class="empty-state" style="padding: 2rem;">
                        <i class="fas fa-bell-slash"></i>
                        <p>No new notifications</p>
                    </div>
                `;
                document.querySelector('.notification-dot')?.remove();
            }
        } catch (error) {
            console.error('Error marking notifications read:', error);
        }
    }
    
    // Search Functionality
    document.getElementById('searchInput')?.addEventListener('keyup', function(e) {
        if (e.key === 'Enter') {
            const query = this.value.trim();
            if (query) {
                performSearch(query);
            }
        }
    });
    
    function performSearch(query) {
        console.log('Searching for:', query);
        // Implement search functionality based on current view
        alert('Search functionality coming soon! Query: ' + query);
    }
    
    // Close modals when clicking outside
    window.addEventListener('click', function(event) {
        if (event.target.classList.contains('modal')) {
            event.target.classList.remove('show');
        }
    });
    
    // ===========================
    // MEMBERS SECTION JAVASCRIPT
    // ===========================
    
    let currentMembers = [];
    let currentJoinRequests = [];
    let selectedRequestId = null;

    // Switch between member tabs
    function switchMemberTab(tabName) {
        // Update tab buttons
        document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
        event.target.closest('.tab-btn').classList.add('active');
        
        // Update tab content
        document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));
        document.getElementById(tabName + '-tab').classList.add('active');
        
        // Load data if needed
        if (tabName === 'join-requests') {
            loadJoinRequests();
        }
    }

    // Load members list
    async function loadMembers() {
        try {
            const response = await fetch(`api/members.php?action=list&club_id=${window.APP_CONTEXT.activeClub.id}`);
            const data = await response.json();
            
            if (data.success) {
                currentMembers = data.data;
                renderMembers(currentMembers);
                populateRoleFilter(currentMembers);
                document.getElementById('memberCount').textContent = currentMembers.length;
            } else {
                document.getElementById('membersList').innerHTML = `
                    <div class="empty-state">
                        <i class="fas fa-exclamation-circle"></i>
                        <p>Error loading members: ${data.message}</p>
                    </div>
                `;
            }
        } catch (error) {
            console.error('Error loading members:', error);
            document.getElementById('membersList').innerHTML = `
                <div class="empty-state">
                    <i class="fas fa-exclamation-circle"></i>
                    <p>Error loading members</p>
                </div>
            `;
        }
    }

    // Render members list
    function renderMembers(members) {
        const container = document.getElementById('membersList');
        
        if (members.length === 0) {
            container.innerHTML = `
                <div class="empty-state">
                    <i class="fas fa-users-slash"></i>
                    <p>No members found</p>
                </div>
            `;
            return;
        }
        
        container.innerHTML = members.map(member => {
            const initials = (member.first_name[0] + member.last_name[0]).toUpperCase();
            const isPresident = member.is_president == 1;
            
            return `
                <div class="member-card" onclick="viewMemberDetails(${member.user_id})">
                    <div class="member-avatar">${initials}</div>
                    <div class="member-info">
                        <div class="member-name">
                            ${escapeHtml(member.first_name + ' ' + member.last_name)}
                            ${isPresident ? '<i class="fas fa-crown" style="color: var(--warning); margin-left: 0.5rem;"></i>' : ''}
                        </div>
                        <div class="member-email">${escapeHtml(member.email)}</div>
                        <div class="member-role ${isPresident ? 'president' : ''}">
                            <i class="fas fa-shield-alt"></i>
                            ${escapeHtml(member.role_name)}
                        </div>
                    </div>
                    ${window.APP_CONTEXT.permissions.manage_members && !isPresident ? `
                    <div class="member-actions" onclick="event.stopPropagation();">
                        <button class="btn btn-sm btn-secondary" onclick="editMemberRole(${member.user_id}, '${escapeHtml(member.first_name + ' ' + member.last_name)}')">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-sm btn-danger" onclick="removeMember(${member.user_id}, '${escapeHtml(member.first_name + ' ' + member.last_name)}')">
                            <i class="fas fa-user-times"></i>
                        </button>
                    </div>
                    ` : ''}
                </div>
            `;
        }).join('');
    }

    // Populate role filter
    function populateRoleFilter(members) {
        const roleFilter = document.getElementById('roleFilterSelect');
        const roles = [...new Set(members.map(m => m.role_name))];
        
        roleFilter.innerHTML = '<option value="">All Roles</option>' + 
            roles.map(role => `<option value="${escapeHtml(role)}">${escapeHtml(role)}</option>`).join('');
    }

    // Filter members
    function filterMembers() {
        const searchTerm = document.getElementById('memberSearchInput').value.toLowerCase();
        const roleFilter = document.getElementById('roleFilterSelect').value;
        
        const filtered = currentMembers.filter(member => {
            const matchesSearch = !searchTerm || 
                member.first_name.toLowerCase().includes(searchTerm) ||
                member.last_name.toLowerCase().includes(searchTerm) ||
                member.email.toLowerCase().includes(searchTerm);
            
            const matchesRole = !roleFilter || member.role_name === roleFilter;
            
            return matchesSearch && matchesRole;
        });
        
        renderMembers(filtered);
    }

    // Event listeners for filtering
    document.getElementById('memberSearchInput')?.addEventListener('input', filterMembers);
    document.getElementById('roleFilterSelect')?.addEventListener('change', filterMembers);

    // Load join requests
    async function loadJoinRequests() {
        try {
            const response = await fetch(`api/club_join.php?action=pending&club_id=${window.APP_CONTEXT.activeClub.id}`);
            const data = await response.json();
            
            if (data.success) {
                currentJoinRequests = data.data;
                renderJoinRequests(currentJoinRequests);
                updateJoinRequestBadges(currentJoinRequests.length);
            } else {
                document.getElementById('joinRequestsList').innerHTML = `
                    <div class="empty-state">
                        <i class="fas fa-exclamation-circle"></i>
                        <p>Error loading join requests</p>
                    </div>
                `;
            }
        } catch (error) {
            console.error('Error loading join requests:', error);
            document.getElementById('joinRequestsList').innerHTML = `
                <div class="empty-state">
                    <i class="fas fa-exclamation-circle"></i>
                    <p>Error loading join requests</p>
                </div>
            `;
        }
    }

    // Render join requests
    function renderJoinRequests(requests) {
        const container = document.getElementById('joinRequestsList');
        
        if (requests.length === 0) {
            container.innerHTML = `
                <div class="empty-state">
                    <i class="fas fa-user-check"></i>
                    <p>No pending join requests</p>
                </div>
            `;
            return;
        }
        
        container.innerHTML = requests.map(request => {
            const initials = (request.first_name[0] + request.last_name[0]).toUpperCase();
            const requestDate = new Date(request.created_at).toLocaleDateString('en-US', {
                month: 'short',
                day: 'numeric',
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
            
            return `
                <div class="join-request-card">
                    <div class="join-request-header">
                        <div class="member-avatar">${initials}</div>
                        <div class="join-request-info">
                            <div class="join-request-name">${escapeHtml(request.first_name + ' ' + request.last_name)}</div>
                            <div class="join-request-email">${escapeHtml(request.email)}</div>
                        </div>
                    </div>
                    
                    <div class="join-request-meta">
                        <div class="join-request-meta-item">
                            <i class="fas fa-calendar"></i>
                            <span>Requested: ${requestDate}</span>
                        </div>
                        <div class="join-request-meta-item">
                            <i class="fas fa-key"></i>
                            <span>Code: ${escapeHtml(request.access_code_used)}</span>
                        </div>
                    </div>
                    
                    ${request.message ? `
                    <div style="padding: 1rem; background: var(--bg-tertiary); border-radius: var(--radius-md); font-size: 0.875rem;">
                        <strong>Message:</strong><br>
                        ${escapeHtml(request.message)}
                    </div>
                    ` : ''}
                    
                    <div class="join-request-actions">
                        <button class="btn btn-danger" onclick="rejectJoinRequest(${request.id}, '${escapeHtml(request.first_name + ' ' + request.last_name)}')">
                            <i class="fas fa-times"></i> Reject
                        </button>
                        <button class="btn btn-success" onclick="openApprovalModal(${request.id}, '${escapeHtml(request.first_name + ' ' + request.last_name)}')">
                            <i class="fas fa-check"></i> Approve
                        </button>
                    </div>
                </div>
            `;
        }).join('');
    }

    // Update join request badges
    function updateJoinRequestBadges(count) {
        const badges = ['joinRequestBadge', 'joinRequestTabBadge', 'membersNavBadge'];
        badges.forEach(badgeId => {
            const badge = document.getElementById(badgeId);
            if (badge) {
                if (count > 0) {
                    badge.textContent = count;
                    badge.style.display = 'inline-flex';
                } else {
                    badge.style.display = 'none';
                }
            }
        });
    }

    // Show join requests section
    function showJoinRequestsSection() {
        document.getElementById('joinRequestsTab')?.click();
    }

    // Open approval modal
    async function openApprovalModal(requestId, memberName) {
        selectedRequestId = requestId;
        
        try {
            // Load available roles
            const response = await fetch(`api/roles.php?action=list&club_id=${window.APP_CONTEXT.activeClub.id}`);
            const data = await response.json();
            
            if (data.success && data.data) {
                const roles = data.data.filter(role => !role.is_president);
                
                document.getElementById('joinRequestContent').innerHTML = `
                    <p style="margin-bottom: 1.5rem;">Select a role for <strong>${escapeHtml(memberName)}</strong>:</p>
                    
                    <div class="role-select-group">
                        ${roles.map(role => `
                            <label class="role-option">
                                <input type="radio" name="selectedRole" value="${role.id}">
                                <div class="role-option-info">
                                    <div class="role-option-name">${escapeHtml(role.role_name)}</div>
                                    ${role.description ? `<div class="role-option-description">${escapeHtml(role.description)}</div>` : ''}
                                </div>
                            </label>
                        `).join('')}
                    </div>
                    
                    <div style="display: flex; gap: 0.75rem; justify-content: flex-end;">
                        <button class="btn btn-secondary" onclick="closeJoinRequestModal()">
                            Cancel
                        </button>
                        <button class="btn btn-success" onclick="approveJoinRequest()">
                            <i class="fas fa-check"></i> Approve Member
                        </button>
                    </div>
                `;
                
                document.getElementById('joinRequestModal').classList.add('show');
            }
        } catch (error) {
            console.error('Error loading roles:', error);
            alert('Error loading roles. Please try again.');
        }
    }

    // Approve join request
    async function approveJoinRequest() {
        const selectedRole = document.querySelector('input[name="selectedRole"]:checked');
        
        if (!selectedRole) {
            showJoinRequestAlert('error', 'Please select a role');
            return;
        }
        
        try {
            const response = await fetch('api/club_join.php?action=approve', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': CSRF_TOKEN },
            body: JSON.stringify({
                request_id: selectedRequestId,
                role_id: parseInt(selectedRole.value)
            })
        });
            
            const data = await response.json();
            
            if (data.success) {
                showJoinRequestAlert('success', 'Member approved successfully!');
                setTimeout(() => {
                    closeJoinRequestModal();
                    loadJoinRequests();
                    loadMembers();
                }, 1500);
            } else {
                showJoinRequestAlert('error', data.message || 'Failed to approve member');
            }
        } catch (error) {
            console.error('Error approving member:', error);
            showJoinRequestAlert('error', 'Error approving member. Please try again.');
        }
    }

    // Reject join request
    async function rejectJoinRequest(requestId, memberName) {
        const reason = prompt(`Why are you rejecting ${memberName}'s request? (Optional)`);
        
        if (reason === null) return;
        
        try {
            const response = await fetch('api/club_join.php?action=reject', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': CSRF_TOKEN },
            body: JSON.stringify({
                request_id: requestId,
                reason: reason
            })
        });
            
            const data = await response.json();
            
            if (data.success) {
                loadJoinRequests();
            } else {
                alert('Error: ' + (data.message || 'Failed to reject request'));
            }
        } catch (error) {
            console.error('Error rejecting request:', error);
            alert('Error rejecting request. Please try again.');
        }
    }

    // Close join request modal
    function closeJoinRequestModal() {
        document.getElementById('joinRequestModal').classList.remove('show');
        selectedRequestId = null;
    }

    // Show join request alert
    function showJoinRequestAlert(type, message) {
        const alert = document.getElementById('joinRequestAlert');
        alert.className = `alert alert-${type} show`;
        alert.innerHTML = `<i class="fas fa-${type === 'success' ? 'check' : 'exclamation'}-circle"></i> ${message}`;
        setTimeout(() => alert.classList.remove('show'), 5000);
    }

    // View member details
    function viewMemberDetails(userId) {
        console.log('View member details:', userId);
    }

    // Edit member role
    function editMemberRole(userId, memberName) {
        alert('Role editing feature coming soon for ' + memberName);
    }

    // Remove member
    async function removeMember(userId, memberName) {
        if (!confirm(`Are you sure you want to remove ${memberName} from the club?`)) {
            return;
        }
        
        try {
            const response = await fetch('api/members.php?action=remove', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': CSRF_TOKEN },
            body: JSON.stringify({
                club_id: window.APP_CONTEXT.activeClub.id,
                user_id: userId
            })
        });
            
            const data = await response.json();
            
            if (data.success) {
                alert('Member removed successfully');
                loadMembers();
            } else {
                alert('Error: ' + (data.message || 'Failed to remove member'));
            }
        } catch (error) {
            console.error('Error removing member:', error);
            alert('Error removing member. Please try again.');
        }
    }

    // Close member details modal
    function closeMemberDetails() {
        document.getElementById('memberDetailsModal').classList.remove('show');
    }

    // Helper function to escape HTML
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // Initialize members view
    function initializeMembersView() {
        loadMembers();
        if (window.APP_CONTEXT.permissions.manage_members) {
            loadJoinRequests();
        }
    }

    // Add navigation listener for members view
    document.querySelector('.nav-item[data-view="members"]')?.addEventListener('click', function() {
        initializeMembersView();
    });
    
    // Load join requests on page load if user has permission
    if (window.APP_CONTEXT.permissions.manage_members) {
        // Check for join requests every 30 seconds
        loadJoinRequests();
        setInterval(loadJoinRequests, 30000);
    }

    // ===========================
    // ROLES VIEW INTEGRATION
    // ===========================
    
    // Load roles iframe when roles view is activated
    document.querySelector('.nav-item[data-view="roles"]')?.addEventListener('click', function() {
        const iframe = document.getElementById('rolesIframe');
        
        // Only load iframe once (lazy loading for performance)
        if (!iframe.src || iframe.src === '') {
            iframe.src = 'manage-role-permissions.php';
            console.log('✅ Roles management loaded');
        }
    });
</script>