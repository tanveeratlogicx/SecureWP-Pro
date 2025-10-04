<?php
/**
 * Lockouts Tab - Enhanced with Log Integration
 */

// Handle lockout actions
if (isset($_POST['action']) && wp_verify_nonce($_POST['_wpnonce'], 'securewp_pro_lockout_action')) {
    $action = sanitize_text_field($_POST['action']);
    
    switch ($action) {
        case 'unlock':
            $lockout_id = intval($_POST['lockout_id']);
            $this->unlock_lockout_by_id($lockout_id);
            echo '<div class="notice notice-success"><p>' . __('Lockout removed successfully.', 'securewp-pro') . '</p></div>';
            break;
            
        case 'delete':
            $lockout_id = intval($_POST['lockout_id']);
            $this->delete_lockout_by_id($lockout_id);
            echo '<div class="notice notice-success"><p>' . __('Lockout record deleted successfully.', 'securewp-pro') . '</p></div>';
            break;
            
        case 'unlock_ip':
            $ip = sanitize_text_field($_POST['ip']);
            $event_type = sanitize_text_field($_POST['event_type']);
            $this->unlock_ip_address($ip, $event_type);
            echo '<div class="notice notice-success"><p>' . __('IP address unlocked successfully.', 'securewp-pro') . '</p></div>';
            break;
    }
}

// Get filter parameters
$filter_ip = isset($_GET['ip']) ? sanitize_text_field($_GET['ip']) : '';
$filter_status = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : 'all';

// Get current lockouts with filters
global $wpdb;
$table_name = $wpdb->prefix . 'securewp_pro_lockouts';

$where_conditions = array('1=1');
$where_params = array();

if (!empty($filter_ip)) {
    $where_conditions[] = 'ip_address = %s';
    $where_params[] = $filter_ip;
}

if ($filter_status === 'active') {
    $where_conditions[] = '(permanent = 1 OR lockout_expiry > %d)';
    $where_params[] = time();
} elseif ($filter_status === 'expired') {
    $where_conditions[] = '(permanent = 0 AND lockout_expiry < %d)';
    $where_params[] = time();
} elseif ($filter_status === 'permanent') {
    $where_conditions[] = 'permanent = 1';
}

$where_clause = implode(' AND ', $where_conditions);
$query = "SELECT * FROM $table_name WHERE $where_clause ORDER BY created DESC LIMIT 100";

if (!empty($where_params)) {
    $lockouts = $wpdb->get_results($wpdb->prepare($query, $where_params));
} else {
    $lockouts = $wpdb->get_results($query);
}

// Get logs for specific IP if filtered
$logs_table = $wpdb->prefix . 'securewp_pro_logs';
$ip_logs = array();
if (!empty($filter_ip)) {
    $ip_logs = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM $logs_table WHERE ip_address = %s ORDER BY created DESC LIMIT 20",
        $filter_ip
    ));
}
?>

