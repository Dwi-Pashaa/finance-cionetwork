<style>
    :root {
        --cio-primary: #2563EB;
        --cio-primary-dark: #0F172A;
        --cio-primary-light: #3B82F6;
        --cio-primary-subtle: #EFF6FF;
        --cio-primary-border: #DBEAFE;
        --cio-primary-gradient: linear-gradient(135deg, #1E40AF 0%, #3B82F6 100%);
        
        --cio-page: #F8FAFC;
        --cio-surface: #FFFFFF;
        --cio-secondary-surface: #F1F5F9;
        --cio-border: #E2E8F0;
        --cio-border-hover: #CBD5E1;
        --cio-input-border: #CBD5E1;
        
        --cio-text: #0F172A;
        --cio-text-secondary: #475569;
        --cio-text-muted: #94A3B8;
        
        --cio-success-soft: #ECFDF5;
        --cio-success: #10B981;
        --cio-success-text: #065F46;
        
        --cio-danger-soft: #FEF2F2;
        --cio-danger: #EF4444;
        --cio-danger-text: #991B1B;
        
        --cio-warning-soft: #FFFBEB;
        --cio-warning: #F59E0B;
        --cio-warning-text: #92400E;

        --cio-info-soft: #F0F9FF;
        --cio-info: #0284C7;
        --cio-info-text: #075985;
        
        --cio-radius-sm: 8px;
        --cio-radius-md: 12px;
        --cio-radius-lg: 16px;
        --cio-radius-xl: 20px;
        
        --cio-shadow-sm: 0 1px 3px 0 rgb(0 0 0 / 0.05), 0 1px 2px -1px rgb(0 0 0 / 0.05);
        --cio-shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.07), 0 2px 4px -2px rgb(0 0 0 / 0.05);
        --cio-shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.08), 0 4px 6px -4px rgb(0 0 0 / 0.04);
        --cio-shadow-glow-primary: 0 8px 20px -4px rgba(37, 99, 235, 0.25);
        --cio-shadow-glow-success: 0 8px 20px -4px rgba(16, 185, 129, 0.25);
        --cio-shadow-glow-danger: 0 8px 20px -4px rgba(239, 68, 68, 0.25);
    }

    body {
        background: var(--cio-page);
        color: var(--cio-text);
        font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        font-size: 14px;
        -webkit-font-smoothing: antialiased;
    }

    /* Welcome Hero Banner */
    .cio-hero-banner {
        background: linear-gradient(135deg, #0F172A 0%, #1E3A8A 50%, #2563EB 100%);
        border-radius: var(--cio-radius-xl);
        color: #FFFFFF;
        padding: 24px 30px;
        position: relative;
        overflow: hidden;
        box-shadow: 0 12px 30px -8px rgba(15, 23, 42, 0.35);
        margin-bottom: 24px;
        border: 1px solid rgba(255, 255, 255, 0.1);
    }

    .cio-hero-banner::before {
        content: '';
        position: absolute;
        top: -60px;
        right: -60px;
        width: 240px;
        height: 240px;
        background: radial-gradient(circle, rgba(59, 130, 246, 0.25) 0%, rgba(255, 255, 255, 0) 70%);
        border-radius: 50%;
        pointer-events: none;
    }

    .cio-hero-banner::after {
        content: '';
        position: absolute;
        bottom: -80px;
        right: 120px;
        width: 180px;
        height: 180px;
        background: radial-gradient(circle, rgba(16, 185, 129, 0.2) 0%, rgba(255, 255, 255, 0) 70%);
        border-radius: 50%;
        pointer-events: none;
    }

    .cio-hero-title {
        font-size: 24px;
        font-weight: 800;
        letter-spacing: -0.025em;
        margin-bottom: 6px;
    }

    .cio-hero-subtitle {
        color: rgba(255, 255, 255, 0.85);
        font-size: 14px;
        font-weight: 500;
        max-width: 600px;
    }

    .cio-hero-badge {
        background: rgba(255, 255, 255, 0.15);
        backdrop-filter: blur(8px);
        border: 1px solid rgba(255, 255, 255, 0.2);
        color: #FFFFFF;
        border-radius: 9999px;
        padding: 6px 14px;
        font-size: 12px;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }

    .cio-hero-btn {
        background: #FFFFFF;
        color: #0F172A;
        font-weight: 700;
        padding: 10px 18px;
        border-radius: var(--cio-radius-md);
        border: none;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        display: inline-flex;
        align-items: center;
        gap: 8px;
        text-decoration: none !important;
    }

    .cio-hero-btn:hover {
        background: #F8FAFC;
        color: #2563EB;
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(0, 0, 0, 0.2);
    }

    .cio-hero-btn-outline {
        background: rgba(255, 255, 255, 0.12);
        backdrop-filter: blur(8px);
        border: 1px solid rgba(255, 255, 255, 0.25);
        color: #FFFFFF;
        font-weight: 600;
        padding: 10px 18px;
        border-radius: var(--cio-radius-md);
        transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        display: inline-flex;
        align-items: center;
        gap: 8px;
        text-decoration: none !important;
    }

    .cio-hero-btn-outline:hover {
        background: rgba(255, 255, 255, 0.25);
        color: #FFFFFF;
        transform: translateY(-2px);
    }

    /* Cards */
    .cio-card {
        background: var(--cio-surface);
        border: 1px solid var(--cio-border) !important;
        border-radius: var(--cio-radius-lg);
        box-shadow: var(--cio-shadow-sm);
        overflow: hidden;
        transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
    }

    .cio-card:hover {
        box-shadow: var(--cio-shadow-md);
        border-color: var(--cio-border-hover) !important;
    }

    .cio-card-header {
        background: #FFFFFF !important;
        border-bottom: 1px solid #E2E8F0 !important;
        padding: 20px 24px !important;
        display: flex !important;
        align-items: center !important;
        gap: 16px !important;
    }

    .cio-title {
        color: #0F172A !important;
        font-size: 18px !important;
        font-weight: 700 !important;
        letter-spacing: -0.015em !important;
        margin: 0 !important;
        line-height: 1.3 !important;
    }

    .cio-subtitle {
        color: #64748B !important;
        font-size: 13px !important;
        font-weight: 500 !important;
        margin-top: 2px !important;
        line-height: 1.4 !important;
    }

    /* Form Controls & Inputs */
    .form-control,
    .form-select {
        border: 1px solid #CBD5E1 !important;
        border-radius: 8px !important;
        padding: 9px 14px !important;
        font-size: 14px !important;
        font-weight: 500 !important;
        color: #0F172A !important;
        background-color: #FFFFFF !important;
        box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04) !important;
        transition: all 0.15s ease-in-out !important;
    }

    .form-control:focus,
    .form-select:focus {
        border-color: #2563EB !important;
        box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.15) !important;
        outline: none !important;
    }

    .form-label {
        font-size: 13px !important;
        font-weight: 600 !important;
        color: #334155 !important;
        margin-bottom: 6px !important;
    }

    textarea.form-control {
        min-height: 90px !important;
        padding: 10px 14px !important;
    }

    .cio-card .card-body,
    .card .card-body {
        padding: 24px !important;
    }
    .cio-metric-card {
        min-height: 135px;
        position: relative;
    }

    .cio-metric-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        border-top-left-radius: var(--cio-radius-lg);
        border-top-right-radius: var(--cio-radius-lg);
    }

    .cio-metric-card.success::before {
        background: linear-gradient(90deg, #10B981, #34D399);
    }

    .cio-metric-card.danger::before {
        background: linear-gradient(90deg, #EF4444, #F87171);
    }

    .cio-metric-card.info::before {
        background: linear-gradient(90deg, #2563EB, #60A5FA);
    }

    .cio-metric-card.warning::before {
        background: linear-gradient(90deg, #F59E0B, #FBBF24);
    }

    .cio-metric-card .card-body {
        padding: 20px 22px;
    }

    .cio-metric-label {
        color: var(--cio-text-secondary);
        font-size: 12px;
        font-weight: 700;
        letter-spacing: 0.03em;
        text-transform: uppercase;
    }

    .cio-metric-value {
        color: var(--cio-text);
        font-size: 24px;
        font-variant-numeric: tabular-nums;
        font-weight: 800;
        letter-spacing: -0.02em;
        line-height: 1.25;
        margin-top: 8px;
    }

    .cio-metric-trend {
        font-size: 12px;
        font-weight: 600;
        margin-top: 6px;
        display: inline-flex;
        align-items: center;
        gap: 4px;
    }

    /* Icon Avatars */
    .cio-icon-avatar {
        align-items: center;
        background: var(--cio-primary-subtle);
        border: 1px solid var(--cio-primary-border);
        border-radius: var(--cio-radius-md);
        color: var(--cio-primary);
        display: inline-flex;
        height: 46px;
        justify-content: center;
        width: 46px;
        flex-shrink: 0;
        transition: transform 0.2s ease;
    }

    .cio-card:hover .cio-icon-avatar {
        transform: scale(1.06);
    }

    .cio-icon-avatar.success {
        background: var(--cio-success-soft);
        border-color: #A7F3D0;
        color: var(--cio-success);
        box-shadow: var(--cio-shadow-glow-success);
    }

    .cio-icon-avatar.danger {
        background: var(--cio-danger-soft);
        border-color: #FECACA;
        color: var(--cio-danger);
        box-shadow: var(--cio-shadow-glow-danger);
    }

    .cio-icon-avatar.warning {
        background: var(--cio-warning-soft);
        border-color: #FDE68A;
        color: var(--cio-warning-text);
    }

    .cio-icon-avatar.info {
        background: var(--cio-primary-subtle);
        border-color: var(--cio-primary-border);
        color: var(--cio-primary);
        box-shadow: var(--cio-shadow-glow-primary);
    }

    .cio-icon {
        height: 18px;
        width: 18px;
    }

    .cio-icon-lg {
        height: 22px;
        width: 22px;
    }

    /* Buttons */
    .cio-btn {
        align-items: center !important;
        border-radius: 8px !important;
        display: inline-flex !important;
        font-weight: 600 !important;
        font-size: 14px !important;
        gap: 8px !important;
        justify-content: center !important;
        padding: 9px 20px !important;
        transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1) !important;
        text-decoration: none !important;
        cursor: pointer !important;
    }

    .cio-btn-primary {
        background-color: #2563EB !important;
        border: 1px solid #2563EB !important;
        color: #FFFFFF !important;
        box-shadow: 0 2px 4px rgba(37, 99, 235, 0.2) !important;
    }

    .cio-btn-primary:hover {
        background-color: #1D4ED8 !important;
        border-color: #1D4ED8 !important;
        color: #FFFFFF !important;
        transform: translateY(-1px) !important;
        box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3) !important;
    }

    .btn-light.cio-btn,
    .cio-btn-secondary {
        background-color: #FFFFFF !important;
        border: 1px solid #CBD5E1 !important;
        color: #475569 !important;
    }

    .btn-light.cio-btn:hover,
    .cio-btn-secondary:hover {
        background-color: #F8FAFC !important;
        border-color: #94A3B8 !important;
        color: #0F172A !important;
    }

    /* Enhanced Table Action Buttons */
    .cio-action-btn {
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        width: 34px !important;
        height: 34px !important;
        padding: 0 !important;
        border-radius: 8px !important;
        transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1) !important;
        box-shadow: 0 1px 2px rgba(15, 23, 42, 0.05) !important;
        cursor: pointer !important;
        text-decoration: none !important;
    }

    .cio-action-btn svg {
        width: 18px !important;
        height: 18px !important;
        display: block !important;
        flex-shrink: 0 !important;
    }

    /* Edit Button (Warning / Soft Amber) */
    .cio-action-btn.btn-edit,
    .cio-action-btn.btn-outline-warning {
        background-color: #FFFBEB !important;
        border: 1px solid #FCD34D !important;
        color: #D97706 !important;
    }

    .cio-action-btn.btn-edit svg,
    .cio-action-btn.btn-outline-warning svg {
        stroke: #D97706 !important;
    }

    .cio-action-btn.btn-edit:hover,
    .cio-action-btn.btn-outline-warning:hover {
        background-color: #F59E0B !important;
        border-color: #D97706 !important;
        color: #FFFFFF !important;
        box-shadow: 0 4px 10px rgba(245, 158, 11, 0.3) !important;
        transform: translateY(-1px) !important;
    }

    .cio-action-btn.btn-edit:hover svg,
    .cio-action-btn.btn-outline-warning:hover svg {
        stroke: #FFFFFF !important;
    }

    /* Delete Button (Danger / Soft Red) */
    .cio-action-btn.btn-delete,
    .cio-action-btn.btn-outline-danger {
        background-color: #FEF2F2 !important;
        border: 1px solid #FCA5A5 !important;
        color: #DC2626 !important;
    }

    .cio-action-btn.btn-delete svg,
    .cio-action-btn.btn-outline-danger svg {
        stroke: #DC2626 !important;
    }

    .cio-action-btn.btn-delete:hover,
    .cio-action-btn.btn-outline-danger:hover {
        background-color: #EF4444 !important;
        border-color: #DC2626 !important;
        color: #FFFFFF !important;
        box-shadow: 0 4px 10px rgba(239, 68, 68, 0.3) !important;
        transform: translateY(-1px) !important;
    }

    .cio-action-btn.btn-delete:hover svg,
    .cio-action-btn.btn-outline-danger:hover svg {
        stroke: #FFFFFF !important;
    }

    /* Primary / Light Action Button */
    .cio-action-btn.btn-info,
    .cio-action-btn.btn-light {
        background-color: #EFF6FF !important;
        border: 1px solid #93C5FD !important;
        color: #2563EB !important;
    }

    .cio-action-btn.btn-info svg,
    .cio-action-btn.btn-light svg {
        stroke: #2563EB !important;
    }

    .cio-action-btn.btn-info:hover,
    .cio-action-btn.btn-light:hover {
        background-color: #2563EB !important;
        border-color: #1D4ED8 !important;
        color: #FFFFFF !important;
        box-shadow: 0 4px 10px rgba(37, 99, 235, 0.3) !important;
        transform: translateY(-1px) !important;
    }

    .cio-action-btn.btn-info:hover svg,
    .cio-action-btn.btn-light:hover svg {
        stroke: #FFFFFF !important;
    }

    /* Action button container group */
    .action-btn-group {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
    }

    .filter-toolbar {
        background: var(--cio-secondary-surface);
        border-bottom: 1px solid var(--cio-border);
        padding: 18px 24px;
    }

    /* Tables */
    .cio-table thead th {
        background: #F8FAFC !important;
        color: #475569 !important;
        font-size: 11px !important;
        font-weight: 700 !important;
        letter-spacing: 0.06em !important;
        padding: 14px 16px !important;
        text-transform: uppercase !important;
        border-bottom: 2px solid #E2E8F0 !important;
    }

    .cio-table tbody td {
        border-color: #F1F5F9 !important;
        color: #334155 !important;
        padding: 14px 16px !important;
        vertical-align: middle !important;
    }

    .cio-table tbody tr:hover {
        background: #F8FAFC !important;
    }

    .cio-currency {
        color: var(--cio-text);
        font-variant-numeric: tabular-nums;
        font-weight: 700;
    }

    /* Recent Lists & Items */
    .cio-recent-list {
        display: grid;
        gap: 10px;
    }

    .cio-recent-item {
        align-items: center;
        background: var(--cio-surface);
        border: 1px solid var(--cio-border);
        border-radius: var(--cio-radius-md);
        display: flex;
        gap: 14px;
        padding: 14px 16px;
        transition: all 0.15s ease;
    }

    .cio-recent-item:hover {
        border-color: var(--cio-primary-border);
        background: var(--cio-primary-subtle);
        transform: translateX(2px);
    }

    /* Status Dot Animation */
    .status-dot-animated {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        display: inline-block;
        box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7);
        animation: statusPulse 2s infinite;
    }

    @keyframes statusPulse {
        0% {
            transform: scale(0.95);
            box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7);
        }
        70% {
            transform: scale(1);
            box-shadow: 0 0 0 6px rgba(16, 185, 129, 0);
        }
        100% {
            transform: scale(0.95);
            box-shadow: 0 0 0 0 rgba(16, 185, 129, 0);
        }
    }

    /* Navbar Link Styling Enhancements */
    .navbar-nav .nav-link {
        color: var(--cio-text-secondary) !important;
        transition: all 0.2s ease !important;
    }

    .navbar-nav .nav-link:hover {
        color: var(--cio-primary) !important;
        background-color: var(--cio-primary-subtle) !important;
    }

    .navbar-nav .nav-item.active > .nav-link,
    .navbar-nav .nav-link.active {
        color: var(--cio-primary) !important;
        background-color: var(--cio-primary-subtle) !important;
        font-weight: 700 !important;
    }

    /* Dropdown item styling */
    .dropdown-menu .dropdown-item {
        color: var(--cio-text-secondary);
        font-weight: 500;
        transition: all 0.15s ease;
        border-radius: var(--cio-radius-sm);
    }

    .dropdown-menu .dropdown-item:hover,
    .dropdown-menu .dropdown-item.active {
        color: var(--cio-primary);
        background-color: var(--cio-primary-subtle);
        font-weight: 600;
    }

    /* Log Tab Pills Styling */
    .cio-log-tabs {
        background: #F1F5F9;
        padding: 4px;
        border-radius: var(--cio-radius-md);
        gap: 4px;
        border: 1px solid var(--cio-border);
    }

    .cio-log-tabs .nav-link {
        color: var(--cio-text-secondary);
        font-size: 13px;
        font-weight: 600;
        padding: 7px 16px;
        border-radius: var(--cio-radius-sm);
        border: none;
        background: transparent;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .cio-log-tabs .nav-link:hover {
        color: var(--cio-primary);
        background: rgba(255, 255, 255, 0.6);
    }

    .cio-log-tabs .nav-link.active {
        color: #FFFFFF;
        background: var(--cio-primary);
        box-shadow: 0 2px 8px rgba(37, 99, 235, 0.35);
        font-weight: 700;
    }

    .cio-log-tabs .nav-link.active .badge-tab {
        background: rgba(255, 255, 255, 0.25);
        color: #FFFFFF;
    }

    .cio-log-tabs .badge-tab {
        font-size: 11px;
        font-weight: 700;
        padding: 2px 8px;
        border-radius: 9999px;
        background: #E2E8F0;
        color: var(--cio-text-secondary);
        transition: all 0.2s ease;
    }

    /* Detailed Activity Log Styling */
    .cio-log-list {
        display: flex;
        flex-direction: column;
        gap: 12px;
    }

    .cio-log-item {
        background: #FFFFFF;
        border: 1px solid var(--cio-border);
        border-radius: var(--cio-radius-md);
        padding: 16px 18px;
        transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        position: relative;
    }

    .cio-log-item:hover {
        border-color: var(--cio-primary-border);
        box-shadow: 0 4px 12px rgba(15, 23, 42, 0.06);
        transform: translateY(-1px);
    }

    .cio-log-item.event-created {
        border-left: 4px solid var(--cio-success);
    }

    .cio-log-item.event-updated {
        border-left: 4px solid var(--cio-warning);
    }

    .cio-log-item.event-deleted {
        border-left: 4px solid var(--cio-danger);
    }

    .cio-log-item.event-other {
        border-left: 4px solid var(--cio-primary);
    }

    .cio-log-badge {
        font-size: 11px;
        font-weight: 700;
        letter-spacing: 0.04em;
        padding: 4px 10px;
        border-radius: 9999px;
        text-transform: uppercase;
    }

    .cio-log-badge.badge-created {
        background: var(--cio-success-soft);
        color: var(--cio-success);
        border: 1px solid #A7F3D0;
    }

    .cio-log-badge.badge-updated {
        background: var(--cio-warning-soft);
        color: var(--cio-warning-text);
        border: 1px solid #FDE68A;
    }

    .cio-log-badge.badge-deleted {
        background: var(--cio-danger-soft);
        color: var(--cio-danger-text);
        border: 1px solid #FECACA;
    }

    .cio-log-badge.badge-other {
        background: var(--cio-primary-subtle);
        color: var(--cio-primary);
        border: 1px solid var(--cio-primary-border);
    }

    .cio-log-amount-pill {
        background: #F8FAFC;
        border: 1px solid #E2E8F0;
        border-radius: var(--cio-radius-sm);
        padding: 3px 10px;
        font-weight: 700;
        font-size: 13px;
        color: #0F172A;
    }

    .cio-log-meta-item {
        font-size: 12px;
        color: var(--cio-text-secondary);
        display: inline-flex;
        align-items: center;
        gap: 5px;
        background: var(--cio-secondary-surface);
        padding: 3px 9px;
        border-radius: 6px;
        border: 1px solid var(--cio-border);
    }

    .modal-blur {
        backdrop-filter: blur(6px);
    }

    /* Complete Responsive Media Queries */
    @media (max-width: 991.98px) {
        .cio-hero-banner {
            padding: 22px 24px;
        }

        .cio-hero-title {
            font-size: 20px;
        }

        .cio-card-header {
            padding: 16px 20px !important;
        }

        .cio-title {
            font-size: 16px !important;
        }
    }

    @media (max-width: 767.98px) {
        .cio-hero-banner {
            padding: 18px 20px;
            border-radius: 14px;
        }

        .cio-hero-title {
            font-size: 18px;
        }

        .cio-hero-subtitle {
            font-size: 13px;
        }

        .cio-hero-btn,
        .cio-hero-btn-outline {
            width: 100%;
            justify-content: center;
            padding: 9px 14px;
            font-size: 13px;
        }

        .cio-card-header {
            padding: 14px 16px !important;
        }

        .filter-toolbar {
            padding: 14px 16px !important;
        }

        .cio-metric-card .card-body {
            padding: 16px 18px;
        }

        .cio-metric-value {
            font-size: 20px;
        }

        .cio-metric-label {
            font-size: 11px;
        }

        .cio-trading-filter {
            width: 100% !important;
            display: flex !important;
            overflow-x: auto !important;
            white-space: nowrap !important;
            padding: 3px !important;
            -webkit-overflow-scrolling: touch;
            scrollbar-width: none;
        }

        .cio-trading-filter::-webkit-scrollbar {
            display: none;
        }

        .cio-trading-filter .btn-tab {
            padding: 6px 10px !important;
            font-size: 11px !important;
            flex: 1 0 auto;
            text-align: center;
        }

        .cio-recent-item {
            padding: 12px 14px;
            gap: 10px;
        }

        .cio-recent-title {
            font-size: 13px;
        }

        .cio-log-item {
            padding: 14px;
        }

        .cio-log-item .d-flex.align-items-start {
            flex-direction: column;
            gap: 10px !important;
        }

        .cio-log-item .text-end {
            text-align: left !important;
            width: 100%;
            margin-top: 6px;
        }
    }

    @media (max-width: 575.98px) {
        .cio-recent-item {
            flex-wrap: wrap;
        }

        .cio-recent-item .cio-currency {
            width: 100%;
            text-align: left !important;
            font-size: 14px;
            margin-top: 2px;
        }

        .cio-log-amount-pill {
            font-size: 12px;
            padding: 2px 8px;
        }

        .cio-table thead th,
        .cio-table tbody td {
            padding: 10px 12px !important;
            font-size: 12px !important;
        }
    }
</style>

