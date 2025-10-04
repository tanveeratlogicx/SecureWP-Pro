<?php
class SecureWP_Pro_Login_Rate_Limiting {
    
    private $logger;
    private $lockout_manager;
    
    public function __construct($logger, $lockout_manager) {
        $this->logger = $logger;
        $this->lockout_manager = $lockout_manager;
        $this->init_hooks();
    }
    
    private function init_hooks() {
        if (get_option('securewp_pro_login_rate_limiting', true)) {
            add_action('wp_login_failed', array($this, 'track_login_failure'));
            add_action('wp_login', array($this, 'track_successful_login'), 10, 2);
            add_filter('authenticate', array($this, 'check_login_attempt'), 30, 3);
            add_action('login_message', array($this, 'login_message'));
        }
    }
    
    public function track_login_failure($username) {
        $ip = $this->get_client_ip();
        
        $this->logger->log_event('login_rate_limiting', 'failed_attempt', 
            'Failed login attempt for username: ' . $username . ', User Agent: ' . 
            (isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : 'Unknown'), $ip);
        
        $this->lockout_manager->check_and_lock($ip, 'login_failure');
    }
    
    /**
     * Log successful login
     */
    public function track_successful_login($username, $user) {
        $ip = $this->get_client_ip();
        
        $this->logger->log_event('login_rate_limiting', 'successful_login', 
            'Successful login for username: ' . $username . ', User Agent: ' . 
            (isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : 'Unknown'), $ip);
        
        // Clear any existing lockouts for this IP
        $this->clear_lockout($ip);
    }
    
    /**
     * Check login attempt
     */
    public function check_login_attempt($user, $username, $password) {
        $ip = $this->get_client_ip();
        
        // Check if IP is permanently blocked
        if ($this->is_permanently_blocked($ip)) {
            $this->logger->log_event('login_rate_limiting', 'permanent_block_attempt', 
                'Login attempt from permanently blocked IP: ' . $ip . ', username: ' . $username, $ip);
            
            return new WP_Error('permanent_block', 
                __('<strong>ERROR</strong>: Your IP address has been permanently blocked.', 'securewp-pro'));
        }
        
        // Check if IP is currently locked out
        if ($this->lockout_manager->is_locked_out($ip, 'login_failure')) {
            $this->logger->log_event('login_rate_limiting', 'blocked', 
                'Login attempt blocked due to lockout for IP: ' . $ip, $ip);
            
            return new WP_Error('temporary_lockout', 
                __('<strong>ERROR</strong>: Too many failed login attempts. Please try again later.', 'securewp-pro'));
        }
        
        return $user;
    }
    
    /**
     * Add login message
     */
    public function login_message($message) {
        if (isset($_GET['lockout'])) {
            $message = '<div id="login_error">' . 
                      __('<strong>ERROR</strong>: Too many failed login attempts. Please try again later.', 'securewp-pro') . 
                      '</div>';
        }
        
        return $message;
    }
    
    /**
     * Check if IP is permanently blocked
     */
    private function is_permanently_blocked($ip) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'securewp_pro_lockouts';
        
        return (bool) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE ip_address = %s AND event_type = 'login_failure' AND permanent = 1",
            $ip
        ));
    }
    
    /**
     * Clear lockout for IP
     */
    private function clear_lockout($ip) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'securewp_pro_lockouts';
        
        $wpdb->delete(
            $table_name,
            array('ip_address' => $ip, 'event_type' => 'login_failure'),
            array('%s', '%s')
        );
    }
    
    private function get_client_ip() {
        // Enhanced IP detection with CloudFlare support
        $ip_keys = array(
            'HTTP_CF_CONNECTING_IP',     // CloudFlare
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_X_CLUSTER_CLIENT_IP',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR'
        );
        
        foreach ($ip_keys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                foreach (explode(',', $_SERVER[$key]) as $ip) {
                    $ip = trim($ip);
                    
                    if (filter_var($ip, FILTER_VALIDATE_IP, 
                        FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                        return $ip;
                    }
                }
            }
        }
        
        return isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '0.0.0.0';
    }
}