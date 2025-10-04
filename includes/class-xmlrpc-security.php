<?php
class SecureWP_Pro_XMLRPC_Security {
    
    private $logger;
    private $lockout_manager;
    
    public function __construct($logger, $lockout_manager) {
        $this->logger = $logger;
        $this->lockout_manager = $lockout_manager;
        $this->init_hooks();
    }
    
    private function init_hooks() {
        if (get_option('securewp_pro_xmlrpc_security', true)) {
            add_filter('xmlrpc_enabled', array($this, 'disable_xmlrpc'));
            add_filter('xmlrpc_methods', array($this, 'disable_pingbacks'));
            add_action('xmlrpc_call', array($this, 'monitor_xmlrpc_access'));
            add_action('init', array($this, 'block_xmlrpc_if_needed'));
        }
    }
    
    public function disable_xmlrpc($enabled) {
        // Check if complete block is enabled in settings
        if (get_option('securewp_pro_block_xmlrpc_completely', false)) {
            return false;
        }
        
        return $enabled;
    }
    
    public function disable_pingbacks($methods) {
        if (get_option('securewp_pro_xmlrpc_security', true)) {
            unset($methods['pingback.ping']);
            unset($methods['pingback.extensions.getPingbacks']);
        }
        return $methods;
    }
    
    /**
     * Block XML-RPC if configured
     */
    public function block_xmlrpc_if_needed() {
        if ($this->should_block_xmlrpc()) {
            add_filter('xmlrpc_enabled', '__return_false');
            add_action('template_redirect', array($this, 'block_xmlrpc_access'));
        }
    }
    
    /**
     * Check if XML-RPC should be completely blocked
     */
    private function should_block_xmlrpc() {
        // Check if complete block is enabled in settings
        if (get_option('securewp_pro_block_xmlrpc_completely', false)) {
            return true;
        }
        
        // Check if request is suspicious
        if ($this->is_suspicious_xmlrpc_request()) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Monitor XML-RPC access
     */
    public function monitor_xmlrpc_access($method) {
        $ip = $this->get_client_ip();
        
        $this->logger->log_event('xmlrpc_security', 'request', 
            'XML-RPC request: ' . $method . ' from IP: ' . $ip, $ip);
        
        // Rate limiting
        if ($this->is_xmlrpc_rate_limited()) {
            $this->block_xmlrpc_request();
        }
        
        // Check for suspicious methods
        if ($this->is_suspicious_method($method)) {
            $this->handle_suspicious_method($method);
        }
        
        // Handle pingback attempts
        if (in_array($method, array('pingback.ping', 'pingback.extensions.getPingbacks'))) {
            $this->logger->log_event('xmlrpc_security', 'pingback_attempt', 'Pingback attempt detected', $ip);
            $this->lockout_manager->check_and_lock($ip, 'xmlrpc_pingback');
            $this->block_xmlrpc_request();
        }
    }
    
    /**
     * Check if XML-RPC request is suspicious
     */
    private function is_suspicious_xmlrpc_request() {
        $ip = $this->get_client_ip();
        $transient_key = 'securewp_pro_xmlrpc_count_' . $ip;
        $request_count = get_transient($transient_key);
        
        if (false === $request_count) {
            set_transient($transient_key, 1, MINUTE_IN_SECONDS * 5);
            return false;
        }
        
        $request_count++;
        set_transient($transient_key, $request_count, MINUTE_IN_SECONDS * 5);
        
        // More than 20 requests in 5 minutes is suspicious
        if ($request_count > 20) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Check if XML-RPC is rate limited
     */
    private function is_xmlrpc_rate_limited() {
        $ip = $this->get_client_ip();
        $transient_key = 'securewp_pro_xmlrpc_rate_' . $ip;
        $request_count = get_transient($transient_key);
        
        if (false === $request_count) {
            set_transient($transient_key, 1, MINUTE_IN_SECONDS);
            return false;
        }
        
        $request_count++;
        
        // Max 10 XML-RPC requests per minute
        if ($request_count > 10) {
            return true;
        }
        
        set_transient($transient_key, $request_count, MINUTE_IN_SECONDS);
        return false;
    }
    
    /**
     * Check if method is suspicious
     */
    private function is_suspicious_method($method) {
        $suspicious_methods = array(
            'system.multicall',
            'system.listMethods',
            'pingback.ping',
            'pingback.extensions.getPingbacks'
        );
        
        return in_array($method, $suspicious_methods);
    }
    
    /**
     * Handle suspicious method
     */
    private function handle_suspicious_method($method) {
        $ip = $this->get_client_ip();
        
        $this->logger->log_event('xmlrpc_security', 'suspicious_method', 
            'Suspicious XML-RPC method: ' . $method, $ip);
        
        if (get_option('securewp_pro_block_suspicious_methods', true)) {
            $this->block_xmlrpc_request();
        }
    }
    
    /**
     * Block XML-RPC access
     */
    public function block_xmlrpc_access() {
        if (isset($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'], 'xmlrpc.php') !== false) {
            $ip = $this->get_client_ip();
            
            $this->logger->log_event('xmlrpc_security', 'blocked', 
                'XML-RPC access blocked from IP: ' . $ip, $ip);
            
            // Notify admin
            if (get_option('securewp_pro_notify_admin', true)) {
                $this->notify_admin_xmlrpc_blocked($ip);
            }
            
            // Send 403 Forbidden
            status_header(403);
            exit('Forbidden');
        }
    }
    
    /**
     * Block XML-RPC request
     */
    private function block_xmlrpc_request() {
        $ip = $this->get_client_ip();
        
        $this->logger->log_event('xmlrpc_security', 'rate_limited', 
            'XML-RPC request rate limited from IP: ' . $ip, $ip);
        
        // Send 429 Too Many Requests
        status_header(429);
        exit('Too Many Requests');
    }
    
    /**
     * Notify admin about blocked XML-RPC access
     */
    private function notify_admin_xmlrpc_blocked($ip) {
        $admin_email = get_option('securewp_pro_notify_email', get_option('admin_email'));
        
        $subject = '[SecureWP Pro] XML-RPC Access Blocked';
        $message = sprintf(
            "XML-RPC access blocked from IP: %s\n\n" .
            "Time: %s\n" .
            "Site: %s",
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
    
    private function get_client_ip() {
        // Implementation to get client IP address
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        return $ip;
    }
}