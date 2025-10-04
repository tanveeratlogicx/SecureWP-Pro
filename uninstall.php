<?php
/**
 * SecureWP Pro Uninstall Handler
 * 
 * @package     SecureWP_Pro
 * @author      Tanveer Malik
 * @link        
 * @since       1.0.0
 */

// If uninstall.php is not called by WordPress, exit
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit();
}

// Prevent accidental execution if plugin is still active
if ( function_exists( 'securewp_pro_plugin_active' ) ) {
    return;
}

/**
 * SecureWP Pro Uninstall Handler
 * 
 * Removes all plugin data and settings when the plugin is deleted
 * from the WordPress plugins page.
 */
class SecureWP_Pro_Uninstaller {
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->remove_plugin_options();
        $this->remove_database_tables();
        $this->remove_cron_events();
        $this->clear_cache();
    }
    
    /**
     * Remove all plugin options from the database
     */
    private function remove_plugin_options() {
        // Single site options
        $options = array(
            'securewp_pro_cron_security',
            'securewp_pro_xmlrpc_security',
            'securewp_pro_login_rate_limiting',
            'securewp_pro_rest_api_security',
            'securewp_pro_password_reset_rate_limiting',
            'securewp_pro_general_rate_limiting',
            'securewp_pro_lockout_times',
            'securewp_pro_cron_key',
        );
        
        foreach ( $options as $option ) {
            delete_option( $option );
        }
        
        // Multisite support (site options)
        if ( is_multisite() ) {
            foreach ( $options as $option ) {
                delete_site_option( $option );
            }
        }
    }
    
    /**
     * Remove custom database tables created by the plugin
     */
    private function remove_database_tables() {
        global $wpdb;
        
        $tables = array(
            $wpdb->prefix . 'securewp_pro_lockouts',
            $wpdb->prefix . 'securewp_pro_logs',
        );
        
        foreach ( $tables as $table ) {
            $wpdb->query( "DROP TABLE IF EXISTS {$table}" );
        }
    }
    
    /**
     * Remove any scheduled cron events
     */
    private function remove_cron_events() {
        $cron_events = array(
            'securewp_pro_cleanup_logs',
            'securewp_pro_lockout_expiry_check',
        );
        
        foreach ( $cron_events as $event ) {
            wp_clear_scheduled_hook( $event );
        }
    }
    
    /**
     * Clear any cached data related to the plugin
     */
    private function clear_cache() {
        if ( function_exists( 'wp_cache_flush' ) ) {
            wp_cache_flush();
        }
    }
}

// Initialize the uninstaller
new SecureWP_Pro_Uninstaller();