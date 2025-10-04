<?php
/**
 * Settings Tab - Configuration Options with Vertical Sub-tabs
 */

// Get current sub-tab
$settings_subtab = isset($_GET['subtab']) ? sanitize_text_field($_GET['subtab']) : 'security-features';
?>

<div class="securewp-settings">
    <h2><?php echo esc_html__('Security Settings', 'securewp-pro'); ?></h2>
    
    <!-- Unsaved Changes Notice -->
    <div id="securewp-unsaved-notice" class="notice notice-warning is-dismissible" style="display: none;">
        <p>
            <strong><?php echo esc_html__('You have unsaved changes!', 'securewp-pro'); ?></strong>
            <?php echo esc_html__('Please save your settings before navigating away from this page.', 'securewp-pro'); ?>
        </p>
    </div>
    <?php if (isset($_GET['swp_notice'])): ?>
        <div class="notice notice-success is-dismissible">
            <p>
                <?php
                $msg = sanitize_text_field($_GET['swp_notice']);
                if ($msg === 'cron_key_regenerated') {
                    echo esc_html__('Cron Secret Key regenerated successfully.', 'securewp-pro');
                } elseif ($msg === 'logs_cleaned') {
                    echo esc_html__('Old logs cleaned successfully.', 'securewp-pro');
                } elseif ($msg === 'settings_reset') {
                    echo esc_html__('Settings reset to defaults.', 'securewp-pro');
                }
                ?>
            </p>
        </div>
    <?php endif; ?>
    
    <!-- Uninstall Warning Notice -->
    <div class="notice notice-warning">
        <p><strong><?php echo esc_html__('Important:', 'securewp-pro'); ?></strong> 
        <?php echo esc_html__('If you uninstall this plugin, all security logs and lockout data will be permanently deleted. Make sure to backup any important security data before uninstalling.', 'securewp-pro'); ?>
        </p>
    </div>
    
    <!-- Vertical Sub-tabs Navigation -->
    <div class="settings-tabs-wrapper">
        <div class="settings-nav-tabs">
            <a href="?page=securewp-pro&tab=settings&subtab=security-features" 
               class="settings-nav-tab <?php echo $settings_subtab === 'security-features' ? 'active' : ''; ?>">
                <span class="dashicons dashicons-shield"></span>
                <span class="tab-label"><?php echo esc_html__('Security Features', 'securewp-pro'); ?></span>
                <span class="tab-desc"><?php echo esc_html__('Core security protections', 'securewp-pro'); ?></span>
            </a>
            
            <a href="?page=securewp-pro&tab=settings&subtab=lockout" 
               class="settings-nav-tab <?php echo $settings_subtab === 'lockout' ? 'active' : ''; ?>">
                <span class="dashicons dashicons-lock"></span>
                <span class="tab-label"><?php echo esc_html__('Lockout Settings', 'securewp-pro'); ?></span>
                <span class="tab-desc"><?php echo esc_html__('IP lockout configuration', 'securewp-pro'); ?></span>
            </a>
            
            <a href="?page=securewp-pro&tab=settings&subtab=notifications" 
               class="settings-nav-tab <?php echo $settings_subtab === 'notifications' ? 'active' : ''; ?>">
                <span class="dashicons dashicons-email-alt"></span>
                <span class="tab-label"><?php echo esc_html__('Notifications', 'securewp-pro'); ?></span>
                <span class="tab-desc"><?php echo esc_html__('Email alerts & reports', 'securewp-pro'); ?></span>
            </a>
            
            <a href="?page=securewp-pro&tab=settings&subtab=advanced" 
               class="settings-nav-tab <?php echo $settings_subtab === 'advanced' ? 'active' : ''; ?>">
                <span class="dashicons dashicons-admin-tools"></span>
                <span class="tab-label"><?php echo esc_html__('Advanced', 'securewp-pro'); ?></span>
                <span class="tab-desc"><?php echo esc_html__('Advanced configuration', 'securewp-pro'); ?></span>
            </a>
        </div>
        
        <div class="settings-content">
            <?php if ($settings_subtab === 'security-features'): ?>
                <form method="post" action="options.php">
                    <?php
                    settings_fields('securewp_pro_security_settings');
                    do_settings_sections('securewp_pro_security_settings');
                    ?>
                    
                    <!-- Top Save Button Area -->
                    <div class="securewp-top-save-area">
                        <div class="securewp-save-wrapper">
                            <?php submit_button(__('Save Settings', 'securewp-pro'), 'primary', 'submit', false); ?>
                            <button type="button" class="button securewp-cancel-button" style="display: none;">
                                <?php echo esc_html__('Cancel Changes', 'securewp-pro'); ?>
                            </button>
                            <span class="securewp-save-description"><?php echo esc_html__('Save your security feature settings', 'securewp-pro'); ?></span>
                        </div>
                    </div>
            <?php elseif ($settings_subtab === 'lockout'): ?>
                <form method="post" action="options.php">
                    <?php
                    settings_fields('securewp_pro_lockout_settings');
                    do_settings_sections('securewp_pro_lockout_settings');
                    ?>
                    
                    <!-- Top Save Button Area -->
                    <div class="securewp-top-save-area">
                        <div class="securewp-save-wrapper">
                            <?php submit_button(__('Save Settings', 'securewp-pro'), 'primary', 'submit', false); ?>
                            <button type="button" class="button securewp-cancel-button" style="display: none;">
                                <?php echo esc_html__('Cancel Changes', 'securewp-pro'); ?>
                            </button>
                            <span class="securewp-save-description"><?php echo esc_html__('Save your lockout configuration', 'securewp-pro'); ?></span>
                        </div>
                    </div>
            <?php elseif ($settings_subtab === 'notifications'): ?>
                <form method="post" action="options.php">
                    <?php
                    settings_fields('securewp_pro_notification_settings');
                    do_settings_sections('securewp_pro_notification_settings');
                    ?>
                    
                    <!-- Top Save Button Area -->
                    <div class="securewp-top-save-area">
                        <div class="securewp-save-wrapper">
                            <?php submit_button(__('Save Settings', 'securewp-pro'), 'primary', 'submit', false); ?>
                            <button type="button" class="button securewp-cancel-button" style="display: none;">
                                <?php echo esc_html__('Cancel Changes', 'securewp-pro'); ?>
                            </button>
                            <span class="securewp-save-description"><?php echo esc_html__('Save your notification preferences', 'securewp-pro'); ?></span>
                        </div>
                    </div>
            <?php elseif ($settings_subtab === 'advanced'): ?>
                <form method="post" action="options.php">
                    <?php
                    settings_fields('securewp_pro_advanced_settings');
                    do_settings_sections('securewp_pro_advanced_settings');
                    ?>
                    
                    <!-- Top Save Button Area -->
                    <div class="securewp-top-save-area">
                        <div class="securewp-save-wrapper">
                            <?php submit_button(__('Save Settings', 'securewp-pro'), 'primary', 'submit', false); ?>
                            <button type="button" class="button securewp-cancel-button" style="display: none;">
                                <?php echo esc_html__('Cancel Changes', 'securewp-pro'); ?>
                            </button>
                            <span class="securewp-save-description"><?php echo esc_html__('Save your advanced configuration', 'securewp-pro'); ?></span>
                        </div>
                    </div>
            <?php endif; ?>
                
                <?php if ($settings_subtab === 'security-features'): ?>
                    <!-- Security Features Sub-tab with Horizontal Tabs -->
                    <div class="settings-section">
                        <!-- Horizontal Tabs Navigation -->
                        <div class="securewp-horizontal-tabs">
                            <button type="button" class="securewp-tab-button active" data-tab="core-security">
                                <?php echo esc_html__('Core Security Features', 'securewp-pro'); ?>
                            </button>
                            <button type="button" class="securewp-tab-button" data-tab="server-hardening">
                                <?php echo esc_html__('Server Hardening Features', 'securewp-pro'); ?>
                            </button>
                            <button type="button" class="securewp-tab-button" data-tab="rate-limiting">
                                <?php echo esc_html__('Rate Limiting Features', 'securewp-pro'); ?>
                            </button>
                        </div>
                        
                        <!-- Tab Content Containers -->
                        <div class="securewp-tab-content">
                            <!-- Core Security Features Tab -->
                            <div class="securewp-tab-pane active" id="core-security">
                                <h3><?php echo esc_html__('Core Security Features', 'securewp-pro'); ?></h3>
                                <p class="description"><?php echo esc_html__('Configure the main security protections for your WordPress site.', 'securewp-pro'); ?></p>
                                
                                <table class="form-table">
                                    <tr>
                                        <th scope="row"><?php echo esc_html__('WP-Cron Security', 'securewp-pro'); ?></th>
                                        <td>
                                            <label>
                                                <input type="checkbox" name="securewp_pro_cron_security" value="1" 
                                                    <?php checked(1, get_option('securewp_pro_cron_security', true)); ?> />
                                                <?php echo esc_html__('Protect WP-Cron from DoS attacks', 'securewp-pro'); ?>
                                            </label>
                                            <p class="description">
                                                <?php echo esc_html__('Adds authentication to WP-Cron requests to prevent unauthorized access.', 'securewp-pro'); ?>
                                            </p>
                                            <div class="swp-evidence" data-feature="securewp_pro_cron_security"></div>
                                        </td>
                                    </tr>
                                    
                                    <tr>
                                        <th scope="row"><?php echo esc_html__('REST API Security', 'securewp-pro'); ?></th>
                                        <td>
                                            <label>
                                                <input type="checkbox" name="securewp_pro_rest_api_security" value="1" 
                                                    <?php checked(1, get_option('securewp_pro_rest_api_security', true)); ?> />
                                                <?php echo esc_html__('Protect REST API user endpoints', 'securewp-pro'); ?>
                                            </label>
                                            <p class="description">
                                                <?php echo esc_html__('Prevents disclosure of usernames through REST API endpoints.', 'securewp-pro'); ?>
                                            </p>
                                            <div class="swp-evidence" data-feature="securewp_pro_rest_api_security"></div>
                                        </td>
                                    </tr>
                                    
                                    <tr>
                                        <th scope="row"><?php echo esc_html__('XML-RPC Protection', 'securewp-pro'); ?></th>
                                        <td>
                                            <label>
                                                <input type="checkbox" name="securewp_pro_xmlrpc_security" value="1" 
                                                    <?php checked(1, get_option('securewp_pro_xmlrpc_security', true)); ?> />
                                                <?php echo esc_html__('Protect against XML-RPC pingback attacks', 'securewp-pro'); ?>
                                            </label>
                                            <p class="description">
                                                <?php echo esc_html__('Disables XML-RPC pingbacks to prevent DDoS attacks.', 'securewp-pro'); ?>
                                            </p>
                                            <div class="swp-evidence" data-feature="securewp_pro_xmlrpc_security"></div>
                                        </td>
                                    </tr>
                                    
                                    <tr>
                                        <th scope="row"><?php echo esc_html__('Block XML-RPC Completely', 'securewp-pro'); ?></th>
                                        <td>
                                            <label>
                                                <input type="checkbox" name="securewp_pro_block_xmlrpc_completely" value="1" 
                                                    <?php checked(1, get_option('securewp_pro_block_xmlrpc_completely', false)); ?> />
                                                <?php echo esc_html__('Completely disable XML-RPC (not recommended for mobile apps)', 'securewp-pro'); ?>
                                            </label>
                                            <p class="description">
                                                <?php echo esc_html__('Completely blocks all XML-RPC requests. Only enable if you don\'t use mobile apps or remote publishing.', 'securewp-pro'); ?>
                                            </p>
                                            <div class="swp-evidence" data-feature="securewp_pro_block_xmlrpc_completely"></div>
                                        </td>
                                    </tr>
                                    
                                    <tr>
                                        <th scope="row"><?php echo esc_html__('Block Suspicious XML-RPC Methods', 'securewp-pro'); ?></th>
                                        <td>
                                            <label>
                                                <input type="checkbox" name="securewp_pro_block_suspicious_methods" value="1" 
                                                    <?php checked(1, get_option('securewp_pro_block_suspicious_methods', true)); ?> />
                                                <?php echo esc_html__('Block suspicious XML-RPC methods (system.multicall, system.listMethods)', 'securewp-pro'); ?>
                                            </label>
                                            <p class="description">
                                                <?php echo esc_html__('Blocks potentially dangerous XML-RPC methods that can be used for attacks.', 'securewp-pro'); ?>
                                            </p>
                                            <div class="swp-evidence" data-feature="securewp_pro_block_suspicious_methods"></div>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                            
                            <!-- Server Hardening Features Tab -->
                            <div class="securewp-tab-pane" id="server-hardening">
                                <h3><?php echo esc_html__('Server Hardening Features', 'securewp-pro'); ?></h3>
                                <p class="description"><?php echo esc_html__('Advanced server hardening and information disclosure protection.', 'securewp-pro'); ?></p>
                                
                                <table class="form-table">
                                    <tr>
                                        <th scope="row"><?php echo esc_html__('Server Information Hiding', 'securewp-pro'); ?></th>
                                        <td>
                                            <label>
                                                <input type="checkbox" name="securewp_pro_server_hardening" value="1" 
                                                    <?php checked(1, get_option('securewp_pro_server_hardening', true)); ?> />
                                                <?php echo esc_html__('Hide server banners and block sensitive file access', 'securewp-pro'); ?>
                                            </label>
                                            <p class="description">
                                                <?php echo esc_html__('Hides server information, WordPress version, and blocks access to readme.html, license.txt, and other sensitive files.', 'securewp-pro'); ?>
                                            </p>
                                            <div class="swp-evidence" data-feature="securewp_pro_server_hardening"></div>
                                        </td>
                                    </tr>
                                    
                                    <tr>
                                        <th scope="row"><?php echo esc_html__('Security Headers', 'securewp-pro'); ?></th>
                                        <td>
                                            <label>
                                                <input type="checkbox" name="securewp_pro_security_headers" value="1" 
                                                    <?php checked(1, get_option('securewp_pro_security_headers', true)); ?> />
                                                <?php echo esc_html__('Add enhanced security headers', 'securewp-pro'); ?>
                                            </label>
                                            <p class="description">
                                                <?php echo esc_html__('Adds X-Content-Type-Options, X-Frame-Options, X-XSS-Protection, Referrer-Policy, and HSTS headers for enhanced security.', 'securewp-pro'); ?>
                                            </p>
                                            <div class="swp-evidence" data-feature="securewp_pro_security_headers"></div>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                            
                            <!-- Rate Limiting Features Tab -->
                            <div class="securewp-tab-pane" id="rate-limiting">
                                <h3><?php echo esc_html__('Rate Limiting Features', 'securewp-pro'); ?></h3>
                                <p class="description"><?php echo esc_html__('Configure rate limiting for various WordPress functions.', 'securewp-pro'); ?></p>
                                
                                <table class="form-table">
                                    <tr>
                                        <th scope="row"><?php echo esc_html__('Login Rate Limiting', 'securewp-pro'); ?></th>
                                        <td>
                                            <label>
                                                <input type="checkbox" name="securewp_pro_login_rate_limiting" value="1" 
                                                    <?php checked(1, get_option('securewp_pro_login_rate_limiting', true)); ?> />
                                                <?php echo esc_html__('Rate limit WordPress login attempts', 'securewp-pro'); ?>
                                            </label>
                                            <p class="description">
                                                <?php echo esc_html__('Blocks IP addresses after multiple failed login attempts.', 'securewp-pro'); ?>
                                            </p>
                                            <div class="swp-evidence" data-feature="securewp_pro_login_rate_limiting"></div>
                                        </td>
                                    </tr>
                                    
                                    <tr>
                                        <th scope="row"><?php echo esc_html__('Password Reset Rate Limiting', 'securewp-pro'); ?></th>
                                        <td>
                                            <label>
                                                <input type="checkbox" name="securewp_pro_password_reset_rate_limiting" value="1" 
                                                    <?php checked(1, get_option('securewp_pro_password_reset_rate_limiting', true)); ?> />
                                                <?php echo esc_html__('Rate limit password reset requests', 'securewp-pro'); ?>
                                            </label>
                                            <p class="description">
                                                <?php echo esc_html__('Prevents abuse of password reset functionality.', 'securewp-pro'); ?>
                                            </p>
                                            <?php
                                            // Display password reset attempts in the last hour
                                            global $wpdb;
                                            $table_name = $wpdb->prefix . 'securewp_pro_logs';
                                            $one_hour_ago = date('Y-m-d H:i:s', strtotime('-1 hour'));
                                            $attempt_count = $wpdb->get_var($wpdb->prepare(
                                                "SELECT COUNT(*) FROM $table_name WHERE event_type = 'password_reset_rate_limiting' AND event_action = 'request' AND created >= %s",
                                                $one_hour_ago
                                            ));
                                            ?>
                                            <div class="securewp-stats-info">
                                                <p><strong><?php echo esc_html__('Recent Activity:', 'securewp-pro'); ?></strong> 
                                                <?php printf(esc_html__('%d password reset attempts in the last hour', 'securewp-pro'), $attempt_count); ?></p>
                                            </div>
                                            <div class="swp-evidence" data-feature="securewp_pro_password_reset_rate_limiting"></div>
                                        </td>
                                    </tr>
                                    
                                    <tr>
                                        <th scope="row"><?php echo esc_html__('Hide Lost Password Link', 'securewp-pro'); ?></th>
                                        <td>
                                            <label>
                                                <input type="checkbox" name="securewp_pro_hide_lost_password_link" value="1" 
                                                    <?php checked(1, get_option('securewp_pro_hide_lost_password_link', false)); ?> />
                                                <?php echo esc_html__('Hide the "Lost your password?" link on the login screen', 'securewp-pro'); ?>
                                            </label>
                                            <p class="description">
                                                <?php echo esc_html__('Completely removes the password reset link from the WordPress login screen for enhanced security.', 'securewp-pro'); ?>
                                            </p>
                                        </td>
                                    </tr>
                                    
                                    <tr>
                                        <th scope="row"><?php echo esc_html__('General Rate Limiting', 'securewp-pro'); ?></th>
                                        <td>
                                            <label>
                                                <input type="checkbox" name="securewp_pro_general_rate_limiting" value="1" 
                                                    <?php checked(1, get_option('securewp_pro_general_rate_limiting', true)); ?> />
                                                <?php echo esc_html__('Rate limit contact and registration forms', 'securewp-pro'); ?>
                                            </label>
                                            <p class="description">
                                                <?php echo esc_html__('Protects against spam and abuse of contact forms and user registration.', 'securewp-pro'); ?>
                                            </p>
                                            <div class="swp-evidence" data-feature="securewp_pro_general_rate_limiting"></div>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th scope="row"><?php echo esc_html__('Contact Form Plugins', 'securewp-pro'); ?></th>
                                        <td>
                                            <fieldset>
                                                <label style="display:block; margin-bottom:6px;">
                                                    <input type="checkbox" name="securewp_pro_rate_limit_cf7" value="1" 
                                                           <?php checked(1, get_option('securewp_pro_rate_limit_cf7', true)); ?> />
                                                    <?php echo esc_html__('Enable rate limit for Contact Form 7', 'securewp-pro'); ?>
                                                </label>
                                                <label style="display:block; margin-bottom:6px;">
                                                    <input type="checkbox" name="securewp_pro_rate_limit_fluentforms" value="1" 
                                                           <?php checked(1, get_option('securewp_pro_rate_limit_fluentforms', true)); ?> />
                                                    <?php echo esc_html__('Enable rate limit for Fluent Forms', 'securewp-pro'); ?>
                                                </label>
                                                <label style="display:block;">
                                                    <input type="checkbox" name="securewp_pro_rate_limit_elementor" value="1" 
                                                           <?php checked(1, get_option('securewp_pro_rate_limit_elementor', true)); ?> />
                                                    <?php echo esc_html__('Enable rate limit for Elementor Pro Forms', 'securewp-pro'); ?>
                                                </label>
                                            </fieldset>
                                            <p class="description">
                                                <?php echo esc_html__('Choose which form plugins should have rate limiting enforced. Each plugin has its own independent bucket.', 'securewp-pro'); ?>
                                            </p>
                                            <div class="swp-evidence" data-feature="securewp_pro_rate_limit_cf7"></div>
                                            <div class="swp-evidence" data-feature="securewp_pro_rate_limit_fluentforms"></div>
                                            <div class="swp-evidence" data-feature="securewp_pro_rate_limit_elementor"></div>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th scope="row"><?php echo esc_html__('Elementor Pro Anti-Spam', 'securewp-pro'); ?></th>
                                        <td>
                                            <label>
                                                <input type="checkbox" name="securewp_pro_elementor_honeypot" value="1" 
                                                       <?php checked(1, get_option('securewp_pro_elementor_honeypot', true)); ?> />
                                                <?php echo esc_html__('Enable honeypot field detection for Elementor Pro Forms', 'securewp-pro'); ?>
                                            </label>
                                            <p class="description">
                                                <?php echo esc_html__('Automatically detects and blocks spam bots that fill honeypot fields (comments, phone_number, address, email_confirm, human_check). This provides additional protection against automated form spam.', 'securewp-pro'); ?>
                                            </p>
                                            <div class="swp-evidence" data-feature="securewp_pro_elementor_honeypot"></div>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>
                    
                    <?php 
                    // Create a button group for Save and Cancel - Security Features
                    echo '<p class="submit">';
                    submit_button(__('Save Settings', 'securewp-pro'), 'primary', 'submit', false);
                    echo ' <button type="button" class="button securewp-cancel-button" style="display: none; margin-left: 10px;">' . esc_html__('Cancel Changes', 'securewp-pro') . '</button>';
                    echo '</p>';
                    ?>
                </form>
                    
                <?php elseif ($settings_subtab === 'lockout'): ?>
                    <!-- Lockout Settings Sub-tab -->
                    <div class="settings-section">
                        <h3><?php echo esc_html__('IP Lockout Configuration', 'securewp-pro'); ?></h3>
                        <p class="description"><?php echo esc_html__('Configure how long IP addresses are locked out after failed attempts. The system uses a progressive lockout system that increases lockout time with each failure.', 'securewp-pro'); ?></p>
                        
                        <table class="form-table">
                            <tr>
                                <th scope="row"><?php echo esc_html__('Initial Lockout', 'securewp-pro'); ?></th>
                                <td>
                                    <?php 
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
                                    ?>
                                    <input type="number" name="securewp_pro_lockout_times[initial]" value="<?php echo esc_attr($lockout_times['initial']); ?>" min="0" class="small-text" />
                                    <span><?php echo esc_html__('seconds after first failure', 'securewp-pro'); ?></span>
                                    <p class="description"><?php echo esc_html__('Usually set to 0 for the first failure.', 'securewp-pro'); ?></p>
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row"><?php echo esc_html__('Second Lockout', 'securewp-pro'); ?></th>
                                <td>
                                    <input type="number" name="securewp_pro_lockout_times[second]" value="<?php echo esc_attr($lockout_times['second']); ?>" min="0" class="small-text" />
                                    <span><?php echo esc_html__('seconds after second failure', 'securewp-pro'); ?></span>
                                    <p class="description"><?php echo esc_html__('Recommended: 30 seconds.', 'securewp-pro'); ?></p>
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row"><?php echo esc_html__('Third Lockout', 'securewp-pro'); ?></th>
                                <td>
                                    <input type="number" name="securewp_pro_lockout_times[third]" value="<?php echo esc_attr($lockout_times['third']); ?>" min="0" class="small-text" />
                                    <span><?php echo esc_html__('seconds after third failure', 'securewp-pro'); ?></span>
                                    <p class="description"><?php echo esc_html__('Recommended: 60 seconds (1 minute).', 'securewp-pro'); ?></p>
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row"><?php echo esc_html__('Fourth Lockout', 'securewp-pro'); ?></th>
                                <td>
                                    <input type="number" name="securewp_pro_lockout_times[fourth]" value="<?php echo esc_attr($lockout_times['fourth']); ?>" min="0" class="small-text" />
                                    <span><?php echo esc_html__('seconds after fourth failure', 'securewp-pro'); ?></span>
                                    <p class="description"><?php echo esc_html__('Recommended: 300 seconds (5 minutes).', 'securewp-pro'); ?></p>
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row"><?php echo esc_html__('Fifth Lockout', 'securewp-pro'); ?></th>
                                <td>
                                    <input type="number" name="securewp_pro_lockout_times[fifth]" value="<?php echo esc_attr($lockout_times['fifth']); ?>" min="0" class="small-text" />
                                    <span><?php echo esc_html__('seconds after fifth failure', 'securewp-pro'); ?></span>
                                    <p class="description"><?php echo esc_html__('Recommended: 600 seconds (10 minutes).', 'securewp-pro'); ?></p>
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row"><?php echo esc_html__('Maximum Lockout', 'securewp-pro'); ?></th>
                                <td>
                                    <input type="number" name="securewp_pro_lockout_times[max]" value="<?php echo esc_attr($lockout_times['max']); ?>" min="0" class="small-text" />
                                    <span><?php echo esc_html__('seconds for maximum lockout', 'securewp-pro'); ?></span>
                                    <p class="description"><?php echo esc_html__('Recommended: 4800 seconds (80 minutes). After this threshold, lockouts become permanent.', 'securewp-pro'); ?></p>
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row"><?php echo esc_html__('Permanent Lockout', 'securewp-pro'); ?></th>
                                <td>
                                    <input type="number" name="securewp_pro_lockout_times[permanent]" value="<?php echo esc_attr($lockout_times['permanent']); ?>" min="0" class="small-text" />
                                    <span><?php echo esc_html__('seconds for permanent lockout', 'securewp-pro'); ?></span>
                                    <p class="description"><?php echo esc_html__('Recommended: 86400 seconds (24 hours). After this time, IPs are permanently blocked until manually unlocked.', 'securewp-pro'); ?></p>
                                </td>
                            </tr>
                        </table>
                        
                        <div class="notice notice-info">
                            <p><strong><?php echo esc_html__('Lockout System Explanation:', 'securewp-pro'); ?></strong></p>
                            <p><?php echo esc_html__('The system uses a progressive lockout approach: 0s → 30s → 1m → 5m → 10m → 80m → 24h → Permanent. After reaching the maximum threshold (80 minutes), further failures result in permanent lockouts that require manual admin intervention to unlock.', 'securewp-pro'); ?></p>
                        </div>
                    </div>
                    
                    <?php 
                    // Create a button group for Save and Cancel - Lockout Settings
                    echo '<p class="submit">';
                    submit_button(__('Save Settings', 'securewp-pro'), 'primary', 'submit', false);
                    echo ' <button type="button" class="button securewp-cancel-button" style="display: none; margin-left: 10px;">' . esc_html__('Cancel Changes', 'securewp-pro') . '</button>';
                    echo '</p>';
                    ?>
                </form>
                    
                <?php elseif ($settings_subtab === 'notifications'): ?>
                    <!-- Notifications Sub-tab -->
                    <div class="settings-section">
                        <h3><?php echo esc_html__('Email Notifications', 'securewp-pro'); ?></h3>
                        <p class="description"><?php echo esc_html__('Configure email alerts for security events and lockouts.', 'securewp-pro'); ?></p>
                        
                        <table class="form-table">
                            <tr>
                                <th scope="row"><?php echo esc_html__('Enable Admin Notifications', 'securewp-pro'); ?></th>
                                <td>
                                    <label>
                                        <input type="checkbox" name="securewp_pro_notify_admin" value="1" 
                                            <?php checked(1, get_option('securewp_pro_notify_admin', true)); ?> />
                                        <?php echo esc_html__('Send email notifications to admin for security events', 'securewp-pro'); ?>
                                    </label>
                                    <p class="description">
                                        <?php echo esc_html__('Admin will receive email alerts for blocked access attempts and lockouts.', 'securewp-pro'); ?>
                                    </p>
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row"><?php echo esc_html__('Notification Email', 'securewp-pro'); ?></th>
                                <td>
                                    <input type="email" name="securewp_pro_notify_email" 
                                           value="<?php echo esc_attr(get_option('securewp_pro_notify_email', get_option('admin_email'))); ?>" 
                                           class="regular-text" />
                                    <p class="description">
                                        <?php echo esc_html__('Email address to receive security notifications. Defaults to admin email.', 'securewp-pro'); ?>
                                    </p>
                                </td>
                            </tr>
                        </table>
                        
                        <h3><?php echo esc_html__('Notification Types', 'securewp-pro'); ?></h3>
                        <p class="description"><?php echo esc_html__('Select which types of security events trigger email notifications.', 'securewp-pro'); ?></p>
                        
                        <table class="form-table">
                            <tr>
                                <th scope="row"><?php echo esc_html__('Login Failures', 'securewp-pro'); ?></th>
                                <td>
                                    <label>
                                        <input type="checkbox" name="securewp_pro_notify_login_failures" value="1" 
                                            <?php checked(1, get_option('securewp_pro_notify_login_failures', true)); ?> />
                                        <?php echo esc_html__('Notify on failed login attempts', 'securewp-pro'); ?>
                                    </label>
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row"><?php echo esc_html__('IP Lockouts', 'securewp-pro'); ?></th>
                                <td>
                                    <label>
                                        <input type="checkbox" name="securewp_pro_notify_lockouts" value="1" 
                                            <?php checked(1, get_option('securewp_pro_notify_lockouts', true)); ?> />
                                        <?php echo esc_html__('Notify when IP addresses are locked out', 'securewp-pro'); ?>
                                    </label>
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row"><?php echo esc_html__('XML-RPC Attacks', 'securewp-pro'); ?></th>
                                <td>
                                    <label>
                                        <input type="checkbox" name="securewp_pro_notify_xmlrpc" value="1" 
                                            <?php checked(1, get_option('securewp_pro_notify_xmlrpc', true)); ?> />
                                        <?php echo esc_html__('Notify on XML-RPC attack attempts', 'securewp-pro'); ?>
                                    </label>
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row"><?php echo esc_html__('Cron Security', 'securewp-pro'); ?></th>
                                <td>
                                    <label>
                                        <input type="checkbox" name="securewp_pro_notify_cron" value="1" 
                                            <?php checked(1, get_option('securewp_pro_notify_cron', false)); ?> />
                                        <?php echo esc_html__('Notify on unauthorized cron access attempts', 'securewp-pro'); ?>
                                    </label>
                                </td>
                            </tr>
                        </table>
                    </div>
                    
                    <?php 
                    // Create a button group for Save and Cancel - Notifications
                    echo '<p class="submit">';
                    submit_button(__('Save Settings', 'securewp-pro'), 'primary', 'submit', false);
                    echo ' <button type="button" class="button securewp-cancel-button" style="display: none; margin-left: 10px;">' . esc_html__('Cancel Changes', 'securewp-pro') . '</button>';
                    echo '</p>';
                    ?>
                </form>
                    
                <?php elseif ($settings_subtab === 'advanced'): ?>
                    <!-- Advanced Settings Sub-tab -->
                    <div class="settings-section">
                        <h3><?php echo esc_html__('Advanced Configuration', 'securewp-pro'); ?></h3>
                        <p class="description"><?php echo esc_html__('Advanced settings for power users and specific server configurations.', 'securewp-pro'); ?></p>
                        
                        <table class="form-table">
                            <tr>
                                <th scope="row"><?php echo esc_html__('Authorized Cron IPs', 'securewp-pro'); ?></th>
                                <td>
                                    <textarea name="securewp_pro_authorized_cron_ips" rows="3" cols="50" class="large-text"><?php echo esc_textarea(get_option('securewp_pro_authorized_cron_ips', '')); ?></textarea>
                                    <p class="description">
                                        <?php echo esc_html__('Enter IP addresses or CIDR ranges (one per line), e.g., 203.0.113.10 or 203.0.113.0/24. Leave empty to only allow server-local requests or header/secret auth.', 'securewp-pro'); ?>
                                    </p>
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row"><?php echo esc_html__('Cron Secret Key', 'securewp-pro'); ?></th>
                                <td>
                                    <input type="password" name="securewp_pro_cron_secret_key" 
                                           value="<?php echo esc_attr(get_option('securewp_pro_cron_secret_key', wp_generate_password(32, false))); ?>" 
                                           class="regular-text" readonly />
                                    <button type="button" class="button securewp-generate-cron-key" style="margin-left: 8px;"
                                            data-nonce="<?php echo esc_attr(wp_create_nonce('securewp_pro_admin_action')); ?>">
                                        <?php echo esc_html__('Generate New Key', 'securewp-pro'); ?>
                                    </button>
                                    <button type="button" class="button securewp-toggle-cron-key" aria-label="<?php echo esc_attr__('Show or hide key', 'securewp-pro'); ?>">
                                        <?php echo esc_html__('Show', 'securewp-pro'); ?>
                                    </button>
                                    <button type="button" class="button securewp-copy-cron-key" aria-label="<?php echo esc_attr__('Copy key to clipboard', 'securewp-pro'); ?>">
                                        <?php echo esc_html__('Copy', 'securewp-pro'); ?>
                                    </button>
                                    <span class="description" style="margin-left:8px; display:inline-block;" id="securewp-cron-key-status"></span>
                                    <p class="description">
                                        <?php echo esc_html__('Secret key for cron authentication. Header: X-SecureWP-Cron: YOUR_KEY. Alternate URL (if ALTERNATE_WP_CRON is enabled): yoursite.com/wp-cron.php?secret=YOUR_KEY', 'securewp-pro'); ?>
                                    </p>
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row"><?php echo esc_html__('Log Retention Period', 'securewp-pro'); ?></th>
                                <td>
                                    <select name="securewp_pro_log_retention">
                                        <option value="7" <?php selected(get_option('securewp_pro_log_retention', 30), 7); ?>><?php echo esc_html__('7 days', 'securewp-pro'); ?></option>
                                        <option value="30" <?php selected(get_option('securewp_pro_log_retention', 30), 30); ?>><?php echo esc_html__('30 days', 'securewp-pro'); ?></option>
                                        <option value="90" <?php selected(get_option('securewp_pro_log_retention', 30), 90); ?>><?php echo esc_html__('90 days', 'securewp-pro'); ?></option>
                                        <option value="365" <?php selected(get_option('securewp_pro_log_retention', 30), 365); ?>><?php echo esc_html__('1 year', 'securewp-pro'); ?></option>
                                        <option value="0" <?php selected(get_option('securewp_pro_log_retention', 30), 0); ?>><?php echo esc_html__('Never delete', 'securewp-pro'); ?></option>
                                    </select>
                                    <p class="description">
                                        <?php echo esc_html__('How long to keep security logs. Older logs will be automatically deleted.', 'securewp-pro'); ?>
                                    </p>
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row"><?php echo esc_html__('Debug Mode', 'securewp-pro'); ?></th>
                                <td>
                                    <label>
                                        <input type="checkbox" name="securewp_pro_debug_mode" value="1" 
                                            <?php checked(1, get_option('securewp_pro_debug_mode', false)); ?> />
                                        <?php echo esc_html__('Enable debug logging', 'securewp-pro'); ?>
                                    </label>
                                    <p class="description">
                                        <?php echo esc_html__('Log additional debug information. Only enable for troubleshooting.', 'securewp-pro'); ?>
                                    </p>
                                </td>
                            </tr>
                        </table>
                        
                        <h3><?php echo esc_html__('Database Maintenance', 'securewp-pro'); ?></h3>
                        <p class="description"><?php echo esc_html__('Tools for maintaining the plugin database tables.', 'securewp-pro'); ?></p>
                        
                        <table class="form-table">
                            <tr>
                                <th scope="row"><?php echo esc_html__('Clean Old Logs', 'securewp-pro'); ?></th>
                                <td>
                                    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                                        <input type="hidden" name="action" value="securewp_pro_clean_old_logs" />
                                        <?php wp_nonce_field('securewp_pro_admin_action'); ?>
                                        <button type="submit" class="button"><?php echo esc_html__('Clean Now', 'securewp-pro'); ?></button>
                                    </form>
                                    <p class="description">
                                        <?php echo esc_html__('Immediately clean logs older than the retention period.', 'securewp-pro'); ?>
                                    </p>
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row"><?php echo esc_html__('Reset All Settings', 'securewp-pro'); ?></th>
                                <td>
                                    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" onsubmit="return confirm('<?php echo esc_js(__('Are you sure you want to reset all settings to defaults? This will not affect logs or lockouts.', 'securewp-pro')); ?>');">
                                        <input type="hidden" name="action" value="securewp_pro_reset_settings" />
                                        <?php wp_nonce_field('securewp_pro_admin_action'); ?>
                                        <button type="submit" class="button button-secondary"><?php echo esc_html__('Reset to Defaults', 'securewp-pro'); ?></button>
                                    </form>
                                    <p class="description">
                                        <?php echo esc_html__('Reset all plugin settings to their default values. This will not delete logs or lockouts.', 'securewp-pro'); ?>
                                    </p>
                                </td>
                            </tr>
                        </table>
                    </div>
                    
                    <?php 
                    // Create a button group for Save and Cancel - Advanced Settings
                    echo '<p class="submit">';
                    submit_button(__('Save Settings', 'securewp-pro'), 'primary', 'submit', false);
                    echo ' <button type="button" class="button securewp-cancel-button" style="display: none; margin-left: 10px;">' . esc_html__('Cancel Changes', 'securewp-pro') . '</button>';
                    echo '</p>';
                    ?>
                <?php endif; ?>
            </form>
        </div>
    </div>
