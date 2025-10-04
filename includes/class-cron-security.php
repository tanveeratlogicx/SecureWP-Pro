<?php
class SecureWP_Pro_Cron_Security {
    
    private $logger;
    
    public function __construct($logger) {
        $this->logger = $logger;
        $this->init_hooks();
    }
    
    private function init_hooks() {
        if (get_option('securewp_pro_cron_security', true)) {
            add_filter('cron_request', array($this, 'secure_cron_requests'), 10, 1);
            add_action('init', array($this, 'protect_wp_cron'));
            add_action('template_redirect', array($this, 'monitor_cron_access'));
        }
    }
    
    public function secure_cron_requests($cron_request) {
        // Add authentication to cron requests
        // Use the configured secret key from settings
        $key = get_option('securewp_pro_cron_secret_key', '');
        if (empty($key)) {
            $key = wp_generate_password(32, false);
            update_option('securewp_pro_cron_secret_key', $key);
        }
        
        $cron_request['args']['headers']['X-SecureWP-Cron'] = $key;
        
        return $cron_request;
    }
    
    /**
     * Protect wp-cron.php from direct access
     */
    public function protect_wp_cron() {
        if (defined('DOING_CRON') && DOING_CRON) {
            // Check if request is from server or authorized IP
            if (!$this->is_authorized_cron_request()) {
                $this->block_cron_access();
            }
        }
    }
    
    /**
     * Monitor cron access for suspicious activity
     */
    public function monitor_cron_access() {
        if (isset($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'], '/wp-cron.php') !== false) {
            $this->log_cron_access();
            
            // Rate limiting for cron requests
            if ($this->is_cron_rate_limited()) {
                $this->block_cron_access();
            }
            
            // Check authentication if not from server
            if (!$this->is_authorized_cron_request()) {
                $this->block_cron_access();
            }
        }
    }
    
    /**
     * Check if cron request is authorized
     */
    private function is_authorized_cron_request() {
        // Allow server requests
        if (isset($_SERVER['REMOTE_ADDR']) && isset($_SERVER['SERVER_ADDR']) && 
            $_SERVER['REMOTE_ADDR'] === $_SERVER['SERVER_ADDR']) {
            return true;
        }
        
        // Check authorized IPs (configured in settings, merged with filter)
        $authorized_ips = array();
        $option_ips_raw = get_option('securewp_pro_authorized_cron_ips', '');
        if (!empty($option_ips_raw)) {
            $lines = preg_split('/\r\n|\r|\n/', $option_ips_raw);
            foreach ($lines as $line) {
                $entry = trim($line);
                if ($entry === '') {
                    continue;
                }
                // Accept plain IP or CIDR notation
                if (filter_var($entry, FILTER_VALIDATE_IP)) {
                    $authorized_ips[] = $entry;
                } elseif (preg_match('/^([0-9]{1,3}\.){3}[0-9]{1,3}\/([0-9]|[12][0-9]|3[0-2])$/', $entry)) {
                    $authorized_ips[] = $entry; // CIDR, validated lightly
                }
            }
        }
        // Allow developers to extend via filter
        $authorized_ips = apply_filters('securewp_pro_authorized_cron_ips', $authorized_ips);
        if ($this->ip_in_list($this->get_client_ip(), $authorized_ips)) {
            return true;
        }
        
        // Check if using alternative cron method
        if (defined('ALTERNATE_WP_CRON') && constant('ALTERNATE_WP_CRON')) {
            return $this->verify_alternate_cron_request();
        }
        
        // Check authentication key
        $key = isset($_SERVER['HTTP_X_SECUREWP_CRON']) ? $_SERVER['HTTP_X_SECUREWP_CRON'] : '';
        $stored_key = get_option('securewp_pro_cron_secret_key', '');
        
        return $key === $stored_key;
    }
    
    /**
     * Verify alternate cron request
     */
    private function verify_alternate_cron_request() {
        $secret_key = get_option('securewp_pro_cron_secret_key', '');
        // Accept both 'secret' (as documented in UI) and 'securewp_cron_key' for backward compatibility
        $provided = isset($_GET['secret']) ? $_GET['secret'] : (isset($_GET['securewp_cron_key']) ? $_GET['securewp_cron_key'] : '');
        if (!empty($secret_key) && !empty($provided) && hash_equals($secret_key, $provided)) {
            return true;
        }
        
        return false;
    }

    /**
     * Check if an IP matches any entry from a list of IPs or CIDR ranges
     */
    private function ip_in_list($ip, $list) {
        foreach ((array) $list as $entry) {
            if ($entry === $ip) {
                return true;
            }
            // CIDR match
            if (strpos($entry, '/') !== false) {
                if ($this->cidr_match($ip, $entry)) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Check if IP is in a CIDR
     */
    private function cidr_match($ip, $cidr) {
        list($subnet, $mask) = explode('/', $cidr, 2);
        if (!filter_var($ip, FILTER_VALIDATE_IP) || !filter_var($subnet, FILTER_VALIDATE_IP)) {
            return false;
        }
        $ip_long = ip2long($ip);
        $subnet_long = ip2long($subnet);
        $mask = (int) $mask;
        $mask_long = -1 << (32 - $mask);
        $subnet_net = $subnet_long & $mask_long;
        $ip_net = $ip_long & $mask_long;
        return ($ip_net === $subnet_net);
    }
    
    /**
     * Check if cron is rate limited
     */
    private function is_cron_rate_limited() {
        $ip = $this->get_client_ip();
        $transient_key = 'securewp_pro_cron_count_' . $ip;
        $cron_count = get_transient($transient_key);
        
        if (false === $cron_count) {
            set_transient($transient_key, 1, MINUTE_IN_SECONDS);
            return false;
        }
        
        $cron_count++;
        
        if ($cron_count > 10) { // Max 10 cron requests per minute
            return true;
        }
        
        set_transient($transient_key, $cron_count, MINUTE_IN_SECONDS);
        return false;
    }
    
    /**
     * Log cron access
     */
    private function log_cron_access() {
        $ip = $this->get_client_ip();
        $this->logger->log_event('cron_security', 'access', 
            'Cron access from IP: ' . $ip, $ip);
    }
    
    /**
     * Block cron access
     */
    private function block_cron_access() {
        $ip = $this->get_client_ip();
        
        // Log the blocked attempt
        $this->logger->log_event('cron_security', 'blocked', 
            'Unauthorized cron access attempt from IP: ' . $ip, $ip);
        
        // Notify admin if enabled
        if (get_option('securewp_pro_notify_admin', true)) {
            $this->notify_admin_cron_blocked($ip);
        }
        
        // Send 403 Forbidden
        wp_die('Forbidden', 'Access Denied', array('response' => 403));
    }
    
    /**
     * Notify admin about blocked cron access
     */
    private function notify_admin_cron_blocked($ip) {
        $admin_email = get_option('securewp_pro_notify_email', get_option('admin_email'));
        
        $subject = '[SecureWP Pro] Cron Access Blocked';
        $message = sprintf(
            "Unauthorized wp-cron.php access blocked from IP: %s\n\n" .
            "Time: %s\n" .
            "Site: %s\n\n" .
            "You can configure authorized IPs in the SecureWP Pro settings.",
            $ip,
            current_time('mysql'),
            get_site_url()
        );
        
        wp_mail(
            $admin_email,
            $subject,
            $message,
            array('Content-Type: text/plain; charset=UTF-8')
        );
    }
    
    /**
     * Get client IP address
     */
    private function get_client_ip() {
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