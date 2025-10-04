<?php
/**
 * Statistics Tab - Security Analytics
 */

global $wpdb;
$lockouts_table = $wpdb->prefix . 'securewp_pro_lockouts';
$logs_table = $wpdb->prefix . 'securewp_pro_logs';

// Get statistics for the last 30 days
$thirty_days_ago = date('Y-m-d', strtotime('-30 days'));

// Overall statistics
$total_lockouts = $wpdb->get_var("SELECT COUNT(*) FROM $lockouts_table");
$active_lockouts = $wpdb->get_var("SELECT COUNT(*) FROM $lockouts_table WHERE permanent = 1 OR lockout_expiry > " . time());
$total_logs = $wpdb->get_var("SELECT COUNT(*) FROM $logs_table");
$blocked_attempts = $wpdb->get_var("SELECT COUNT(*) FROM $logs_table WHERE event_action = 'blocked'");

// Daily statistics for the last 30 days
$daily_stats = $wpdb->get_results($wpdb->prepare("
    SELECT DATE(created) as date, COUNT(*) as count 
    FROM $logs_table 
    WHERE created >= %s 
    GROUP BY DATE(created) 
    ORDER BY date DESC
", $thirty_days_ago));

// Event type breakdown
$event_type_stats = $wpdb->get_results("
    SELECT event_type, COUNT(*) as count 
    FROM $logs_table 
    GROUP BY event_type 
    ORDER BY count DESC
");

// Event action breakdown
$event_action_stats = $wpdb->get_results("
    SELECT event_action, COUNT(*) as count 
    FROM $logs_table 
    GROUP BY event_action 
    ORDER BY count DESC
");

// Top blocked IPs
$top_blocked_ips = $wpdb->get_results("
    SELECT ip_address, COUNT(*) as attempts 
    FROM $logs_table 
    WHERE event_action = 'blocked' 
    GROUP BY ip_address 
    ORDER BY attempts DESC 
    LIMIT 10
");

// Lockout type breakdown
$lockout_type_stats = $wpdb->get_results("
    SELECT event_type, COUNT(*) as count 
    FROM $lockouts_table 
    GROUP BY event_type 
    ORDER BY count DESC
");

// Recent activity (last 24 hours)
$recent_activity = $wpdb->get_results($wpdb->prepare("
    SELECT event_type, event_action, COUNT(*) as count 
    FROM $logs_table 
    WHERE created >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
    GROUP BY event_type, event_action 
    ORDER BY count DESC
"));
?>

<div class="securewp-statistics">
    <h2><?php echo esc_html__('Security Statistics', 'securewp-pro'); ?></h2>
    
    <!-- Overall Stats -->
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
            <div class="stat-number"><?php echo esc_html($blocked_attempts); ?></div>
            <div class="stat-label"><?php echo esc_html__('Blocked Attempts', 'securewp-pro'); ?></div>
        </div>
    </div>
    
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px; margin-top: 30px;">
        <!-- Event Types Chart -->
        <div class="chart-container">
            <h3><?php echo esc_html__('Security Events by Type', 'securewp-pro'); ?></h3>
            
            <?php if (!empty($event_type_stats)): ?>
                <div class="chart-data">
                    <?php foreach ($event_type_stats as $stat): ?>
                        <div class="chart-row">
                            <div class="chart-label"><?php echo esc_html(ucwords(str_replace('_', ' ', $stat->event_type))); ?></div>
                            <div class="chart-bar">
                                <div class="chart-fill" style="width: <?php echo esc_attr(($stat->count / $event_type_stats[0]->count) * 100); ?>%"></div>
                            </div>
                            <div class="chart-value"><?php echo esc_html($stat->count); ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p><?php echo esc_html__('No event data available.', 'securewp-pro'); ?></p>
            <?php endif; ?>
        </div>
        
        <!-- Event Actions Chart -->
        <div class="chart-container">
            <h3><?php echo esc_html__('Security Events by Action', 'securewp-pro'); ?></h3>
            
            <?php if (!empty($event_action_stats)): ?>
                <div class="chart-data">
                    <?php foreach ($event_action_stats as $stat): ?>
                        <div class="chart-row">
                            <div class="chart-label"><?php echo esc_html(ucwords(str_replace('_', ' ', $stat->event_action))); ?></div>
                            <div class="chart-bar">
                                <div class="chart-fill" style="width: <?php echo esc_attr(($stat->count / $event_action_stats[0]->count) * 100); ?>%"></div>
                            </div>
                            <div class="chart-value"><?php echo esc_html($stat->count); ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p><?php echo esc_html__('No action data available.', 'securewp-pro'); ?></p>
            <?php endif; ?>
        </div>
    </div>
    
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px; margin-top: 30px;">
        <!-- Top Blocked IPs -->
        <div>
            <h3><?php echo esc_html__('Most Blocked IPs', 'securewp-pro'); ?></h3>
            
            <?php if (!empty($top_blocked_ips)): ?>
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
            <?php else: ?>
                <p><?php echo esc_html__('No blocked IPs yet.', 'securewp-pro'); ?></p>
            <?php endif; ?>
        </div>
        
        <!-- Lockout Types -->
        <div>
            <h3><?php echo esc_html__('Lockouts by Type', 'securewp-pro'); ?></h3>
            
            <?php if (!empty($lockout_type_stats)): ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php echo esc_html__('Event Type', 'securewp-pro'); ?></th>
                            <th><?php echo esc_html__('Count', 'securewp-pro'); ?></th>
                            <th><?php echo esc_html__('Actions', 'securewp-pro'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($lockout_type_stats as $stat): ?>
                            <tr>
                                <td><?php echo esc_html(ucwords(str_replace('_', ' ', $stat->event_type))); ?></td>
                                <td><?php echo esc_html($stat->count); ?></td>
                                <td>
                                    <a href="?page=securewp-pro&tab=lockouts&status=all&type=<?php echo esc_attr($stat->event_type); ?>" 
                                       class="button button-small">
                                        <?php echo esc_html__('View Lockouts', 'securewp-pro'); ?>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p><?php echo esc_html__('No lockout data available.', 'securewp-pro'); ?></p>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Recent Activity -->
    <div style="margin-top: 30px;">
        <h3><?php echo esc_html__('Recent Activity (Last 24 Hours)', 'securewp-pro'); ?></h3>
        
        <?php if (!empty($recent_activity)): ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php echo esc_html__('Event Type', 'securewp-pro'); ?></th>
                        <th><?php echo esc_html__('Action', 'securewp-pro'); ?></th>
                        <th><?php echo esc_html__('Count', 'securewp-pro'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recent_activity as $activity): ?>
                        <tr>
                            <td><?php echo esc_html(ucwords(str_replace('_', ' ', $activity->event_type))); ?></td>
                            <td><?php echo esc_html(ucwords(str_replace('_', ' ', $activity->event_action))); ?></td>
                            <td><?php echo esc_html($activity->count); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p><?php echo esc_html__('No recent activity.', 'securewp-pro'); ?></p>
        <?php endif; ?>
    </div>
    
    <!-- Daily Activity Chart -->
    <?php if (!empty($daily_stats)): ?>
        <div style="margin-top: 30px;">
            <h3><?php echo esc_html__('Daily Activity (Last 30 Days)', 'securewp-pro'); ?></h3>
            
            <div class="daily-chart">
                <?php 
                $max_count = max(array_column($daily_stats, 'count'));
                foreach (array_reverse($daily_stats) as $day): 
                ?>
                    <div class="daily-bar" style="height: <?php echo esc_attr(($day->count / $max_count) * 100); ?>%">
                        <span class="daily-count"><?php echo esc_html($day->count); ?></span>
                        <span class="daily-date"><?php echo esc_html(date('M j', strtotime($day->date))); ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<style>
.chart-container {
    background: #f8f9fa;
    border: 1px solid #e1e5e9;
    border-radius: 4px;
    padding: 20px;
}

.chart-row {
    display: flex;
    align-items: center;
    margin-bottom: 10px;
}

.chart-label {
    width: 150px;
    font-size: 0.9em;
}

.chart-bar {
    flex: 1;
    height: 20px;
    background: #e1e5e9;
    border-radius: 10px;
    margin: 0 10px;
    overflow: hidden;
}

.chart-fill {
    height: 100%;
    background: linear-gradient(90deg, #0073aa, #005177);
    transition: width 0.3s ease;
}

.chart-value {
    width: 50px;
    text-align: right;
    font-weight: bold;
    color: #0073aa;
}

.daily-chart {
    display: flex;
    align-items: end;
    height: 200px;
    border-bottom: 2px solid #e1e5e9;
    border-left: 2px solid #e1e5e9;
    padding: 10px;
    gap: 5px;
}

.daily-bar {
    flex: 1;
    background: linear-gradient(180deg, #0073aa, #005177);
    border-radius: 2px 2px 0 0;
    position: relative;
    min-height: 20px;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    align-items: center;
    padding: 5px;
}

.daily-count {
    color: #fff;
    font-weight: bold;
    font-size: 0.8em;
}

.daily-date {
    color: #fff;
    font-size: 0.7em;
    transform: rotate(-90deg);
    white-space: nowrap;
}

@media (max-width: 768px) {
    .daily-chart {
        height: 150px;
    }
    
    .daily-date {
        font-size: 0.6em;
    }
}
</style>

