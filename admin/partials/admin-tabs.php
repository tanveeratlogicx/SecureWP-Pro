<?php
/**
 * Tabbed Admin Interface for SecureWP Pro
 * Security hardened version
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Verify user permissions
if (!current_user_can('manage_options')) {
    wp_die(__('You do not have sufficient permissions to access this page.', 'securewp-pro'));
}

// Handle AJAX actions with proper security checks
if (isset($_POST['action']) && check_admin_referer('securewp_pro_admin_action', '_wpnonce')) {
    $action = sanitize_text_field($_POST['action']);
    
    switch ($action) {
        case 'unlock_ip':
            if (!current_user_can('manage_options')) {
                wp_die(__('Insufficient permissions.', 'securewp-pro'));
            }
            $ip = sanitize_text_field($_POST['ip']);
            $event_type = sanitize_text_field($_POST['event_type']);
            
            // Validate IP address format
            if (!filter_var($ip, FILTER_VALIDATE_IP)) {
                echo '<div class="notice notice-error"><p>' . esc_html__('Invalid IP address format.', 'securewp-pro') . '</p></div>';
                break;
            }
            
            // Validate event type
            $allowed_event_types = array('login', 'xmlrpc', 'contact_form_cf7', 'contact_form_fluentforms', 'contact_form_elementor', 'password_reset', 'registration');
            if (!in_array($event_type, $allowed_event_types, true)) {
                echo '<div class="notice notice-error"><p>' . esc_html__('Invalid event type.', 'securewp-pro') . '</p></div>';
                break;
            }
            
            if (method_exists($this, 'unlock_ip_address')) {
                $result = $this->unlock_ip_address($ip, $event_type);
                if ($result) {
                    echo '<div class="notice notice-success"><p>' . esc_html__('IP address unlocked successfully.', 'securewp-pro') . '</p></div>';
                } else {
                    echo '<div class="notice notice-error"><p>' . esc_html__('Failed to unlock IP address.', 'securewp-pro') . '</p></div>';
                }
            }
            break;
            
        case 'delete_lockout':
            if (!current_user_can('manage_options')) {
                wp_die(__('Insufficient permissions.', 'securewp-pro'));
            }
            $lockout_id = absint($_POST['lockout_id']);
            
            // Validate lockout ID
            if ($lockout_id <= 0) {
                echo '<div class="notice notice-error"><p>' . esc_html__('Invalid lockout ID.', 'securewp-pro') . '</p></div>';
                break;
            }
            
            if (method_exists($this, 'delete_lockout_by_id')) {
                $result = $this->delete_lockout_by_id($lockout_id);
                if ($result) {
                    echo '<div class="notice notice-success"><p>' . esc_html__('Lockout record deleted successfully.', 'securewp-pro') . '</p></div>';
                } else {
                    echo '<div class="notice notice-error"><p>' . esc_html__('Failed to delete lockout record.', 'securewp-pro') . '</p></div>';
                }
            }
            break;
            
        case 'clear_logs':
            if (!current_user_can('manage_options')) {
                wp_die(__('Insufficient permissions.', 'securewp-pro'));
            }
            if (method_exists($this, 'clear_security_logs')) {
                $result = $this->clear_security_logs();
                if ($result) {
                    echo '<div class="notice notice-success"><p>' . esc_html__('Security logs cleared successfully.', 'securewp-pro') . '</p></div>';
                } else {
                    echo '<div class="notice notice-error"><p>' . esc_html__('Failed to clear security logs.', 'securewp-pro') . '</p></div>';
                }
            }
            break;
            
        case 'export_logs':
            if (!current_user_can('manage_options')) {
                wp_die(__('Insufficient permissions.', 'securewp-pro'));
            }
            if (method_exists($this, 'export_security_logs')) {
                $this->export_security_logs();
            }
            break;
            
        default:
            echo '<div class="notice notice-error"><p>' . esc_html__('Invalid action.', 'securewp-pro') . '</p></div>';
    }
}

// Get data for tabs with secure queries
global $wpdb;
$lockouts_table = $wpdb->prefix . 'securewp_pro_lockouts';
$logs_table = $wpdb->prefix . 'securewp_pro_logs';

// Use prepared statements for security
$recent_lockouts = $wpdb->get_results($wpdb->prepare(
    "SELECT * FROM `{$lockouts_table}` ORDER BY created DESC LIMIT %d", 20
));
$recent_logs = $wpdb->get_results($wpdb->prepare(
    "SELECT * FROM `{$logs_table}` ORDER BY created DESC LIMIT %d", 50
));
$lockout_stats = $wpdb->get_results($wpdb->prepare(
    "SELECT event_type, COUNT(*) as count FROM `{$lockouts_table}` GROUP BY event_type LIMIT %d", 100
));
$log_stats = $wpdb->get_results($wpdb->prepare(
    "SELECT event_type, COUNT(*) as count FROM `{$logs_table}` GROUP BY event_type LIMIT %d", 100
));

$active_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'overview';
// Sync submenu pages to tabs if no explicit tab is provided
$current_page_slug = isset($_GET['page']) ? sanitize_text_field($_GET['page']) : '';
if (!isset($_GET['tab'])) {
    if ($current_page_slug === 'securewp-pro-lockouts') {
        $active_tab = 'lockouts';
    } elseif ($current_page_slug === 'securewp-pro-logs') {
        $active_tab = 'logs';
    }
}
?>

<div class="wrap">
    <h1><?php echo esc_html__('SecureWP Pro Dashboard', 'securewp-pro'); ?></h1>
    
    <!-- Tab Navigation -->
    <nav class="nav-tab-wrapper wp-clearfix">
        <a href="?page=securewp-pro&tab=overview" class="nav-tab <?php echo $active_tab === 'overview' ? 'nav-tab-active' : ''; ?>">
            <span class="dashicons dashicons-dashboard"></span> <?php echo esc_html__('Overview', 'securewp-pro'); ?>
        </a>
        <a href="?page=securewp-pro&tab=settings" class="nav-tab <?php echo $active_tab === 'settings' ? 'nav-tab-active' : ''; ?>">
            <span class="dashicons dashicons-admin-settings"></span> <?php echo esc_html__('Settings', 'securewp-pro'); ?>
        </a>
        <a href="?page=securewp-pro&tab=lockouts" class="nav-tab <?php echo $active_tab === 'lockouts' ? 'nav-tab-active' : ''; ?>">
            <span class="dashicons dashicons-lock"></span> <?php echo esc_html__('Lockouts', 'securewp-pro'); ?>
            <?php if (!empty($recent_lockouts)): ?>
                <span class="update-plugins"><span class="plugin-count"><?php echo count($recent_lockouts); ?></span></span>
            <?php endif; ?>
        </a>
        <a href="?page=securewp-pro&tab=logs" class="nav-tab <?php echo $active_tab === 'logs' ? 'nav-tab-active' : ''; ?>">
            <span class="dashicons dashicons-list-view"></span> <?php echo esc_html__('Security Logs', 'securewp-pro'); ?>
        </a>
        <a href="?page=securewp-pro&tab=statistics" class="nav-tab <?php echo $active_tab === 'statistics' ? 'nav-tab-active' : ''; ?>">
            <span class="dashicons dashicons-chart-bar"></span> <?php echo esc_html__('Statistics', 'securewp-pro'); ?>
        </a>
    </nav>

    <div class="tab-content">
        <?php
        switch ($active_tab) {
            case 'overview':
                include_once SECUREWP_PRO_PLUGIN_DIR . 'admin/partials/tab-overview.php';
                break;
            case 'settings':
                include_once SECUREWP_PRO_PLUGIN_DIR . 'admin/partials/tab-settings.php';
                break;
            case 'lockouts':
                include_once SECUREWP_PRO_PLUGIN_DIR . 'admin/partials/tab-lockouts.php';
                break;
            case 'logs':
                include_once SECUREWP_PRO_PLUGIN_DIR . 'admin/partials/tab-logs.php';
                break;
            case 'statistics':
                include_once SECUREWP_PRO_PLUGIN_DIR . 'admin/partials/tab-statistics.php';
                break;
            default:
                include_once SECUREWP_PRO_PLUGIN_DIR . 'admin/partials/tab-overview.php';
        }
        ?>
    </div>
</div>

<style>
.nav-tab-wrapper {
    border-bottom: 1px solid #ccd0d4;
    margin-bottom: 20px;
}

.nav-tab {
    position: relative;
    display: inline-block;
    padding: 10px 15px;
    margin-right: 5px;
    background: #f1f1f1;
    border: 1px solid #ccd0d4;
    border-bottom: none;
    text-decoration: none;
    color: #555;
}

.nav-tab:hover {
    background: #f9f9f9;
    color: #23282d;
}

.nav-tab-active {
    background: #fff;
    border-bottom: 1px solid #fff;
    margin-bottom: -1px;
}

.nav-tab .dashicons {
    margin-right: 5px;
    vertical-align: middle;
}

.update-plugins {
    position: absolute;
    top: -5px;
    right: -5px;
}

.plugin-count {
    background: #d63638;
    color: #fff;
    border-radius: 50%;
    width: 18px;
    height: 18px;
    display: inline-block;
    text-align: center;
    line-height: 18px;
    font-size: 11px;
    font-weight: 600;
}

.tab-content {
    background: #fff;
    border: 1px solid #ccd0d4;
    border-top: none;
    padding: 20px;
    min-height: 400px;
}

.quick-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.stat-card {
    background: #f8f9fa;
    border: 1px solid #e1e5e9;
    border-radius: 4px;
    padding: 20px;
    text-align: center;
}

.stat-number {
    font-size: 2em;
    font-weight: bold;
    color: #0073aa;
    margin-bottom: 5px;
}

.stat-label {
    color: #666;
    font-size: 0.9em;
}

.action-buttons {
    margin: 10px 0;
}

.action-buttons .button {
    margin-right: 10px;
}

.log-entry {
    border-left: 4px solid #ddd;
    padding: 10px;
    margin-bottom: 10px;
    background: #f9f9f9;
}

.log-entry.blocked {
    border-left-color: #dc3232;
}

.log-entry.failed {
    border-left-color: #f56e28;
}

.log-entry.success {
    border-left-color: #46b450;
}

.log-entry.info {
    border-left-color: #00a0d2;
}

.log-meta {
    font-size: 0.9em;
    color: #666;
    margin-top: 5px;
}

.ip-address {
    font-family: monospace;
    background: #f0f0f0;
    padding: 2px 6px;
    border-radius: 3px;
    font-size: 0.9em;
}

.lockout-status {
    display: inline-block;
    padding: 3px 8px;
    border-radius: 3px;
    font-size: 0.8em;
    font-weight: bold;
    text-transform: uppercase;
}

.lockout-status.active {
    background: #dc3232;
    color: #fff;
}

.lockout-status.expired {
    background: #46b450;
    color: #fff;
}

.lockout-status.permanent {
    background: #000;
    color: #fff;
}

@media (max-width: 768px) {
    .quick-stats {
        grid-template-columns: 1fr;
    }
    
    .nav-tab {
        display: block;
        margin-bottom: 5px;
    }
}
</style>

