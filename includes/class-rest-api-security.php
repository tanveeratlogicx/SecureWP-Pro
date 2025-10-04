<?php
class SecureWP_Pro_REST_API_Security {
    
    private $logger;
    
    public function __construct($logger) {
        $this->logger = $logger;
        $this->init_hooks();
    }
    
    private function init_hooks() {
        add_filter('rest_endpoints', array($this, 'protect_users_endpoint'));
        add_filter('rest_prepare_user', array($this, 'filter_user_data'), 10, 3);
    }
    
    public function protect_users_endpoint($endpoints) {
        if (get_option('securewp_pro_rest_api_security', true)) {
            if (isset($endpoints['/wp/v2/users'])) {
                // Modify users endpoint to require higher permissions
                foreach ($endpoints['/wp/v2/users'] as $key => $handler) {
                    if (isset($handler['permission_callback'])) {
                        $endpoints['/wp/v2/users'][$key]['permission_callback'] = 
                            array($this, 'users_permissions_check');
                    }
                }
            }
        }
        return $endpoints;
    }
    
    public function users_permissions_check($request) {
        // Only allow administrators to list users
        return current_user_can('manage_options');
    }
    
    public function filter_user_data($response, $user, $request) {
        if (is_wp_error($response)) {
            return $response;
        }
        
        // Remove sensitive data for non-administrators
        if (!current_user_can('manage_options')) {
            $data = $response->get_data();
            unset($data['email']);
            unset($data['url']);
            unset($data['capabilities']);
            unset($data['extra_capabilities']);
            $response->set_data($data);
        }
        
        return $response;
    }
}