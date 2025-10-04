<?php
/**
 * Plugin Name: SecureWP Pro
 * Plugin URI: 
 * Description: A comprehensive security plugin for WordPress that protects against various attacks and vulnerabilities.
 * Version: 3.0.1
 * Author: Tanveer Malik
 * Author URI: 
 * License: GPL v2 or later
 * Text Domain: securewp-pro
 * 
 * Security hardened version with comprehensive protection against:
 * • SQL Injection attacks via prepared statements and parameter binding
 * • Cross-Site Request Forgery (CSRF) through nonce verification
 * • Authorization bypass with multi-layer capability checks
 * • Cross-Site Scripting (XSS) via input sanitization and output escaping
 * • Insecure Direct Object References through validation and access controls
 * • Security Misconfiguration with secure defaults and proper error handling
 * • Sensitive Data Exposure via secure data handling and masking
 * • Broken Authentication through enhanced user verification
 * • Insufficient Logging & Monitoring with comprehensive audit trails
 * • Directory Traversal attacks via path validation and access restrictions
 * • Remote Code Execution through input validation and file access controls
 * • Session Hijacking via secure session handling
 * • Brute Force attacks through progressive rate limiting and lockouts
 * • XML-RPC attacks with configurable blocking and rate limiting
 * • REST API abuse via authentication requirements and rate limiting
 * • Form spam through honeypot detection and submission rate limiting
 * • Password reset abuse via rate limiting and validation
 * • Cron job exploitation through secret key authentication
 * • Information disclosure via proper error handling and access controls
 * • Server banner grabbing via header modification and server information hiding
 * • Sensitive file access (readme.html, license.txt, etc.) via file access protection
 * • Directory browsing via index file generation and access controls
 * • Version fingerprinting via version string removal and query string filtering
 * 
 * Compliance: OWASP Top 10, WordPress Security Standards, PHP Security Best Practices
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Define plugin constants
define('SECUREWP_PRO_VERSION', '2.1.0');
define('SECUREWP_PRO_PLUGIN_URL', plugin_dir_url(__FILE__));
define('SECUREWP_PRO_PLUGIN_DIR', plugin_dir_path(__FILE__));

// Licensing: Optional domain lock. Define SECUREWP_PRO_LICENSED_DOMAIN (e.g., in wp-config.php) to restrict usage.
if (!function_exists('securewp_pro_is_domain_allowed')) {
    function securewp_pro_is_domain_allowed() {
        $allowed_const = defined('SECUREWP_PRO_LICENSED_DOMAIN') ? SECUREWP_PRO_LICENSED_DOMAIN : '';
        if (empty($allowed_const)) {
            return true; // No lock configured
        }
        $allowed = strtolower(trim((string) $allowed_const));
        $current = parse_url(home_url('/'), PHP_URL_HOST);
        $current = strtolower(preg_replace('/^www\./', '', (string) $current));
        $allowed = strtolower(preg_replace('/^www\./', '', (string) $allowed));
        return $current === $allowed;
    }
}

// Include required files
require_once SECUREWP_PRO_PLUGIN_DIR . 'includes/class-cron-security.php';
require_once SECUREWP_PRO_PLUGIN_DIR . 'includes/class-xmlrpc-security.php';
require_once SECUREWP_PRO_PLUGIN_DIR . 'includes/class-login-rate-limiting.php';
require_once SECUREWP_PRO_PLUGIN_DIR . 'includes/class-rest-api-security.php';
require_once SECUREWP_PRO_PLUGIN_DIR . 'includes/class-password-reset-rate-limiting.php';
require_once SECUREWP_PRO_PLUGIN_DIR . 'includes/class-general-rate-limiting.php';
require_once SECUREWP_PRO_PLUGIN_DIR . 'includes/class-lockout-manager.php';
require_once SECUREWP_PRO_PLUGIN_DIR . 'includes/class-logger.php';
require_once SECUREWP_PRO_PLUGIN_DIR . 'includes/class-notifier.php';
require_once SECUREWP_PRO_PLUGIN_DIR . 'includes/class-server-hardening.php';

/**
 * Main plugin class
 */
