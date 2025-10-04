<?php
class SecureWP_Pro_General_Rate_Limiting {
    
    private $logger;
    private $lockout_manager;
    
    public function __construct($logger, $lockout_manager) {
        $this->logger = $logger;
        $this->lockout_manager = $lockout_manager;
        $this->init_hooks();
    }
    
    private function init_hooks() {
        // Contact form 7 integration
        add_action('wpcf7_before_send_mail', array($this, 'check_contact_form_rate_limit'));
        
        // Fluent Forms integration - validate before submission and block if rate limited
        // This filter allows us to add a global validation error that prevents submission.
        // Works even if Fluent Forms is not active; the filter simply won't run.
        add_filter('fluentform/validation_errors', array($this, 'check_fluentform_rate_limit'), 10, 3);
        
        // Elementor Pro Forms integration - validate before submission
        add_action('elementor_pro/forms/validation', array($this, 'check_elementor_pro_rate_limit'), 10, 2);
        
        // Registration form rate limiting
        add_action('register_post', array($this, 'check_registration_rate_limit'), 10, 3);
    }
    
    public function check_contact_form_rate_limit($contact_form) {
        $ip = $this->get_client_ip();
        
        // Respect settings: master toggle and per-plugin toggle for CF7
        if (!get_option('securewp_pro_general_rate_limiting', true) || !get_option('securewp_pro_rate_limit_cf7', true)) {
            return; // Do nothing if disabled
        }
        
        if ($this->lockout_manager->is_locked_out($ip, 'contact_form_cf7')) {
            $this->logger->log_event('general_rate_limiting', 'blocked', 
                'CF7 submission blocked due to lockout for IP: ' . $ip, $ip);
            
            wp_die(__('Too many form submissions. Please try again later.'), 403);
        }
        
        $this->lockout_manager->check_and_lock($ip, 'contact_form_cf7');
    }
    
    /**
     * Fluent Forms rate limiting: runs during validation and injects a global error when locked out.
     * 
     * @param array $errors    Existing validation errors
     * @param array $formData  Submitted form data
     * @param object $form     Form model/object
     * @return array           Validation errors (possibly with a global error added)
     */
    public function check_fluentform_rate_limit($errors, $formData, $form) {
        $ip = $this->get_client_ip();
        
        // Respect settings: master toggle and per-plugin toggle for Fluent Forms
        if (!get_option('securewp_pro_general_rate_limiting', true) || !get_option('securewp_pro_rate_limit_fluentforms', true)) {
            return $errors; // Do nothing if disabled
        }
        
        if ($this->lockout_manager->is_locked_out($ip, 'contact_form_fluentforms')) {
            $this->logger->log_event('general_rate_limiting', 'blocked',
                'Fluent Forms submission blocked due to lockout for IP: ' . $ip, $ip);
            // Add a global error to prevent submission
            if (!isset($errors['global']) || !is_array($errors['global'])) {
                $errors['global'] = array();
            }
            $errors['global'][] = __('Too many form submissions. Please try again later.');
            return $errors;
        }
        
        // Count this attempt towards the Fluent Forms bucket
        $this->lockout_manager->check_and_lock($ip, 'contact_form_fluentforms');
        
        return $errors;
    }
    
    /**
     * Elementor Pro Forms rate limiting: runs during validation and adds error when locked out.
     * Also includes honeypot field detection for additional spam protection.
     * 
     * @param object $record       The form record object
     * @param object $ajax_handler The Ajax handler object
     */
    public function check_elementor_pro_rate_limit($record, $ajax_handler) {
        $ip = $this->get_client_ip();
        
        // Respect settings: master toggle and per-plugin toggle for Elementor Pro
        if (!get_option('securewp_pro_general_rate_limiting', true) || !get_option('securewp_pro_rate_limit_elementor', true)) {
            return; // Do nothing if disabled
        }
        
        // Check for honeypot field (common spam bot detection) - only if enabled
        if (get_option('securewp_pro_elementor_honeypot', true)) {
            $raw_fields = $record->get('fields');
            if (!empty($raw_fields)) {
                foreach ($raw_fields as $field_id => $field_data) {
                    // Look for common honeypot field names
                    if (in_array($field_id, array('comments', 'phone_number', 'address', 'email_confirm', 'human_check')) 
                        && !empty($field_data['value'])) {
                        $this->logger->log_event('general_rate_limiting', 'blocked',
                            'Elementor Pro form submission blocked due to honeypot field filled for IP: ' . $ip, $ip);
                        
                        // Block silently for bots - don't reveal the honeypot
                        $ajax_handler->add_error('general', __('Form submission failed. Please try again.', 'securewp-pro'));
                        return;
                    }
                }
            }
        }
        
        // Rate limiting check
        if ($this->lockout_manager->is_locked_out($ip, 'contact_form_elementor')) {
            $this->logger->log_event('general_rate_limiting', 'blocked',
                'Elementor Pro form submission blocked due to lockout for IP: ' . $ip, $ip);
            
            // Add validation error to prevent form submission
            $ajax_handler->add_error('rate_limit', __('Too many form submissions. Please try again later.', 'securewp-pro'));
            return;
        }
        
        // Count this attempt towards the Elementor Pro bucket
        $this->lockout_manager->check_and_lock($ip, 'contact_form_elementor');
    }
    
    public function check_registration_rate_limit($sanitized_user_login, $user_email, $errors) {
        $ip = $this->get_client_ip();
        
        if ($this->lockout_manager->is_locked_out($ip, 'registration')) {
            $this->logger->log_event('general_rate_limiting', 'blocked', 
                'Registration attempt blocked due to lockout for IP: ' . $ip, $ip);
            
            $errors->add('rate_limit_exceeded', __('Too many registration attempts. Please try again later.'));
        }
        
        $this->lockout_manager->check_and_lock($ip, 'registration');
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