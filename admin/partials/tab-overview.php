<?php
/**
 * Overview Tab - Dashboard Summary
 */

// Get statistics
global $wpdb;
$lockouts_table = $wpdb->prefix . 'securewp_pro_lockouts';
$logs_table = $wpdb->prefix . 'securewp_pro_logs';

$total_lockouts = $wpdb->get_var("SELECT COUNT(*) FROM $lockouts_table");
$active_lockouts = $wpdb->get_var("SELECT COUNT(*) FROM $lockouts_table WHERE permanent = 1 OR lockout_expiry > " . time());
$total_logs = $wpdb->get_var("SELECT COUNT(*) FROM $logs_table");
$today_logs = $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM $logs_table WHERE DATE(created) = %s",
    current_time('Y-m-d')
));

$recent_events = $wpdb->get_results("
    SELECT * FROM $logs_table 
    ORDER BY created DESC 
    LIMIT 10
");

$top_blocked_ips = $wpdb->get_results("
    SELECT ip_address, COUNT(*) as attempts 
    FROM $logs_table 
    WHERE event_action = 'blocked' 
    GROUP BY ip_address 
    ORDER BY attempts DESC 
    LIMIT 5
");
?>

<div class="securewp-overview">
    <h2><?php echo esc_html__('Security Dashboard', 'securewp-pro'); ?></h2>
    
    <!-- Quick Stats -->
    <div class="quick-stats">
        <div class="stat-card">
            <div class="stat-number"><?php echo esc_html($total_lockouts); ?></div>
            <div class="stat-label"><?php echo esc_html__('Total Lockouts', 'securewp-pro'); ?></div>
        </div>
        
        <div class="stat-card">
            <div class="stat-number"><?php echo esc_html($active_lockouts); ?></div>
            <div class="stat-label"><?php echo esc_html__('Active Lockouts', 'securewp-pro'); ?></div>
        </div>
        
        <div class="stat-card">
            <div class="stat-number"><?php echo esc_html($total_logs); ?></div>
            <div class="stat-label"><?php echo esc_html__('Total Log Entries', 'securewp-pro'); ?></div>
        </div>
        
        <div class="stat-card">
            <div class="stat-number"><?php echo esc_html($today_logs); ?></div>
            <div class="stat-label"><?php echo esc_html__('Today\'s Events', 'securewp-pro'); ?></div>
        </div>
    </div>
    
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px; margin-top: 30px;">
        <!-- Recent Security Events -->
        <div>
            <h3><?php echo esc_html__('Recent Security Events', 'securewp-pro'); ?></h3>
            
            <?php if (empty($recent_events)): ?>
                <p><?php echo esc_html__('No security events recorded yet.', 'securewp-pro'); ?></p>
            <?php else: ?>
                <div class="recent-events">
                    <?php foreach ($recent_events as $event): ?>
                        <div class="log-entry <?php echo esc_attr($event->event_action); ?>">
                            <div class="log-details">
                                <strong><?php echo esc_html(ucwords(str_replace('_', ' ', $event->event_type))); ?></strong> - 
                                <?php echo esc_html($event->event_action); ?>
                            </div>
                            <div class="log-meta">
                                <span class="ip-address"><?php echo esc_html($event->ip_address); ?></span> • 
                                <?php echo esc_html($event->created); ?>
                            </div>
                            <?php if (!empty($event->details)): ?>
                                <div class="log-description"><?php echo esc_html($event->details); ?></div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <p>
                    <a href="?page=securewp-pro&tab=logs" class="button">
                        <?php echo esc_html__('View All Logs', 'securewp-pro'); ?>
                    </a>
                </p>
            <?php endif; ?>
        </div>
        
        <!-- Top Blocked IPs -->
        <div>
            <h3><?php echo esc_html__('Most Blocked IPs', 'securewp-pro'); ?></h3>
            
            <?php if (empty($top_blocked_ips)): ?>
                <p><?php echo esc_html__('No blocked IPs yet.', 'securewp-pro'); ?></p>
            <?php else: ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php echo esc_html__('IP Address', 'securewp-pro'); ?></th>
                            <th><?php echo esc_html__('Block Attempts', 'securewp-pro'); ?></th>
                            <th><?php echo esc_html__('Actions', 'securewp-pro'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($top_blocked_ips as $ip_data): ?>
                            <tr>
                                <td><code><?php echo esc_html($ip_data->ip_address); ?></code></td>
                                <td><?php echo esc_html($ip_data->attempts); ?></td>
                                <td>
                                    <a href="?page=securewp-pro&tab=lockouts&ip=<?php echo esc_attr($ip_data->ip_address); ?>" 
                                       class="button button-small">
                                        <?php echo esc_html__('View Details', 'securewp-pro'); ?>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <p>
                    <a href="?page=securewp-pro&tab=lockouts" class="button">
                        <?php echo esc_html__('Manage All Lockouts', 'securewp-pro'); ?>
                    </a>
                </p>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Quick Actions -->
    <div style="margin-top: 30px; padding: 20px; background: #f8f9fa; border-radius: 4px;">
        <h3><?php echo esc_html__('Quick Actions', 'securewp-pro'); ?></h3>
        <div class="action-buttons">
            <a href="?page=securewp-pro&tab=settings" class="button button-primary">
                <span class="dashicons dashicons-admin-settings"></span> <?php echo esc_html__('Configure Settings', 'securewp-pro'); ?>
            </a>
            
            <a href="?page=securewp-pro&tab=lockouts" class="button">
                <span class="dashicons dashicons-lock"></span> <?php echo esc_html__('Manage Lockouts', 'securewp-pro'); ?>
            </a>
            
            <a href="?page=securewp-pro&tab=logs" class="button">
                <span class="dashicons dashicons-list-view"></span> <?php echo esc_html__('View Security Logs', 'securewp-pro'); ?>
            </a>
            
            <button type="button" class="button" onclick="clearAllLogs()">
                <span class="dashicons dashicons-trash"></span> <?php echo esc_html__('Clear All Logs', 'securewp-pro'); ?>
            </button>
        </div>
    </div>
</div>

<script>
function clearAllLogs() {
    if (confirm('<?php echo esc_js(__('Are you sure you want to clear all security logs? This action cannot be undone.', 'securewp-pro')); ?>')) {
        var form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = '<input type="hidden" name="action" value="clear_logs">' +
                        '<input type="hidden" name="_wpnonce" value="<?php echo wp_create_nonce('securewp_pro_admin_action'); ?>">';
        document.body.appendChild(form);
        form.submit();
    }
}
</script>

