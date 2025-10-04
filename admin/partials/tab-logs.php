<?php
/**
 * Logs Tab - Enhanced Security Logs with Actions
 */

// Handle log actions
if (isset($_POST['action']) && wp_verify_nonce($_POST['_wpnonce'], 'securewp_pro_log_action')) {
    $action = sanitize_text_field($_POST['action']);
    
    switch ($action) {
        case 'clear_logs':
            $this->clear_security_logs();
            echo '<div class="notice notice-success"><p>' . __('Security logs cleared successfully.', 'securewp-pro') . '</p></div>';
            break;
            
        case 'export_logs':
            $this->export_security_logs();
            break;
            
        case 'lockout_ip':
            $ip = sanitize_text_field($_POST['ip']);
            $this->lockout_ip_manually($ip);
            echo '<div class="notice notice-success"><p>' . __('IP address locked out successfully.', 'securewp-pro') . '</p></div>';
            break;
    }
}

// Get filter parameters
$filter_type = isset($_GET['type']) ? sanitize_text_field($_GET['type']) : 'all';
$filter_action = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : 'all';
$filter_ip = isset($_GET['ip']) ? sanitize_text_field($_GET['ip']) : '';
$filter_date = isset($_GET['date']) ? sanitize_text_field($_GET['date']) : '';

// Get recent logs with filters
global $wpdb;
$logs_table = $wpdb->prefix . 'securewp_pro_logs';

$where_conditions = array('1=1');
$where_params = array();

if ($filter_type !== 'all') {
    $where_conditions[] = 'event_type = %s';
    $where_params[] = $filter_type;
}

if ($filter_action !== 'all') {
    $where_conditions[] = 'event_action = %s';
    $where_params[] = $filter_action;
}

if (!empty($filter_ip)) {
    $where_conditions[] = 'ip_address = %s';
    $where_params[] = $filter_ip;
}

if (!empty($filter_date)) {
    $where_conditions[] = 'DATE(created) = %s';
    $where_params[] = $filter_date;
}

$where_clause = implode(' AND ', $where_conditions);
$query = "SELECT * FROM $logs_table WHERE $where_clause ORDER BY created DESC LIMIT 200";

if (!empty($where_params)) {
    $logs = $wpdb->get_results($wpdb->prepare($query, $where_params));
} else {
    $logs = $wpdb->get_results($query);
}

// Get log statistics
$total_logs = $wpdb->get_var("SELECT COUNT(*) FROM $logs_table");
$today_logs = $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM $logs_table WHERE DATE(created) = %s",
    current_time('Y-m-d')
));

$event_types = $wpdb->get_results("SELECT event_type, COUNT(*) as count FROM $logs_table GROUP BY event_type ORDER BY count DESC");
$event_actions = $wpdb->get_results("SELECT event_action, COUNT(*) as count FROM $logs_table GROUP BY event_action ORDER BY count DESC");
?>