</div>


<style>
.settings-tabs-wrapper {
    display: flex;
    gap: 20px;
    margin-top: 20px;
}

.settings-nav-tabs {
    width: 250px;
    flex-shrink: 0;
}

.settings-nav-tab {
    display: block;
    padding: 15px;
    margin-bottom: 5px;
    background: #f8f9fa;
    border: 1px solid #e1e5e9;
    border-radius: 4px;
    text-decoration: none;
    color: #555;
    transition: all 0.2s ease;
}

.settings-nav-tab:hover {
    background: #e9ecef;
    color: #333;
    text-decoration: none;
}

.settings-nav-tab.active {
    background: #0073aa;
    color: #fff;
    border-color: #0073aa;
}

.settings-nav-tab .dashicons {
    display: block;
    font-size: 24px;
    margin-bottom: 8px;
}

.tab-label {
    display: block;
    font-weight: bold;
    margin-bottom: 4px;
}

.tab-desc {
    display: block;
    font-size: 0.9em;
    opacity: 0.8;
}

.settings-content {
    flex: 1;
    background: #fff;
    border: 1px solid #e1e5e9;
    border-radius: 4px;
    padding: 20px;
}

/* Top Save Button Area Styling */
.securewp-top-save-area {
    background: #f8f9fa;
    border: 1px solid #e1e5e9;
    border-radius: 6px;
    padding: 15px 20px;
    margin-bottom: 25px;
    border-left: 4px solid #0073aa;
}

