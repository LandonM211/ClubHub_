<style>
        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }
        
        .modal.show {
            display: flex;
        }
        
        .modal-content {
            background: var(--bg-card);
            border-radius: var(--radius-xl);
            padding: 2rem;
            max-width: 500px;
            width: 100%;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }
        
        .modal-title {
            font-size: 1.5rem;
            font-weight: 700;
        }
        
        .close-btn {
            background: none;
            border: none;
            font-size: 1.5rem;
            color: var(--text-secondary);
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 32px;
            height: 32px;
            border-radius: var(--radius-md);
            transition: all var(--transition);
        }
        
        .close-btn:hover {
            background: var(--bg-tertiary);
            color: var(--text-primary);
        }
        
        /* Dropdown Menu Styles */
        .dropdown {
            position: relative;
        }
        
        .dropdown-menu {
            display: none;
            position: absolute;
            top: 100%;
            right: 0;
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: var(--radius-lg);
            box-shadow: 0 8px 20px var(--shadow-lg);
            min-width: 250px;
            margin-top: 0.5rem;
            z-index: 100;
        }
        
        .dropdown-menu.show {
            display: block;
        }
        
        .dropdown-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem 1rem;
            cursor: pointer;
            transition: all var(--transition);
            color: var(--text-primary);
            text-decoration: none;
            border-bottom: 1px solid var(--border);
        }
        
        .dropdown-item:last-child {
            border-bottom: none;
        }
        
        .dropdown-item:hover {
            background: var(--bg-tertiary);
        }
        
        .dropdown-item i {
            width: 20px;
            text-align: center;
        }
        
        /* Club List Styles */
        .club-list-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem;
            background: var(--bg-tertiary);
            border-radius: var(--radius-md);
            margin-bottom: 0.5rem;
            cursor: pointer;
            transition: all var(--transition);
            border: 2px solid transparent;
        }
        
        .club-list-item:hover {
            border-color: var(--primary);
            background: var(--bg-card);
        }
        
        .club-list-item.active {
            border-color: var(--primary);
            background: var(--bg-card);
        }
        
        .club-list-icon {
            width: 48px;
            height: 48px;
            border-radius: var(--radius-md);
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.5rem;
        }
        
        .club-list-info {
            flex: 1;
        }
        
        .club-list-name {
            font-weight: 600;
            margin-bottom: 0.25rem;
        }
        
        .club-list-role {
            font-size: 0.75rem;
            color: var(--text-secondary);
        }
        
        /* Notification Panel Styles */
        .notification-panel {
            max-width: 400px;
        }
        
        .notification-item {
            padding: 1rem;
            background: var(--bg-tertiary);
            border-radius: var(--radius-md);
            margin-bottom: 0.75rem;
            border-left: 3px solid var(--primary);
        }
        
        .notification-item.success {
            border-left-color: var(--success);
        }
        
        .notification-item.warning {
            border-left-color: var(--warning);
        }
        
        .notification-item.error {
            border-left-color: var(--danger);
        }
        
        .notification-title {
            font-weight: 600;
            margin-bottom: 0.25rem;
        }
        
        .notification-message {
            font-size: 0.875rem;
            color: var(--text-secondary);
            margin-bottom: 0.5rem;
        }
        
        .notification-time {
            font-size: 0.75rem;
            color: var(--text-tertiary);
        }
        
        /* Alert Styles */
        .alert {
            padding: 1rem;
            border-radius: var(--radius-md);
            margin-bottom: 1rem;
            display: none;
        }
        
        .alert.show {
            display: block;
        }
        
        .alert-success {
            background: rgba(16, 185, 129, 0.1);
            border: 1px solid var(--success);
            color: var(--success);
        }
        
        .alert-error {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid var(--danger);
            color: var(--danger);
        }
        
        .alert-info {
            background: rgba(59, 130, 246, 0.1);
            border: 1px solid var(--primary);
            color: var(--primary);
        }
        
        /* Form Styles */
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-label {
            display: block;
            font-size: 0.875rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }
        
        .form-input, .form-textarea {
            width: 100%;
            padding: 0.875rem 1rem;
            background: var(--bg-tertiary);
            border: 1px solid var(--border);
            border-radius: var(--radius-md);
            font-size: 0.875rem;
            color: var(--text-primary);
            font-family: inherit;
            transition: all var(--transition);
        }
        
        .form-textarea {
            resize: vertical;
            min-height: 100px;
        }
        
        .form-input:focus, .form-textarea:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }
        
        .btn-full {
            width: 100%;
            justify-content: center;
        }
        
        /* Members Section Styles */
        .tabs-container {
            border-bottom: 2px solid var(--border);
        }

        .tabs {
            display: flex;
            gap: 0.5rem;
        }

        .tab-btn {
            padding: 0.875rem 1.5rem;
            background: transparent;
            border: none;
            border-bottom: 3px solid transparent;
            color: var(--text-secondary);
            font-size: 0.875rem;
            font-weight: 600;
            cursor: pointer;
            transition: all var(--transition);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .tab-btn:hover {
            color: var(--text-primary);
            background: var(--bg-tertiary);
        }

        .tab-btn.active {
            color: var(--primary);
            border-bottom-color: var(--primary);
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        .badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.25rem 0.5rem;
            background: var(--primary);
            color: white;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
            min-width: 20px;
        }

        .search-input-wrapper {
            position: relative;
        }

        .search-input-wrapper i {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-secondary);
        }

        .member-card {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1.25rem;
            border-bottom: 1px solid var(--border);
            transition: all var(--transition);
            cursor: pointer;
        }

        .member-card:last-child {
            border-bottom: none;
        }

        .member-card:hover {
            background: var(--bg-tertiary);
        }

        .member-avatar {
            width: 56px;
            height: 56px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 700;
            font-size: 1.25rem;
        }

        .member-info {
            flex: 1;
        }

        .member-name {
            font-weight: 600;
            font-size: 1rem;
            margin-bottom: 0.25rem;
        }

        .member-email {
            color: var(--text-secondary);
            font-size: 0.875rem;
            margin-bottom: 0.25rem;
        }

        .member-role {
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            padding: 0.25rem 0.75rem;
            background: var(--bg-tertiary);
            border-radius: var(--radius-md);
            font-size: 0.75rem;
            font-weight: 600;
        }

        .member-role.president {
            background: rgba(239, 68, 68, 0.1);
            color: var(--danger);
        }

        .member-actions {
            display: flex;
            gap: 0.5rem;
        }

        .join-request-card {
            display: flex;
            flex-direction: column;
            gap: 1rem;
            padding: 1.5rem;
            border: 2px solid var(--border);
            border-radius: var(--radius-lg);
            margin-bottom: 1rem;
            transition: all var(--transition);
        }

        .join-request-card:hover {
            border-color: var(--primary);
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.1);
        }

        .join-request-header {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .join-request-info {
            flex: 1;
        }

        .join-request-name {
            font-weight: 600;
            font-size: 1.125rem;
            margin-bottom: 0.25rem;
        }

        .join-request-email {
            color: var(--text-secondary);
            font-size: 0.875rem;
        }

        .join-request-meta {
            display: flex;
            gap: 1.5rem;
            padding: 1rem;
            background: var(--bg-tertiary);
            border-radius: var(--radius-md);
            font-size: 0.875rem;
        }

        .join-request-meta-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--text-secondary);
        }

        .join-request-actions {
            display: flex;
            gap: 0.75rem;
            justify-content: flex-end;
        }

        .empty-state {
            text-align: center;
            padding: 3rem 2rem;
            color: var(--text-secondary);
        }

        .empty-state i {
            font-size: 3rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }

        .empty-state p {
            font-size: 1rem;
            margin-bottom: 0;
        }

        .role-select-group {
            margin-bottom: 1.5rem;
        }

        .role-option {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 1rem;
            border: 2px solid var(--border);
            border-radius: var(--radius-md);
            margin-bottom: 0.5rem;
            cursor: pointer;
            transition: all var(--transition);
        }

        .role-option:hover {
            border-color: var(--primary);
            background: var(--bg-tertiary);
        }

        .role-option input[type="radio"] {
            width: 20px;
            height: 20px;
            cursor: pointer;
        }

        .role-option-info {
            flex: 1;
        }

        .role-option-name {
            font-weight: 600;
            margin-bottom: 0.25rem;
        }

        .role-option-description {
            font-size: 0.75rem;
            color: var(--text-secondary);
        }
    </style>