<?php
class SecureWP_Pro_Password_Reset_Rate_Limiting {
    
    private $logger;
    private $lockout_manager;
    
    public function __construct($logger, $lockout_manager) {
        $this->logger = $logger;
        $this->lockout_manager = $lockout_manager;
        $this->init_hooks();
    }
    
    private function init_hooks() {
        add_action('retrieve_password', array($this, 'track_password_reset_request'));
        add_filter('allow_password_reset', array($this, 'check_password_reset_lockout'), 10, 2);
    }
    
    public function track_password_reset_request($username) {
        $ip = $this->get_client_ip();
        
        $this->logger->log_event('password_reset_rate_limiting', 'request', 
            'Password reset request for username: ' . $username, $ip);
        
        $this->lockout_manager->check_and_lock($ip, 'password_reset');
    }
    
    public function check_password_reset_lockout($allow, $user_id) {
        $ip = $this->get_client_ip();
        
        if ($this->lockout_manager->is_locked_out($ip, 'password_reset')) {
            $this->logger->log_event('password_reset_rate_limiting', 'blocked', 
                'Password reset attempt blocked due to lockout for IP: ' . $ip, $ip);
            
            return false;
        }
        
        return $allow;
    }
    
    private function get_client_ip() {
        // Get client IP address with proper handling of proxy headers
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        
        // Handle comma-separated IPs (from proxies)
        if (strpos($ip, ',') !== false) {
            $ip = trim(explode(',', $ip)[0]);
        }
        
        return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : '0.0.0.0';
    }
}