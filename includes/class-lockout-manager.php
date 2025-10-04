<?php
/**
 * SecureWP Pro Lockout Manager Class
 * Security hardened version
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class SecureWP_Pro_Lockout_Manager {
    
    private $logger;
    private $notifier;
    private $table_name;
    
    public function __construct($logger, $notifier) {
        $this->logger = $logger;
        $this->notifier = $notifier;
        
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'securewp_pro_lockouts';
        
        $this->init_hooks();
    }
    
    private function init_hooks() {
        // Initialize any required hooks for the lockout manager
        add_action('wp_loaded', array($this, 'cleanup_expired_lockouts'));
    }
    
    public function create_lockout_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'securewp_pro_lockouts';
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            ip_address varchar(45) NOT NULL,
            event_type varchar(50) NOT NULL,
            failure_count smallint NOT NULL DEFAULT 0,
            lockout_expiry int NOT NULL DEFAULT 0,
            permanent tinyint(1) NOT NULL DEFAULT 0,
            created datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
            modified datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
            PRIMARY KEY  (id),
            KEY ip_address (ip_address),
            KEY event_type (event_type)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    public function check_and_lock($ip, $event_type) {
        global $wpdb;
        
        // Input validation
        if (!filter_var($ip, FILTER_VALIDATE_IP)) {
            return false;
        }
        
        $event_type = sanitize_text_field($event_type);
        if (empty($event_type)) {
            return false;
        }
        
        // Get existing lockout record using prepared statement
        $lockout = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM `{$this->table_name}` WHERE ip_address = %s AND event_type = %s",
            $ip, $event_type
        ));
        
        $default_lockout_times = array(
            'initial' => 0, 
            'second' => 30, 
            'third' => 60, 
            'fourth' => 300, 
            'fifth' => 600, 
            'max' => 4800, 
            'permanent' => 86400
        );
        $lockout_times = get_option('securewp_pro_lockout_times', $default_lockout_times);
        // Ensure all required keys exist
        $lockout_times = wp_parse_args($lockout_times, $default_lockout_times);
        $failure_count = 1;
        $lockout_expiry = 0;
        $permanent = 0;
        
        if ($lockout) {
            $failure_count = $lockout->failure_count + 1;
            
            // Update existing record
            if ($lockout->permanent) {
                // Already permanently locked out
                return;
            }
            
            // Calculate lockout time based on failure count
            if ($failure_count == 2) {
                $lockout_expiry = time() + $lockout_times['second'];
            } elseif ($failure_count == 3) {
                $lockout_expiry = time() + $lockout_times['third'];
            } elseif ($failure_count == 4) {
                $lockout_expiry = time() + $lockout_times['fourth'];
            } elseif ($failure_count == 5) {
                $lockout_expiry = time() + $lockout_times['fifth'];
            } elseif ($failure_count >= 6) {
                // If current lockout extends beyond the max threshold window, escalate to permanent (24h)
                if ($lockout->lockout_expiry > (time() + (int) $lockout_times['max'])) {
                    // After 80 minutes (4800 seconds), lock for 24 hours
                    $lockout_expiry = time() + (int) $lockout_times['permanent'];
                    $permanent = 1;
                    
                    // Notify admin about permanent lockout
                    $this->notifier->notify_admin_permanent_lockout($ip, $event_type);
                } else {
                    // Double the previous lockout duration safely
                    // Convert modified (MySQL datetime) to timestamp
                    $modified_ts = strtotime($lockout->modified);
                    if ($modified_ts === false) {
                        $modified_ts = time();
                    }
                    // Previous duration was how long the last lockout was set for
                    $previous_duration = (int) ($lockout->lockout_expiry - $modified_ts);
                    // Ensure sane baseline (fallback to 'fifth' duration if prior calculation is invalid)
                    if ($previous_duration <= 0) {
                        $previous_duration = isset($lockout_times['fifth']) ? (int) $lockout_times['fifth'] : 600;
                    }
                    $new_duration = $previous_duration * 2;
                    $lockout_expiry = time() + $new_duration;
                }
            }
            
            $wpdb->update(
                $this->table_name,
                array(
                    'failure_count' => $failure_count,
                    'lockout_expiry' => $lockout_expiry,
                    'permanent' => $permanent,
                    'modified' => current_time('mysql')
                ),
                array('id' => $lockout->id),
                array('%d', '%d', '%d', '%s'),
                array('%d')
            );
            
        } else {
            // Create new lockout record with prepared statement
            $wpdb->insert(
                $this->table_name,
                array(
                    'ip_address' => $ip,
                    'event_type' => $event_type,
                    'failure_count' => $failure_count,
                    'lockout_expiry' => $lockout_expiry,
                    'created' => current_time('mysql'),
                    'modified' => current_time('mysql')
                ),
                array('%s', '%s', '%d', '%d', '%s', '%s')
            );
        }
        
        // Notify admin if this is a significant event
        if ($failure_count >= 3) {
            $this->notifier->notify_admin_lockout($ip, $event_type, $failure_count);
        }
    }
    
    public function is_locked_out($ip, $event_type) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'securewp_pro_lockouts';
        
        $lockout = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE ip_address = %s AND event_type = %s",
            $ip, $event_type
        ));
        
        if (!$lockout) {
            return false;
        }
        
        // Check if permanently locked out
        if ($lockout->permanent) {
            return true;
        }
        
        // Check if lockout period has expired
        if ($lockout->lockout_expiry > time()) {
            return true;
        }
        
        return false;
    }
    
    public function cleanup_expired_lockouts() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'securewp_pro_lockouts';
        
        // Remove expired non-permanent lockouts
        $wpdb->query($wpdb->prepare(
            "DELETE FROM $table_name WHERE permanent = 0 AND lockout_expiry < %d",
            time()
        ));
    }
    
    /**
     * Unlock an IP address (admin function)
     */
    public function unlock_ip($ip, $event_type) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'securewp_pro_lockouts';
        
        $result = $wpdb->update(
            $table_name,
            array(
                'permanent' => 0,
                'lockout_expiry' => 0,
                'modified' => current_time('mysql')
            ),
            array(
                'ip_address' => $ip,
                'event_type' => $event_type
            )
        );
        
        if ($result !== false) {
            $this->logger->log_event('admin_action', 'ip_unlocked', 
                'Admin unlocked IP: ' . $ip . ' for event: ' . $event_type);
        }
        
        return $result !== false;
    }
}