<div class="securewp-lockouts">
    <h2><?php echo esc_html__('Lockout Management', 'securewp-pro'); ?></h2>
    
    <!-- Filters -->
    <div class="tablenav top">
        <div class="alignleft actions">
            <form method="get" style="display: inline-block;">
                <input type="hidden" name="page" value="securewp-pro">
                <input type="hidden" name="tab" value="lockouts">
                
                <label for="filter_ip"><?php echo esc_html__('Filter by IP:', 'securewp-pro'); ?></label>
                <input type="text" id="filter_ip" name="ip" value="<?php echo esc_attr($filter_ip); ?>" 
                       placeholder="192.168.1.1" style="width: 150px;">
                
                <label for="filter_status"><?php echo esc_html__('Status:', 'securewp-pro'); ?></label>
                <select id="filter_status" name="status">
                    <option value="all" <?php selected($filter_status, 'all'); ?>><?php echo esc_html__('All', 'securewp-pro'); ?></option>
                    <option value="active" <?php selected($filter_status, 'active'); ?>><?php echo esc_html__('Active', 'securewp-pro'); ?></option>
                    <option value="expired" <?php selected($filter_status, 'expired'); ?>><?php echo esc_html__('Expired', 'securewp-pro'); ?></option>
                    <option value="permanent" <?php selected($filter_status, 'permanent'); ?>><?php echo esc_html__('Permanent', 'securewp-pro'); ?></option>
                </select>
                
                <input type="submit" class="button" value="<?php echo esc_attr__('Filter', 'securewp-pro'); ?>">
                
                <?php if (!empty($filter_ip) || $filter_status !== 'all'): ?>
                    <a href="?page=securewp-pro&tab=lockouts" class="button">
                        <?php echo esc_html__('Clear Filters', 'securewp-pro'); ?>
                    </a>
                <?php endif; ?>
            </form>
        </div>
        
        <div class="alignright actions">
            <button type="button" class="button" onclick="bulkUnlockExpired()">
                <?php echo esc_html__('Unlock All Expired', 'securewp-pro'); ?>
            </button>
        </div>
    </div>
    
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px; margin-top: 20px;">
        <!-- Lockouts List -->
        <div>
            <h3><?php echo esc_html__('Current Lockouts', 'securewp-pro'); ?></h3>
            
            <?php if (empty($lockouts)): ?>
                <p><?php echo esc_html__('No lockouts found.', 'securewp-pro'); ?></p>
            <?php else: ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php echo esc_html__('IP Address', 'securewp-pro'); ?></th>
                            <th><?php echo esc_html__('Event Type', 'securewp-pro'); ?></th>
                            <th><?php echo esc_html__('Status', 'securewp-pro'); ?></th>
                            <th><?php echo esc_html__('Created', 'securewp-pro'); ?></th>
                            <th><?php echo esc_html__('Actions', 'securewp-pro'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($lockouts as $lockout): ?>
                            <tr>
                                <td>
                                    <code><?php echo esc_html($lockout->ip_address); ?></code>
                                    <br>
                                    <a href="?page=securewp-pro&tab=lockouts&ip=<?php echo esc_attr($lockout->ip_address); ?>" 
                                       class="button button-small">
                                        <?php echo esc_html__('View Logs', 'securewp-pro'); ?>
                                    </a>
                                </td>
                                <td><?php echo esc_html(ucwords(str_replace('_', ' ', $lockout->event_type))); ?></td>
                                <td>
                                    <?php if ($lockout->permanent): ?>
                                        <span class="lockout-status permanent"><?php echo esc_html__('Permanent', 'securewp-pro'); ?></span>
                                    <?php elseif ($lockout->lockout_expiry > time()): ?>
                                        <span class="lockout-status active"><?php echo esc_html__('Active', 'securewp-pro'); ?></span>
                                        <br><small><?php echo esc_html(sprintf(__('Expires: %s', 'securewp-pro'), date('Y-m-d H:i:s', $lockout->lockout_expiry))); ?></small>
                                    <?php else: ?>
                                        <span class="lockout-status expired"><?php echo esc_html__('Expired', 'securewp-pro'); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo esc_html($lockout->created); ?></td>
                                <td>
                                    <?php if ($lockout->permanent || $lockout->lockout_expiry > time()): ?>
                                        <button type="button" class="button button-small unlock-btn" 
                                                data-ip="<?php echo esc_attr($lockout->ip_address); ?>" 
                                                data-event="<?php echo esc_attr($lockout->event_type); ?>">
                                            <?php echo esc_html__('Unlock', 'securewp-pro'); ?>
                                        </button>
                                    <?php endif; ?>
                                    
                                    <button type="button" class="button button-small delete-btn" 
                                            data-id="<?php echo esc_attr($lockout->id); ?>">
                                        <?php echo esc_html__('Delete', 'securewp-pro'); ?>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        
        <!-- IP Logs (if filtered) -->
        <div>
            <?php if (!empty($filter_ip)): ?>
                <h3><?php printf(__('Recent Logs for %s', 'securewp-pro'), esc_html($filter_ip)); ?></h3>
                
                <?php if (empty($ip_logs)): ?>
                    <p><?php echo esc_html__('No logs found for this IP.', 'securewp-pro'); ?></p>
                <?php else: ?>
                    <div class="ip-logs">
                        <?php foreach ($ip_logs as $log): ?>
                            <div class="log-entry <?php echo esc_attr($log->event_action); ?>">
                                <div class="log-details">
                                    <strong><?php echo esc_html(ucwords(str_replace('_', ' ', $log->event_type))); ?></strong> - 
                                    <?php echo esc_html($log->event_action); ?>
                                </div>
                                <div class="log-meta">
                                    <?php echo esc_html($log->created); ?>
                                </div>
                                <?php if (!empty($log->details)): ?>
                                    <div class="log-description"><?php echo esc_html($log->details); ?></div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <h3><?php echo esc_html__('Quick Actions', 'securewp-pro'); ?></h3>
                <div class="quick-actions">
                    <p><?php echo esc_html__('Select an IP address from the lockouts list to view detailed logs and take actions.', 'securewp-pro'); ?></p>
                    
                    <h4><?php echo esc_html__('Bulk Actions', 'securewp-pro'); ?></h4>
                    <div class="action-buttons">
                        <button type="button" class="button" onclick="unlockAllExpired()">
                            <?php echo esc_html__('Unlock All Expired Lockouts', 'securewp-pro'); ?>
                        </button>
                        
                        <button type="button" class="button" onclick="deleteAllExpired()">
                            <?php echo esc_html__('Delete All Expired Records', 'securewp-pro'); ?>
                        </button>
                    </div>
                    
                    <h4><?php echo esc_html__('Export Data', 'securewp-pro'); ?></h4>
                    <div class="action-buttons">
                        <button type="button" class="button" onclick="exportLockouts()">
                            <?php echo esc_html__('Export Lockouts', 'securewp-pro'); ?>
                        </button>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Hidden forms for actions -->
<form id="unlock-form" method="post" style="display: none;">
    <?php wp_nonce_field('securewp_pro_lockout_action'); ?>
    <input type="hidden" name="action" value="unlock_ip">
    <input type="hidden" name="ip" id="unlock-ip">
    <input type="hidden" name="event_type" id="unlock-event">
</form>

<form id="delete-form" method="post" style="display: none;">
    <?php wp_nonce_field('securewp_pro_lockout_action'); ?>
    <input type="hidden" name="action" value="delete">
    <input type="hidden" name="lockout_id" id="delete-id">
</form>

<script>
jQuery(document).ready(function($) {
    // Unlock button handler
    $('.unlock-btn').click(function() {
        var ip = $(this).data('ip');
        var event = $(this).data('event');
        
        if (confirm('<?php echo esc_js(__('Are you sure you want to unlock this IP address?', 'securewp-pro')); ?>')) {
            $('#unlock-ip').val(ip);
            $('#unlock-event').val(event);
            $('#unlock-form').submit();
        }
    });
    
    // Delete button handler
    $('.delete-btn').click(function() {
        var id = $(this).data('id');
        
        if (confirm('<?php echo esc_js(__('Are you sure you want to delete this lockout record?', 'securewp-pro')); ?>')) {
            $('#delete-id').val(id);
            $('#delete-form').submit();
        }
    });
});

function unlockAllExpired() {
    if (confirm('<?php echo esc_js(__('Are you sure you want to unlock all expired lockouts?', 'securewp-pro')); ?>')) {
        // Implement bulk unlock functionality
        alert('<?php echo esc_js(__('Bulk unlock functionality will be implemented.', 'securewp-pro')); ?>');
    }
}

function deleteAllExpired() {
    if (confirm('<?php echo esc_js(__('Are you sure you want to delete all expired lockout records?', 'securewp-pro')); ?>')) {
        // Implement bulk delete functionality
        alert('<?php echo esc_js(__('Bulk delete functionality will be implemented.', 'securewp-pro')); ?>');
    }
}

function exportLockouts() {
    // Implement export functionality
    alert('<?php echo esc_js(__('Export functionality will be implemented.', 'securewp-pro')); ?>');
}
</script>

