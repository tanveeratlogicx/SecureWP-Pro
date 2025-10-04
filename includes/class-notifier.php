<?php
class SecureWP_Pro_Notifier {
    
    private $logger;
    
    public function __construct($logger) {
        $this->logger = $logger;
    }
    
    public function notify_admin_lockout($ip, $event_type, $failure_count) {
        $to = get_option('admin_email');
        $subject = sprintf(__('SecureWP Pro: Lockout triggered for IP %s', 'securewp-pro'), $ip);
        $message = sprintf(
            __('A lockout has been triggered for IP address %s due to %s events. Failure count: %d', 'securewp-pro'),
            $ip, $event_type, $failure_count
        );
        
        wp_mail($to, $subject, $message);
        
        $this->logger->log_event('notification', 'lockout_triggered', 
            'Admin notified about lockout for IP: ' . $ip . ', event: ' . $event_type);
    }
    
    public function notify_admin_permanent_lockout($ip, $event_type) {
        $to = get_option('admin_email');
        $subject = sprintf(__('SecureWP Pro: Permanent lockout triggered for IP %s', 'securewp-pro'), $ip);
        $message = sprintf(
            __('A permanent lockout has been triggered for IP address %s due to repeated %s events.', 'securewp-pro'),
            $ip, $event_type
        );
        
        wp_mail($to, $subject, $message);
        
        $this->logger->log_event('notification', 'permanent_lockout_triggered', 
            'Admin notified about permanent lockout for IP: ' . $ip . ', event: ' . $event_type);
    }
}