.securewp-save-wrapper {
    display: flex;
    align-items: center;
    gap: 15px;
}

.securewp-save-wrapper .button-primary {
    margin: 0;
    padding: 8px 20px;
    font-weight: 600;
}

.securewp-save-description {
    color: #666;
    font-style: italic;
    font-size: 14px;
}

/* Horizontal Tabs Styling for Security Features */
.securewp-horizontal-tabs {
    display: flex;
    border-bottom: 1px solid #e1e5e9;
    margin-bottom: 25px;
    background: #f8f9fa;
    border-radius: 4px 4px 0 0;
    padding: 0;
}

.securewp-tab-button {
    background: transparent;
    border: none;
    padding: 15px 20px;
    cursor: pointer;
    font-weight: 600;
    color: #555;
    border-bottom: 3px solid transparent;
    transition: all 0.2s ease;
    position: relative;
}

.securewp-tab-button:hover {
    background: #e9ecef;
    color: #333;
}

.securewp-tab-button.active {
    color: #0073aa;
    border-bottom: 3px solid #0073aa;
    background: #fff;
}

.securewp-tab-content {
    position: relative;
}

.securewp-tab-pane {
    display: none;
}

.securewp-tab-pane.active {
    display: block;
    animation: fadeIn 0.3s ease;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

@media (max-width: 600px) {
    .securewp-save-wrapper {
        flex-direction: column;
        align-items: flex-start;
        gap: 8px;
    }
    
    .securewp-save-description {
        font-size: 13px;
    }
    
    .securewp-horizontal-tabs {
        flex-direction: column;
    }
    
    .securewp-tab-button {
        text-align: left;
        border-bottom: 1px solid #e1e5e9;
        border-left: 3px solid transparent;
    }
    
    .securewp-tab-button.active {
        border-bottom: 1px solid #e1e5e9;
        border-left: 3px solid #0073aa;
    }
}

.settings-section h3 {
    margin-top: 0;
    padding-bottom: 10px;
    border-bottom: 1px solid #e1e5e9;
}

.settings-section .description {
    margin-bottom: 20px;
    font-style: italic;
    color: #666;
}

.form-table th {
    width: 200px;
}

.small-text {
    width: 80px;
}

@media (max-width: 768px) {
    .settings-tabs-wrapper {
        flex-direction: column;
    }
    
    .settings-nav-tabs {
        width: 100%;
    }
    
    .settings-nav-tab {
        display: inline-block;
        width: calc(50% - 5px);
        margin-right: 10px;
        text-align: center;
        padding: 10px;
    }
    
    .settings-nav-tab .dashicons {
        font-size: 20px;
        margin-bottom: 5px;
    }
    
    .tab-desc {
        display: none;
    }
}
</style>

<script>
// No inline JS actions needed; secure admin-post handlers are used instead.
</script>

