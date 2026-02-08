<style>
        .permissions-container {
            display: grid;
            grid-template-columns: 1fr 400px;
            gap: 2rem;
            align-items: start;
        }
        
        @media (max-width: 1400px) {
            .permissions-container {
                grid-template-columns: 1fr;
            }
            
            .preview-sidebar {
                position: relative !important;
                top: auto !important;
                max-height: none !important;
            }
        }
        
        .page-header {
            margin-bottom: 2rem;
        }
        
        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--primary);
            text-decoration: none;
            font-weight: 600;
            margin-bottom: 1rem;
        }
        
        .back-link:hover {
            text-decoration: underline;
        }
        
        .page-title {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        
        .page-subtitle {
            color: var(--text-secondary);
        }
        
        .alert {
            padding: 1rem;
            border-radius: var(--radius-md);
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
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
        
        .role-selector-card {
            background: var(--bg-card);
            border-radius: var(--radius-lg);
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        }
        
        .role-selector-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }
        
        .role-selector-header h3 {
            font-size: 1.125rem;
            font-weight: 600;
        }
        
        .role-tabs {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }
        
        .role-tab {
            padding: 0.75rem 1.5rem;
            background: var(--bg-tertiary);
            border: 2px solid transparent;
            border-radius: var(--radius-md);
            cursor: pointer;
            transition: all var(--transition);
            font-weight: 600;
            font-size: 0.875rem;
        }
        
        .role-tab:hover {
            border-color: var(--primary);
            background: var(--bg-card);
        }
        
        .role-tab.active {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            border-color: var(--primary);
        }
        
        .role-tab.president {
            background: rgba(239, 68, 68, 0.1);
            color: var(--danger);
        }
        
        .role-tab.president.active {
            background: var(--danger);
            color: white;
        }
        
        .role-info-banner {
            background: var(--bg-tertiary);
            padding: 1rem;
            border-radius: var(--radius-md);
            margin-bottom: 1.5rem;
        }
        
        .role-info-banner h4 {
            font-weight: 600;
            margin-bottom: 0.5rem;
        }
        
        .role-info-banner p {
            font-size: 0.875rem;
            color: var(--text-secondary);
        }
        
        .permissions-card {
            background: var(--bg-card);
            border-radius: var(--radius-lg);
            padding: 1.5rem;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        }
        
        .category-section {
            margin-bottom: 2rem;
        }
        
        .category-header {
            font-size: 1rem;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 1rem;
            padding-bottom: 0.75rem;
            border-bottom: 2px solid var(--border);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .permission-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1rem;
            border-bottom: 1px solid var(--border);
            transition: background var(--transition);
        }
        
        .permission-row:last-child {
            border-bottom: none;
        }
        
        .permission-row:hover {
            background: var(--bg-tertiary);
        }
        
        .permission-info {
            flex: 1;
        }
        
        .permission-name {
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 0.25rem;
        }
        
        .permission-desc {
            font-size: 0.875rem;
            color: var(--text-secondary);
        }
        
        .permission-toggle {
            position: relative;
            width: 52px;
            height: 28px;
        }
        
        .permission-toggle input {
            opacity: 0;
            width: 0;
            height: 0;
        }
        
        .toggle-slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            transition: .4s;
            border-radius: 28px;
        }
        
        .toggle-slider:before {
            position: absolute;
            content: "";
            height: 20px;
            width: 20px;
            left: 4px;
            bottom: 4px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
        }
        
        input:checked + .toggle-slider {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
        }
        
        input:checked + .toggle-slider:before {
            transform: translateX(24px);
        }
        
        input:disabled + .toggle-slider {
            opacity: 0.5;
            cursor: not-allowed;
        }
        
        /* PREVIEW SIDEBAR */
        .preview-sidebar {
            position: sticky;
            top: 20px;
            max-height: calc(100vh - 40px);
            overflow-y: auto;
        }
        
        .preview-container {
            background: var(--bg-card);
            border-radius: var(--radius-lg);
            padding: 1.5rem;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        }
        
        .preview-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid var(--border);
        }
        
        .preview-header h3 {
            font-size: 1.125rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .preview-badge {
            background: var(--success);
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 700;
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.7; }
        }
        
        .preview-dashboard {
            background: var(--bg-tertiary);
            border-radius: var(--radius-md);
            padding: 1rem;
        }
        
        .preview-section-title {
            font-size: 0.75rem;
            font-weight: 700;
            color: var(--text-secondary);
            text-transform: uppercase;
            margin-bottom: 0.75rem;
            letter-spacing: 0.5px;
        }
        
        .preview-stats {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.75rem;
            margin-bottom: 1.5rem;
        }
        
        .preview-stat {
            background: var(--bg-card);
            padding: 1rem;
            border-radius: var(--radius-md);
            text-align: center;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
            transition: all 0.3s;
        }
        
        .preview-stat.hidden {
            opacity: 0.2;
            filter: blur(2px);
            transform: scale(0.95);
        }
        
        .preview-stat-icon {
            font-size: 1.75rem;
            margin-bottom: 0.5rem;
        }
        
        .preview-stat-value {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 0.25rem;
        }
        
        .preview-stat-label {
            font-size: 0.7rem;
            color: var(--text-secondary);
            text-transform: uppercase;
            font-weight: 600;
        }
        
        .preview-nav {
            display: grid;
            gap: 0.5rem;
            margin-bottom: 1.5rem;
        }
        
        .preview-nav-item {
            background: var(--bg-card);
            padding: 0.875rem;
            border-radius: var(--radius-md);
            display: flex;
            align-items: center;
            gap: 0.75rem;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
            transition: all 0.3s;
        }
        
        .preview-nav-item.hidden {
            opacity: 0.2;
            filter: blur(2px);
            transform: translateX(-10px);
        }
        
        .preview-nav-item i {
            font-size: 1.25rem;
            width: 24px;
            text-align: center;
        }
        
        .preview-nav-item span {
            font-size: 0.875rem;
            font-weight: 600;
        }
        
        .preview-modules {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.5rem;
        }
        
        .preview-module {
            background: var(--bg-card);
            padding: 1rem;
            border-radius: var(--radius-md);
            text-align: center;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
            transition: all 0.3s;
        }
        
        .preview-module.hidden {
            opacity: 0.2;
            filter: blur(2px);
            transform: scale(0.9);
        }
        
        .preview-module-icon {
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
        }
        
        .preview-module-name {
            font-size: 0.75rem;
            font-weight: 600;
            line-height: 1.3;
        }
        
        .preview-note {
            background: rgba(59, 130, 246, 0.1);
            border: 1px solid var(--primary);
            color: var(--primary);
            padding: 0.75rem;
            border-radius: var(--radius-md);
            font-size: 0.75rem;
            text-align: center;
            margin-top: 1.5rem;
        }
        
        .btn-create-role {
            background: var(--success);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: var(--radius-md);
            border: none;
            font-weight: 600;
            cursor: pointer;
            transition: all var(--transition);
        }
        
        .btn-create-role:hover {
            background: #059669;
            transform: translateY(-2px);
        }
        
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
        }
        
        .modal.show {
            display: flex;
        }
        
        .modal-content {
            background: var(--bg-card);
            border-radius: var(--radius-xl);
            padding: 2rem;
            max-width: 500px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
        }
        
        .modal-header {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 1.5rem;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-label {
            display: block;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }
        
        .form-input, .form-textarea {
            width: 100%;
            padding: 0.875rem;
            border: 2px solid var(--border);
            border-radius: var(--radius-md);
            font-family: inherit;
        }
        
        .form-textarea {
            resize: vertical;
            min-height: 100px;
        }
        
        .modal-actions {
            display: flex;
            gap: 0.75rem;
            justify-content: flex-end;
        }
        
        .btn {
            padding: 0.875rem 1.5rem;
            border-radius: var(--radius-md);
            border: none;
            font-weight: 600;
            cursor: pointer;
            transition: all var(--transition);
        }
        
        .btn-secondary {
            background: var(--bg-tertiary);
            color: var(--text-primary);
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
        }
    </style>