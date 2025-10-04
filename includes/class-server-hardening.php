<?php
/**
 * Server Hardening Security
 * 
 * Protects against information disclosure and file access vulnerabilities
 * 
 * Security Features:
 * - Server banner hiding/modification
 * - Sensitive file access protection (readme.html, license.txt, etc.)
 * - Version information hiding
 * - Directory browsing protection
 * - Security headers enforcement
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class SecureWP_Pro_Server_Hardening {
    
    private $logger;
    
    public function __construct($logger) {
        $this->logger = $logger;
        $this->init();
    }
    
    /**
     * Initialize server hardening features
     */
    private function init() {
        // Only initialize if the feature is enabled
        if (get_option('securewp_pro_server_hardening', true)) {
            add_action('init', array($this, 'setup_server_hardening'));
            add_action('wp_headers', array($this, 'modify_server_headers'));
            add_action('template_redirect', array($this, 'block_sensitive_files'));
            add_filter('wp_headers', array($this, 'add_security_headers'));
            
            // Remove WordPress version from various places
            add_filter('the_generator', array($this, 'remove_version_info'));
            remove_action('wp_head', 'wp_generator');
            
            // Hide plugin/theme versions
            add_filter('style_loader_src', array($this, 'remove_version_query_string'), 15);
            add_filter('script_loader_src', array($this, 'remove_version_query_string'), 15);
            
            // Protect against directory browsing
            add_action('init', array($this, 'prevent_directory_browsing'));
        }
    }
    
    /**
     * Setup server hardening measures
     */
    public function setup_server_hardening() {
        // Remove X-Powered-By header if possible
        if (function_exists('header_remove')) {
            header_remove('X-Powered-By');
        }
        
        // Remove Server header modification via PHP
        if (!headers_sent()) {
            header('Server: SecureWP', true);
        }
    }
    
    /**
     * Modify server headers to hide/change server information
     */
    public function modify_server_headers($headers) {
        // Remove or modify server identification headers
        if (isset($headers['Server'])) {
            $headers['Server'] = 'SecureWP';
        }
        
        // Remove X-Powered-By if present
        if (isset($headers['X-Powered-By'])) {
            unset($headers['X-Powered-By']);
        }
        
        // Remove any PHP version disclosure
        if (isset($headers['X-PHP-Version'])) {
            unset($headers['X-PHP-Version']);
        }
        
        return $headers;
    }
    
    /**
     * Add security headers to enhance protection
     */
    public function add_security_headers($headers) {
        // Add security headers if enabled
        if (get_option('securewp_pro_security_headers', true)) {
            $headers['X-Content-Type-Options'] = 'nosniff';
            $headers['X-Frame-Options'] = 'SAMEORIGIN';
            $headers['X-XSS-Protection'] = '1; mode=block';
            $headers['Referrer-Policy'] = 'strict-origin-when-cross-origin';
            
            // Add HSTS if on HTTPS
            if (is_ssl()) {
                $headers['Strict-Transport-Security'] = 'max-age=31536000; includeSubDomains';
            }
        }
        
        return $headers;
    }
    
    /**
     * Block access to sensitive files
     */
    public function block_sensitive_files() {
        $blocked_files = array(
            'readme.html',
            'readme.txt', 
            'license.txt',
            'changelog.txt',
            'wp-config.php',
            '.htaccess',
            'error_log',
            'debug.log',
            'wp-config-sample.php',
            'install.php',
            'upgrade.php'
        );
        
        // Get the current request URI
        $request_uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $file_name = basename($request_uri);
        
        // Check if the requested file is in our blocked list
        if (in_array(strtolower($file_name), array_map('strtolower', $blocked_files))) {
            $this->block_file_access($file_name);
        }
        
        // Block access to plugin/theme readme files
        if (preg_match('/\/(plugins|themes)\/.*\/(readme|license|changelog)\.(html|txt|md)$/i', $request_uri)) {
            $this->block_file_access($file_name);
        }
        
        // Block access to backup files
        if (preg_match('/\.(bak|backup|old|orig|save|tmp)$/i', $file_name)) {
            $this->block_file_access($file_name);
        }
        
        // Block access to log files
        if (preg_match('/\.(log|err)$/i', $file_name)) {
            $this->block_file_access($file_name);
        }
        
        // Block access to configuration files
        if (preg_match('/\.(conf|config|ini|env)$/i', $file_name)) {
            $this->block_file_access($file_name);
        }
    }
    
    /**
     * Block file access and log the attempt
     */
    private function block_file_access($file_name) {
        $ip_address = $this->get_client_ip();
        $user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? sanitize_text_field($_SERVER['HTTP_USER_AGENT']) : 'Unknown';
        
        // Log the blocked access attempt
        $this->logger->log_event(
            'server_hardening',
            'blocked_file_access',
            sprintf(
                'Blocked access to sensitive file: %s from IP: %s, User Agent: %s',
                sanitize_text_field($file_name),
                $ip_address,
                $user_agent
            ),
            $ip_address
        );
        
        // Send 404 instead of 403 to avoid information disclosure
        status_header(404);
        nocache_headers();
        include(get_404_template());
        exit;
    }
    
    /**
     * Remove WordPress version information
     */
    public function remove_version_info() {
        return '';
    }
    
    /**
     * Remove version query strings from CSS/JS files
     */
    public function remove_version_query_string($src) {
        if (strpos($src, 'ver=') !== false) {
            $src = remove_query_arg('ver', $src);
        }
        return $src;
    }
    
    /**
     * Prevent directory browsing by adding index.php files
     */
    public function prevent_directory_browsing() {
        $directories = array(
            ABSPATH . 'wp-content/uploads/',
            ABSPATH . 'wp-content/plugins/',
            ABSPATH . 'wp-content/themes/',
            WP_CONTENT_DIR . '/cache/',
        );
        
        foreach ($directories as $dir) {
            if (is_dir($dir) && is_writable($dir)) {
                $index_file = $dir . 'index.php';
                if (!file_exists($index_file)) {
                    $content = "<?php\n// Silence is golden\n";
                    file_put_contents($index_file, $content);
                }
            }
        }
    }
    
    /**
     * Get client IP address with proper handling of proxies
     */
    private function get_client_ip() {
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
                if (strpos($ip, ',') !== false) {
                    $ip = trim(explode(',', $ip)[0]);
                }
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }
        
        return '0.0.0.0';
    }
    
    /**
     * Generate .htaccess rules for additional server-level protection
     */
    public function generate_htaccess_rules() {
        $rules = array();
        
        // Hide server signature
        $rules[] = '# Hide server signature';
        $rules[] = 'ServerTokens Prod';
        $rules[] = 'ServerSignature Off';
        $rules[] = '';
        
        // Block sensitive files
        $rules[] = '# Block access to sensitive files';
        $rules[] = '<FilesMatch "^(readme|license|changelog)\.(html|txt|md)$">';
        $rules[] = '    Require all denied';
        $rules[] = '</FilesMatch>';
        $rules[] = '';
        
        $rules[] = '<FilesMatch "\.(bak|backup|old|orig|save|tmp|log|err|conf|config|ini|env)$">';
        $rules[] = '    Require all denied';
        $rules[] = '</FilesMatch>';
        $rules[] = '';
        
        // Security headers
        $rules[] = '# Security headers';
        $rules[] = 'Header always set X-Content-Type-Options nosniff';
        $rules[] = 'Header always set X-Frame-Options SAMEORIGIN';
        $rules[] = 'Header always set X-XSS-Protection "1; mode=block"';
        $rules[] = 'Header always set Referrer-Policy "strict-origin-when-cross-origin"';
        $rules[] = '';
        
        // Hide server information
        $rules[] = '# Hide server information';
        $rules[] = 'Header unset Server';
        $rules[] = 'Header unset X-Powered-By';
        $rules[] = 'Header unset X-PHP-Version';
        $rules[] = '';
        
        return implode("\n", $rules);
    }
    
    /**
     * Check if a file is considered sensitive
     */
    public function is_sensitive_file($file_path) {
        $sensitive_patterns = array(
            '/readme\.(html|txt|md)$/i',
            '/license\.(txt|md)$/i',
            '/changelog\.(txt|md)$/i',
            '/wp-config\.php$/i',
            '/\.htaccess$/i',
            '/\.(log|err)$/i',
            '/\.(bak|backup|old|orig|save|tmp)$/i',
            '/\.(conf|config|ini|env)$/i'
        );
        
        foreach ($sensitive_patterns as $pattern) {
            if (preg_match($pattern, $file_path)) {
                return true;
            }
        }
        
        return false;
    }
}