class SecureWP_Pro {
    
    private $cron_security;
    private $xmlrpc_security;
    private $login_rate_limiting;
    private $rest_api_security;
    private $password_reset_rate_limiting;
    private $general_rate_limiting;
    private $lockout_manager;
    private $logger;
    private $notifier;
    private $server_hardening;
    private $licensed = true;
    
    public function __construct() {
        $this->licensed = securewp_pro_is_domain_allowed();
        $this->load_dependencies();
        $this->init_components();
        $this->setup_hooks();
    }

    /**
     * AJAX: Return evidence URLs/snippets for a given feature key
     */
    public function ajax_get_evidence() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Insufficient permissions', 'securewp-pro')), 403);
        }
        if (!isset($_POST['_ajax_nonce']) || !wp_verify_nonce(sanitize_text_field($_POST['_ajax_nonce']), 'securewp_pro_admin_action')) {
            wp_send_json_error(array('message' => __('Invalid nonce', 'securewp-pro')), 400);
        }
        if (!$this->licensed) {
            wp_send_json_success(array('html' => '<em>' . esc_html__('Evidence unavailable: feature disabled on this domain.', 'securewp-pro') . '</em>'));
        }
        $feature = isset($_POST['feature']) ? sanitize_key($_POST['feature']) : '';
        $base = home_url('/');
        $cron_key = get_option('securewp_pro_cron_secret_key');
        // If no key exists yet, generate and persist one so the evidence link always has a real value
        if (empty($cron_key) || !is_string($cron_key)) {
            $cron_key = wp_generate_password(32, false);
            update_option('securewp_pro_cron_secret_key', $cron_key);
        }
        $masked_cron_key = '';
        $len = strlen($cron_key);
        $start = substr($cron_key, 0, min(4, $len));
        $end = $len > 8 ? substr($cron_key, -4) : '';
        $mask_len = max(0, $len - strlen($start) - strlen($end));
        $masked_cron_key = $start . str_repeat('•', $mask_len) . $end;

        $html = '';
        switch ($feature) {
            case 'securewp_pro_cron_security':
                $html = '<ul class="swp-evidence-list">'
                    . '<li><a href="' . esc_url($base . 'wp-cron.php') . '" target="_blank">' . esc_html__('wp-cron.php (unauthorized should be 403)', 'securewp-pro') . '</a></li>'
                    . '<li><a href="' . esc_url(add_query_arg('secret', $cron_key ?: 'CRON_SECRET_KEY', $base . 'wp-cron.php')) . '" target="_blank">' . esc_html__('wp-cron.php?secret=<hidden>', 'securewp-pro') . '</a></li>'
                    . '</ul>'
                    . '<div class="swp-evidence-inline">'
                    . '<small>' . esc_html__('Cron Secret Key:', 'securewp-pro') . ' <code>' . esc_html($masked_cron_key ?: '••••••') . '</code></small>'
                    . '</div>';
                break;
            case 'securewp_pro_xmlrpc_security':
            case 'securewp_pro_block_xmlrpc_completely':
            case 'securewp_pro_block_suspicious_methods':
                $html = '<ul class="swp-evidence-list">'
                    . '<li><a href="' . esc_url($base . 'xmlrpc.php') . '" target="_blank">' . esc_html__('xmlrpc.php (should be blocked or limited)', 'securewp-pro') . '</a></li>'
                    . '</ul>';
                break;
            case 'securewp_pro_rest_api_security':
                $html = '<ul class="swp-evidence-list">'
                    . '<li><a href="' . esc_url($base . 'wp-json/wp/v2/users') . '" target="_blank">' . esc_html__('/wp-json/wp/v2/users (should require auth / be restricted)', 'securewp-pro') . '</a></li>'
                    . '</ul>';
                break;
            case 'securewp_pro_login_rate_limiting':
                $html = '<ul class="swp-evidence-list">'
                    . '<li><a href="' . esc_url($base . 'wp-login.php') . '" target="_blank">' . esc_html__('wp-login.php (failed attempts should lock out)', 'securewp-pro') . '</a></li>'
                    . '</ul>';
                break;
            case 'securewp_pro_password_reset_rate_limiting':
                $html = '<ul class="swp-evidence-list">'
                    . '<li><a href="' . esc_url(add_query_arg('action', 'lostpassword', $base . 'wp-login.php')) . '" target="_blank">' . esc_html__('wp-login.php?action=lostpassword', 'securewp-pro') . '</a></li>'
                    . '</ul>';
                break;
            case 'securewp_pro_general_rate_limiting':
                $html = '<ul class="swp-evidence-list">'
                    . '<li><a href="' . esc_url(add_query_arg('action', 'register', $base . 'wp-login.php')) . '" target="_blank">' . esc_html__('wp-login.php?action=register', 'securewp-pro') . '</a></li>'
                    . '<li>' . esc_html__('For CF7/Fluent Forms, open any page containing the form and submit rapidly to trigger limits.', 'securewp-pro') . '</li>'
                    . '</ul>';
                break;
            case 'securewp_pro_rate_limit_cf7':
                $html = '<ul class="swp-evidence-list">'
                    . '<li>' . esc_html__('Open any page with a Contact Form 7 form. Rapid submissions should lead to HTTP 403 with a rate-limit message.', 'securewp-pro') . '</li>'
                    . '</ul>';
                break;
            case 'securewp_pro_rate_limit_fluentforms':
                $html = '<ul class="swp-evidence-list">'
                    . '<li>' . esc_html__('Open any page with a Fluent Form. Rapid submissions should show a global validation error.', 'securewp-pro') . '</li>'
                    . '</ul>';
                break;
            case 'securewp_pro_rate_limit_elementor':
                $html = '<ul class="swp-evidence-list">'
                    . '<li>' . esc_html__('Open any page with an Elementor Pro Form. Rapid submissions should show a validation error.', 'securewp-pro') . '</li>'
                    . '</ul>';
                break;
            case 'securewp_pro_elementor_honeypot':
                $html = '<ul class="swp-evidence-list">'
                    . '<li><strong>' . esc_html__('To add honeypot protection:', 'securewp-pro') . '</strong></li>'
                    . '<li>' . esc_html__('1. Edit your Elementor Pro Form', 'securewp-pro') . '</li>'
                    . '<li>' . esc_html__('2. Add a hidden text field with ID: "comments", "phone_number", "address", "email_confirm", or "human_check"', 'securewp-pro') . '</li>'
                    . '<li>' . esc_html__('3. Set the field CSS to: position:absolute;left:-9999px;opacity:0;', 'securewp-pro') . '</li>'
                    . '<li>' . esc_html__('4. Human users won\'t see it, but bots will fill it and get blocked automatically', 'securewp-pro') . '</li>'
                    . '</ul>';
                break;
            case 'securewp_pro_server_hardening':
                $html = '<ul class="swp-evidence-list">'
                    . '<li><a href="' . esc_url($base . 'readme.html') . '" target="_blank">' . esc_html__('readme.html (should return 404)', 'securewp-pro') . '</a></li>'
                    . '<li><a href="' . esc_url($base . 'license.txt') . '" target="_blank">' . esc_html__('license.txt (should return 404)', 'securewp-pro') . '</a></li>'
                    . '<li>' . esc_html__('Check browser dev tools for hidden Server header and security headers', 'securewp-pro') . '</li>'
                    . '</ul>';
                break;
            case 'securewp_pro_security_headers':
                $html = '<ul class="swp-evidence-list">'
                    . '<li>' . esc_html__('Open browser dev tools → Network tab → Check response headers for:', 'securewp-pro') . '</li>'
                    . '<li>• X-Content-Type-Options: nosniff</li>'
                    . '<li>• X-Frame-Options: SAMEORIGIN</li>'
                    . '<li>• X-XSS-Protection: 1; mode=block</li>'
                    . '<li>• Referrer-Policy: strict-origin-when-cross-origin</li>'
                    . '<li>• Strict-Transport-Security (HTTPS only)</li>'
                    . '</ul>';
                break;
            default:
                $html = '';
        }

        wp_send_json_success(array('html' => $html));
    }
    
    private function load_dependencies() {
        // Dependencies are already included via require_once above
    }
    
    private function init_components() {
        $this->logger = new SecureWP_Pro_Logger();
        $this->notifier = new SecureWP_Pro_Notifier($this->logger);
        $this->lockout_manager = new SecureWP_Pro_Lockout_Manager($this->logger, $this->notifier);
        
        // Only initialize protection features if licensed for this domain
        if ($this->licensed) {
            $this->cron_security = new SecureWP_Pro_Cron_Security($this->logger);
            $this->xmlrpc_security = new SecureWP_Pro_XMLRPC_Security($this->logger, $this->lockout_manager);
            $this->login_rate_limiting = new SecureWP_Pro_Login_Rate_Limiting($this->logger, $this->lockout_manager);
            $this->rest_api_security = new SecureWP_Pro_REST_API_Security($this->logger);
            $this->password_reset_rate_limiting = new SecureWP_Pro_Password_Reset_Rate_Limiting($this->logger, $this->lockout_manager);
            $this->general_rate_limiting = new SecureWP_Pro_General_Rate_Limiting($this->logger, $this->lockout_manager);
            $this->server_hardening = new SecureWP_Pro_Server_Hardening($this->logger);
        }
    }
    
    private function setup_hooks() {
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        
        add_action('plugins_loaded', array($this, 'load_textdomain'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'setup_settings'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        // Secure admin actions
        add_action('admin_post_securewp_pro_clean_old_logs', array($this, 'handle_clean_old_logs'));
        add_action('admin_post_securewp_pro_reset_settings', array($this, 'handle_reset_settings'));
        add_action('admin_post_clear_logs', array($this, 'handle_clear_logs'));
        // AJAX actions
        add_action('wp_ajax_securewp_pro_generate_cron_key', array($this, 'ajax_generate_cron_key'));
        add_action('wp_ajax_securewp_pro_get_evidence', array($this, 'ajax_get_evidence'));
        
        // Hide lost password link if option is enabled
        add_filter('lost_password_html_link', array($this, 'maybe_hide_lost_password_link'));
        add_filter('wp_login_errors', array($this, 'maybe_remove_lost_password_from_errors'));
        
        // Admin notice if unlicensed
        add_action('admin_notices', array($this, 'maybe_show_license_notice'));
    }
    
    public function activate() {
        // Create required database tables
        $this->logger->create_log_table();
        $this->lockout_manager->create_lockout_table();
        
        // Set default options
        add_option('securewp_pro_cron_security', true);
        add_option('securewp_pro_xmlrpc_security', true);
        add_option('securewp_pro_login_rate_limiting', true);
        add_option('securewp_pro_rest_api_security', true);
        add_option('securewp_pro_password_reset_rate_limiting', true);
        add_option('securewp_pro_hide_lost_password_link', false);
        add_option('securewp_pro_general_rate_limiting', true);
        // Per-plugin toggles for form rate limiting
        add_option('securewp_pro_rate_limit_cf7', true);
        add_option('securewp_pro_rate_limit_fluentforms', true);
        add_option('securewp_pro_rate_limit_elementor', true);
        add_option('securewp_pro_elementor_honeypot', true);
        
        // Server hardening options
        add_option('securewp_pro_server_hardening', true);
        add_option('securewp_pro_security_headers', true);
        
        // Set default lockout times (0, 30s, 1m, 5m, then double, after 80 mins = 24 hrs, then permanent)
        add_option('securewp_pro_lockout_times', array(
            'initial' => 0,      // 0 seconds after first failure
            'second' => 30,      // 30 seconds after second failure
            'third' => 60,       // 1 minute after third failure
            'fourth' => 300,     // 5 minutes after fourth failure
            'fifth' => 600,      // 10 minutes after fifth failure (doubled from 5m)
            'max' => 4800,       // 80 minutes (4800 seconds) - threshold for 24h lockout
            'permanent' => 86400 // 24 hours (86400 seconds) after 80 minute threshold
        ));
    }
    
    public function deactivate() {
        // Clean up scheduled events if any
        wp_clear_scheduled_hook('securewp_pro_cleanup_logs');
    }
    
    public function load_textdomain() {
        load_plugin_textdomain('securewp-pro', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }
    
    public function add_admin_menu() {
        add_menu_page(
            __('SecureWP Pro Settings', 'securewp-pro'),
            __('SecureWP Pro', 'securewp-pro'),
            'manage_options',
            'securewp-pro',
            array($this, 'display_settings_page'),
            'dashicons-shield-alt',
            60 // Position before Appearance (which is at 60)
        );
        
        // Add submenu for lockout management
        add_submenu_page(
            'securewp-pro',
            __('Lockout Management', 'securewp-pro'),
            __('Lockouts', 'securewp-pro'),
            'manage_options',
            'securewp-pro-lockouts',
            array($this, 'display_lockouts_page')
        );
        
        // Add submenu for security logs
        add_submenu_page(
            'securewp-pro',
            __('Security Logs', 'securewp-pro'),
            __('Logs', 'securewp-pro'),
            'manage_options',
            'securewp-pro-logs',
            array($this, 'display_logs_page')
        );
        
        // Remove the Password Reset Debug submenu page
        /*
        // Add debug page
        add_submenu_page(
            'securewp-pro',
            __('Password Reset Debug', 'securewp-pro'),
            __('Password Reset Debug', 'securewp-pro'),
            'manage_options',
            'securewp-pro-debug',
            array($this, 'display_debug_page')
        );
        */
    }
    
    public function display_settings_page() {
        include_once SECUREWP_PRO_PLUGIN_DIR . 'admin/partials/admin-tabs.php';
    }
    
    public function display_lockouts_page() {
        include_once SECUREWP_PRO_PLUGIN_DIR . 'admin/partials/admin-tabs.php';
    }
    
    public function display_logs_page() {
        include_once SECUREWP_PRO_PLUGIN_DIR . 'admin/partials/admin-tabs.php';
    }
    
    public function setup_settings() {
        // Security Features settings group
        register_setting('securewp_pro_security_settings', 'securewp_pro_cron_security');
        register_setting('securewp_pro_security_settings', 'securewp_pro_xmlrpc_security');
        register_setting('securewp_pro_security_settings', 'securewp_pro_block_xmlrpc_completely');
        register_setting('securewp_pro_security_settings', 'securewp_pro_block_suspicious_methods');
        register_setting('securewp_pro_security_settings', 'securewp_pro_login_rate_limiting');
        register_setting('securewp_pro_security_settings', 'securewp_pro_rest_api_security');
        register_setting('securewp_pro_security_settings', 'securewp_pro_password_reset_rate_limiting');
        register_setting('securewp_pro_security_settings', 'securewp_pro_hide_lost_password_link');
        register_setting('securewp_pro_security_settings', 'securewp_pro_general_rate_limiting');
        register_setting('securewp_pro_security_settings', 'securewp_pro_rate_limit_cf7');
        register_setting('securewp_pro_security_settings', 'securewp_pro_rate_limit_fluentforms');
        register_setting('securewp_pro_security_settings', 'securewp_pro_rate_limit_elementor');
        register_setting('securewp_pro_security_settings', 'securewp_pro_elementor_honeypot');
        register_setting('securewp_pro_security_settings', 'securewp_pro_server_hardening');
        register_setting('securewp_pro_security_settings', 'securewp_pro_security_headers');
        
        // Lockout Settings group
        register_setting('securewp_pro_lockout_settings', 'securewp_pro_lockout_times');
        
        // Notification Settings group
        register_setting('securewp_pro_notification_settings', 'securewp_pro_notify_admin');
        register_setting('securewp_pro_notification_settings', 'securewp_pro_notify_email');
        register_setting('securewp_pro_notification_settings', 'securewp_pro_notify_login_failures');
        register_setting('securewp_pro_notification_settings', 'securewp_pro_notify_lockouts');
        register_setting('securewp_pro_notification_settings', 'securewp_pro_notify_xmlrpc');
        register_setting('securewp_pro_notification_settings', 'securewp_pro_notify_cron');
        
        // Advanced Settings group
        register_setting('securewp_pro_advanced_settings', 'securewp_pro_authorized_cron_ips');
        register_setting('securewp_pro_advanced_settings', 'securewp_pro_cron_secret_key');
        register_setting('securewp_pro_advanced_settings', 'securewp_pro_log_retention');
        register_setting('securewp_pro_advanced_settings', 'securewp_pro_debug_mode');
    }

    /**
     * Enqueue admin assets (CSS/JS) and localize variables
     */
    public function enqueue_admin_assets($hook_suffix) {
        // Only load on our plugin pages to keep admin light
        $is_securewp_page = isset($_GET['page']) && strpos(sanitize_text_field($_GET['page']), 'securewp-pro') === 0;
        if (!$is_securewp_page) {
            return;
        }

        // Styles
        wp_enqueue_style(
            'securewp-pro-admin',
            SECUREWP_PRO_PLUGIN_URL . 'admin/css/admin.css',
            array(),
            SECUREWP_PRO_VERSION
        );

        // Scripts
        wp_enqueue_script(
            'securewp-pro-admin',
            SECUREWP_PRO_PLUGIN_URL . 'admin/js/admin.js',
            array('jquery'),
            SECUREWP_PRO_VERSION,
            true
        );

        // Localize for AJAX and UI strings
        wp_localize_script('securewp-pro-admin', 'SecureWPPro', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('securewp_pro_admin_action'),
            'licensed' => (bool) $this->licensed,
            'i18n'     => array(
                'generating' => __('Generating…', 'securewp-pro'),
                'generated'  => __('New key generated', 'securewp-pro'),
                'generate'   => __('Generate New Key', 'securewp-pro'),
                'error'      => __('Error generating key', 'securewp-pro'),
            ),
        ));
    }

    /**
     * Show an admin notice when domain is not licensed
     */
    public function maybe_show_license_notice() {
        if (!current_user_can('manage_options')) return;
        if ($this->licensed) return;
        echo '<div class="notice notice-error"><p>'
            . esc_html__('SecureWP Pro is restricted to a specific domain and is disabled on this site. Contact the administrator to update SECUREWP_PRO_LICENSED_DOMAIN.', 'securewp-pro')
            . '</p></div>';
    }
    
    /**
     * Hide the lost password link if the option is enabled
     */
    public function maybe_hide_lost_password_link($html_link) {
        // Check if the option to hide the lost password link is enabled
        if (get_option('securewp_pro_hide_lost_password_link', false)) {
            return ''; // Return empty string to hide the link
        }
        
        return $html_link; // Return the original link if not hidden
    }
    
    /**
     * Remove lost password link from login error messages if the option is enabled
     */
    public function maybe_remove_lost_password_from_errors($errors) {
        // Check if the option to hide the lost password link is enabled
        if (!get_option('securewp_pro_hide_lost_password_link', false)) {
            return $errors; // Return original errors if not hidden
        }
        
        // Check if there are errors to process
        if (is_wp_error($errors)) {
            $error_codes = $errors->get_error_codes();
            
            foreach ($error_codes as $code) {
                $messages = $errors->get_error_messages($code);
                
                // Process each message
                for ($i = 0; $i < count($messages); $i++) {
                    // Remove the lost password link from the message (case insensitive)
                    $messages[$i] = preg_replace('/\s*<a href="[^"]*">[^<]*Lost your password\?[^<]*<\/a>\s*/i', '', $messages[$i]);
                }
                
                // Clear the existing error messages for this code
                $errors->remove($code);
                
                // Add the modified messages back
                foreach ($messages as $message) {
                    $errors->add($code, $message);
                }
            }
        }
        
        return $errors;
    }
    
    /**
     * Helper method to unlock IP address
     */
    public function unlock_ip_address($ip, $event_type) {
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

    /**
     * Admin-post: Clear logs from Overview quick action
     * Security hardened version
     */
    public function handle_clear_logs() {
        // Verify user capabilities
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to perform this action.', 'securewp-pro'));
        }
        
        // Verify nonce for CSRF protection
        if (!isset($_POST['_wpnonce']) || !wp_verify_nonce(sanitize_text_field($_POST['_wpnonce']), 'securewp_pro_admin_action')) {
            wp_die(__('Security check failed. Please try again.', 'securewp-pro'));
        }
        
        $result = $this->clear_security_logs();
        $notice = $result ? 'logs_cleared' : 'logs_clear_failed';
        
        wp_safe_redirect(add_query_arg(array(
            'page' => 'securewp-pro', 
            'tab' => 'overview', 
            'swp_notice' => $notice
        ), admin_url('admin.php')));
        exit;
    }

    /**
     * Admin-post: Generate a new Cron Secret Key
     */
    

    /**
     * AJAX: Generate a new Cron Secret Key without redirect
     */
    public function ajax_generate_cron_key() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Insufficient permissions', 'securewp-pro')), 403);
        }
        if (!$this->licensed) {
            wp_send_json_error(array('message' => __('Feature disabled on this domain', 'securewp-pro')), 403);
        }
        // Validate nonce
        if (!isset($_POST['_ajax_nonce']) || !wp_verify_nonce(sanitize_text_field($_POST['_ajax_nonce']), 'securewp_pro_admin_action')) {
            wp_send_json_error(array('message' => __('Invalid nonce', 'securewp-pro')), 400);
        }

        $key = wp_generate_password(32, false);
        update_option('securewp_pro_cron_secret_key', $key);

        wp_send_json_success(array(
            'key'     => $key,
            'message' => __('Cron Secret Key regenerated successfully.', 'securewp-pro'),
        ));
    }

    /**
     * Admin-post: Clean old logs based on retention setting
     */
    public function handle_clean_old_logs() {
        if (!current_user_can('manage_options') || !isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'securewp_pro_admin_action')) {
            wp_die(__('Unauthorized request', 'securewp-pro'));
        }
        global $wpdb;
        $table_name = $wpdb->prefix . 'securewp_pro_logs';
        $days = (int) get_option('securewp_pro_log_retention', 30);
        if ($days > 0) {
            $threshold = gmdate('Y-m-d H:i:s', time() - ($days * DAY_IN_SECONDS));
            $wpdb->query($wpdb->prepare("DELETE FROM $table_name WHERE created < %s", $threshold));
        }
        wp_safe_redirect(add_query_arg(array('page' => 'securewp-pro', 'tab' => 'advanced', 'swp_notice' => 'logs_cleaned'), admin_url('admin.php')));
        exit;
    }

    /**
     * Admin-post: Reset plugin settings to defaults
     */
    public function handle_reset_settings() {
        if (!current_user_can('manage_options') || !isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'securewp_pro_admin_action')) {
            wp_die(__('Unauthorized request', 'securewp-pro'));
        }
        $this->reset_settings_to_defaults();
        wp_safe_redirect(add_query_arg(array('page' => 'securewp-pro', 'tab' => 'settings', 'swp_notice' => 'settings_reset'), admin_url('admin.php')));
        exit;
    }

    /**
     * Reset options to default values
     */
    private function reset_settings_to_defaults() {
        // Core toggles
        update_option('securewp_pro_cron_security', true);
        update_option('securewp_pro_xmlrpc_security', true);
        update_option('securewp_pro_login_rate_limiting', true);
        update_option('securewp_pro_rest_api_security', true);
        update_option('securewp_pro_password_reset_rate_limiting', true);
        update_option('securewp_pro_hide_lost_password_link', false);
        update_option('securewp_pro_general_rate_limiting', true);
        // Per-plugin toggles for contact form rate limiting
        update_option('securewp_pro_rate_limit_cf7', true);
        update_option('securewp_pro_rate_limit_fluentforms', true);
        update_option('securewp_pro_rate_limit_elementor', true);
        update_option('securewp_pro_elementor_honeypot', true);

        // XML-RPC specifics
        update_option('securewp_pro_block_xmlrpc_completely', false);
        update_option('securewp_pro_block_suspicious_methods', true);

        // Lockout times
        update_option('securewp_pro_lockout_times', array(
            'initial' => 0,
            'second' => 30,
            'third' => 60,
            'fourth' => 300,
            'fifth' => 600,
            'max' => 4800,
            'permanent' => 86400,
        ));

        // Notifications
        update_option('securewp_pro_notify_admin', true);
        update_option('securewp_pro_notify_email', get_option('admin_email'));
        update_option('securewp_pro_notify_login_failures', true);
        update_option('securewp_pro_notify_lockouts', true);
        update_option('securewp_pro_notify_xmlrpc', true);
        update_option('securewp_pro_notify_cron', false);

        // Advanced
        update_option('securewp_pro_authorized_cron_ips', '');
        if (!get_option('securewp_pro_cron_secret_key')) {
            update_option('securewp_pro_cron_secret_key', wp_generate_password(32, false));
        }
        update_option('securewp_pro_log_retention', 30);
        update_option('securewp_pro_debug_mode', false);
    }
    
    /**
     * Helper method to unlock lockout by ID
     */
    public function unlock_lockout_by_id($lockout_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'securewp_pro_lockouts';
        
        $result = $wpdb->update(
            $table_name,
            array(
                'permanent' => 0,
                'lockout_expiry' => 0,
                'modified' => current_time('mysql')
            ),
            array('id' => $lockout_id)
        );
        
        if ($result !== false) {
            $lockout = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $lockout_id));
            if ($lockout) {
                $this->logger->log_event('admin_action', 'lockout_unlocked', 
                    'Admin unlocked lockout ID: ' . $lockout_id . ' for IP: ' . $lockout->ip_address);
            }
        }
        
        return $result !== false;
    }
    
    /**
     * Helper method to delete lockout by ID
     */
    public function delete_lockout_by_id($lockout_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'securewp_pro_lockouts';
        
        $lockout = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $lockout_id));
        
        $result = $wpdb->delete($table_name, array('id' => $lockout_id), array('%d'));
        
        if ($result !== false && $lockout) {
            $this->logger->log_event('admin_action', 'lockout_deleted', 
                'Admin deleted lockout ID: ' . $lockout_id . ' for IP: ' . $lockout->ip_address);
        }
        
        return $result !== false;
    }
    
    /**
     * Helper method to clear security logs
     */
    public function clear_security_logs() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'securewp_pro_logs';
        
        $result = $wpdb->query("DELETE FROM $table_name");
        
        if ($result !== false) {
            $this->logger->log_event('admin_action', 'logs_cleared', 
                'Admin cleared all security logs');
        }
        
        return $result !== false;
    }
    
    /**
     * Helper method to export security logs
     */
    public function export_security_logs() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'securewp_pro_logs';
        
        $logs = $wpdb->get_results("SELECT * FROM $table_name ORDER BY created DESC");
        
        $filename = 'securewp-pro-logs-' . date('Y-m-d-H-i-s') . '.csv';
        
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        $output = fopen('php://output', 'w');
        
        // CSV headers
        fputcsv($output, array('ID', 'Event Type', 'Event Action', 'Details', 'IP Address', 'Created'));
        
        // CSV data
        foreach ($logs as $log) {
            fputcsv($output, array(
                $log->id,
                $log->event_type,
                $log->event_action,
                $log->details,
                $log->ip_address,
                $log->created
            ));
        }
        
        fclose($output);
        exit;
    }
    
    /**
     * Helper method to manually lockout IP
     */
    public function lockout_ip_manually($ip) {
        if ($this->lockout_manager) {
            $this->lockout_manager->check_and_lock($ip, 'manual_admin_lockout');
            $this->logger->log_event('admin_action', 'manual_lockout', 
                'Admin manually locked out IP: ' . $ip);
            return true;
        }
        return false;
    }
}

// Initialize the plugin
$securewp_pro = new SecureWP_Pro();