<div class="securewp-logs">
    <h2><?php echo esc_html__('Security Logs', 'securewp-pro'); ?></h2>
    
    <!-- Statistics -->
    <div class="quick-stats" style="margin-bottom: 20px;">
        <div class="stat-card">
            <div class="stat-number"><?php echo esc_html($total_logs); ?></div>
            <div class="stat-label"><?php echo esc_html__('Total Logs', 'securewp-pro'); ?></div>
        </div>
        
        <div class="stat-card">
            <div class="stat-number"><?php echo esc_html($today_logs); ?></div>
            <div class="stat-label"><?php echo esc_html__('Today\'s Events', 'securewp-pro'); ?></div>
        </div>
        
        <div class="stat-card">
            <div class="stat-number"><?php echo esc_html(count($logs)); ?></div>
            <div class="stat-label"><?php echo esc_html__('Filtered Results', 'securewp-pro'); ?></div>
        </div>
    </div>
    
    <!-- Filters -->
    <div class="tablenav top">
        <div class="alignleft actions">
            <form method="get" style="display: inline-block;">
                <input type="hidden" name="page" value="securewp-pro">
                <input type="hidden" name="tab" value="logs">
                
                <label for="filter_type"><?php echo esc_html__('Event Type:', 'securewp-pro'); ?></label>
                <select id="filter_type" name="type">
                    <option value="all" <?php selected($filter_type, 'all'); ?>><?php echo esc_html__('All Types', 'securewp-pro'); ?></option>
                    <?php foreach ($event_types as $type): ?>
                        <option value="<?php echo esc_attr($type->event_type); ?>" <?php selected($filter_type, $type->event_type); ?>>
                            <?php echo esc_html(ucwords(str_replace('_', ' ', $type->event_type))); ?> (<?php echo esc_html($type->count); ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
                
                <label for="filter_action"><?php echo esc_html__('Action:', 'securewp-pro'); ?></label>
                <select id="filter_action" name="action">
                    <option value="all" <?php selected($filter_action, 'all'); ?>><?php echo esc_html__('All Actions', 'securewp-pro'); ?></option>
                    <?php foreach ($event_actions as $action): ?>
                        <option value="<?php echo esc_attr($action->event_action); ?>" <?php selected($filter_action, $action->event_action); ?>>
                            <?php echo esc_html(ucwords(str_replace('_', ' ', $action->event_action))); ?> (<?php echo esc_html($action->count); ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
                
                <label for="filter_ip"><?php echo esc_html__('IP Address:', 'securewp-pro'); ?></label>
                <input type="text" id="filter_ip" name="ip" value="<?php echo esc_attr($filter_ip); ?>" 
                       placeholder="192.168.1.1" style="width: 150px;">
                
                <label for="filter_date"><?php echo esc_html__('Date:', 'securewp-pro'); ?></label>
                <input type="date" id="filter_date" name="date" value="<?php echo esc_attr($filter_date); ?>">
                
                <input type="submit" class="button" value="<?php echo esc_attr__('Filter', 'securewp-pro'); ?>">
                
                <?php if ($filter_type !== 'all' || $filter_action !== 'all' || !empty($filter_ip) || !empty($filter_date)): ?>
                    <a href="?page=securewp-pro&tab=logs" class="button">
                        <?php echo esc_html__('Clear Filters', 'securewp-pro'); ?>
                    </a>
                <?php endif; ?>
            </form>
        </div>
        
        <div class="alignright actions">
            <button type="button" class="button" onclick="exportLogs()">
                <?php echo esc_html__('Export Logs', 'securewp-pro'); ?>
            </button>
            
            <button type="button" class="button" onclick="clearAllLogs()">
                <?php echo esc_html__('Clear All Logs', 'securewp-pro'); ?>
            </button>
        </div>
    </div>
    
    <!-- Logs Display -->
    <?php if (empty($logs)): ?>
        <div class="notice notice-info">
            <p><?php echo esc_html__('No security logs found.', 'securewp-pro'); ?></p>
        </div>
    <?php else: ?>
        <div class="logs-container">
            <?php foreach ($logs as $log): ?>
                <div class="log-entry <?php echo esc_attr($log->event_action); ?>">
                    <div class="log-header">
                        <div class="log-type">
                            <strong><?php echo esc_html(ucwords(str_replace('_', ' ', $log->event_type))); ?></strong> - 
                            <?php echo esc_html(ucwords(str_replace('_', ' ', $log->event_action))); ?>
                        </div>
                        
                        <div class="log-actions">
                            <span class="ip-address"><?php echo esc_html($log->ip_address); ?></span>
                            
                            <?php if ($log->event_action === 'failed_attempt' || $log->event_action === 'blocked'): ?>
                                <button type="button" class="button button-small lockout-ip-btn" 
                                        data-ip="<?php echo esc_attr($log->ip_address); ?>">
                                    <?php echo esc_html__('Lockout IP', 'securewp-pro'); ?>
                                </button>
                            <?php endif; ?>
                            
                            <a href="?page=securewp-pro&tab=lockouts&ip=<?php echo esc_attr($log->ip_address); ?>" 
                               class="button button-small">
                                <?php echo esc_html__('View IP Details', 'securewp-pro'); ?>
                            </a>
                        </div>
                    </div>
                    
                    <div class="log-meta">
                        <span class="log-time"><?php echo esc_html($log->created); ?></span>
                    </div>
                    
                    <?php if (!empty($log->details)): ?>
                        <div class="log-description"><?php echo esc_html($log->details); ?></div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
        
        <?php if (count($logs) >= 200): ?>
            <div class="notice notice-info">
                <p><?php echo esc_html__('Showing the 200 most recent logs. Use filters to narrow down results.', 'securewp-pro'); ?></p>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<!-- Hidden forms for actions -->
<form id="lockout-ip-form" method="post" style="display: none;">
    <?php wp_nonce_field('securewp_pro_log_action'); ?>
    <input type="hidden" name="action" value="lockout_ip">
    <input type="hidden" name="ip" id="lockout-ip-address">
</form>

<form id="clear-logs-form" method="post" style="display: none;">
    <?php wp_nonce_field('securewp_pro_log_action'); ?>
    <input type="hidden" name="action" value="clear_logs">
</form>

<form id="export-logs-form" method="post" style="display: none;">
    <?php wp_nonce_field('securewp_pro_log_action'); ?>
    <input type="hidden" name="action" value="export_logs">
</form>

<script>
jQuery(document).ready(function($) {
    // Lockout IP button handler
    $('.lockout-ip-btn').click(function() {
        var ip = $(this).data('ip');
        
        if (confirm('<?php echo esc_js(__('Are you sure you want to lockout this IP address?', 'securewp-pro')); ?>')) {
            $('#lockout-ip-address').val(ip);
            $('#lockout-ip-form').submit();
        }
    });
});

function clearAllLogs() {
    if (confirm('<?php echo esc_js(__('Are you sure you want to clear all security logs? This action cannot be undone.', 'securewp-pro')); ?>')) {
        $('#clear-logs-form').submit();
    }
}

function exportLogs() {
    $('#export-logs-form').submit();
}
</script>

