<script>
        // Permission to preview element mapping
        const previewMap = {
            // Stats
            'view_members': ['preview-stat-members'],
            'view_events': ['preview-stat-events'],
            'view_attendance': ['preview-stat-attendance'],
            'access_chat': ['preview-stat-messages'],
            
            // Navigation
            'view_announcements': ['preview-nav-announcements'],
            'view_events': ['preview-nav-events'],
            'view_members': ['preview-nav-members'],
            'view_attendance': ['preview-nav-attendance'],
            'access_chat': ['preview-nav-chat'],
            
            // Modules
            'modify_club_settings': ['preview-module-settings'],
            'manage_roles': ['preview-module-roles'],
            'view_analytics': ['preview-module-analytics'],
            'manage_chat_rooms': ['preview-module-chat-manage'],
        };
        
        // Initialize preview based on current permissions
        function initializePreview() {
            const checkboxes = document.querySelectorAll('input[type="checkbox"][data-permission]');
            checkboxes.forEach(checkbox => {
                updatePreviewElement(checkbox.dataset.permission, checkbox.checked);
            });
        }
        
        // Toggle permission with AJAX
        function togglePermission(permissionKey, isEnabled) {
            const formData = new FormData();
            formData.append('csrf_token', '<?php echo $_SESSION['csrf_token']; ?>');
            formData.append('action', 'update_permission');
            formData.append('role_id', '<?php echo $selectedRoleId; ?>');
            formData.append('permission_key', permissionKey);
            formData.append('permission_value', isEnabled ? '1' : '0');
            
            fetch('', {
                method: 'POST',
                body: formData
            }).then(response => response.text())
              .then(data => {
                  // Update preview
                  updatePreviewElement(permissionKey, isEnabled);
              })
              .catch(error => console.error('Error:', error));
        }
        
        // Update preview elements
        function updatePreviewElement(permissionKey, isEnabled) {
            if (previewMap[permissionKey]) {
                previewMap[permissionKey].forEach(elementId => {
                    const element = document.getElementById(elementId);
                    if (element) {
                        if (isEnabled) {
                            element.classList.remove('hidden');
                            element.style.animation = 'fadeIn 0.3s ease-in';
                        } else {
                            element.classList.add('hidden');
                        }
                    }
                });
            }
        }
        
        // Modal functions
        function openCreateRoleModal() {
            document.getElementById('createRoleModal').classList.add('show');
        }
        
        function closeCreateRoleModal() {
            document.getElementById('createRoleModal').classList.remove('show');
        }
        
        // Close modal on outside click
        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.classList.remove('show');
            }
        }
        
        // Initialize on page load
        document.addEventListener('DOMContentLoaded', initializePreview);
        
        // Add animation styles
        const style = document.createElement('style');
        style.textContent = `
            @keyframes fadeIn {
                from { opacity: 0; transform: scale(0.95); }
                to { opacity: 1; transform: scale(1); }
            }
        `;
        document.head.appendChild(style);
    </script>