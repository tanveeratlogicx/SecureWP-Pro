<?php
/**
 * SecureWP Pro Logger Class
 * Security hardened version
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class SecureWP_Pro_Logger {
    
    private $table_name;
    
    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'securewp_pro_logs';
    }
    
    public function create_log_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'securewp_pro_logs';
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            event_type varchar(50) NOT NULL,
            event_action varchar(50) NOT NULL,
            details text NOT NULL,
            ip_address varchar(45) NOT NULL,
            created datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
            PRIMARY KEY  (id),
            KEY event_type (event_type),
            KEY ip_address (ip_address)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    public function log_event($event_type, $event_action, $details, $ip_address = '') {
        global $wpdb;
        
        // Input validation
        $event_type = sanitize_text_field($event_type);
        $event_action = sanitize_text_field($event_action);
        $details = sanitize_textarea_field($details);
        
        if (empty($ip_address)) {
            $ip_address = $this->get_client_ip();
        } else {
            // Validate provided IP address
            $ip_address = filter_var($ip_address, FILTER_VALIDATE_IP) ? $ip_address : '0.0.0.0';
        }
        
        // Limit field lengths to prevent overflow attacks
        $event_type = substr($event_type, 0, 50);
        $event_action = substr($event_action, 0, 50);
        $details = substr($details, 0, 1000); // Reasonable limit for details
        
        // Use prepared statement for security
        $result = $wpdb->insert(
            $this->table_name,
            array(
                'event_type' => $event_type,
                'event_action' => $event_action,
                'details' => $details,
                'ip_address' => $ip_address,
                'created' => current_time('mysql')
            ),
            array('%s', '%s', '%s', '%s', '%s')
        );
        
        return $result !== false;
    }
    
    private function get_client_ip() {
        // Get client IP address with proper handling of proxy headers
        $ip_sources = array(
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_X_CLUSTER_CLIENT_IP',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR'
        );
        
        foreach ($ip_sources as $source) {
            if (!empty($_SERVER[$source])) {
                $ip = sanitize_text_field($_SERVER[$source]);
                
                // Handle comma-separated IPs (from proxies)
                if (strpos($ip, ',') !== false) {
                    $ip = trim(explode(',', $ip)[0]);
                }
                
                // Validate and return the first valid IP
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }
        
        return '0.0.0.0';
    }
}