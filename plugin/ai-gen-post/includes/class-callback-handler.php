<?php
class N8N_Callback_Handler {
    public function __construct() {
        add_action('rest_api_init', array($this, 'register_callback_endpoint'));
    }

    public function register_callback_endpoint() {
        register_rest_route('n8n-webhook/v1', '/callback', array(
            'methods' => 'POST',
            'callback' => array($this, 'handle_callback'),
            'permission_callback' => array($this, 'check_permission')
        ));
    }

    public function check_permission($request) {
        // Get the Authorization header
        $auth_header = $request->get_header('Authorization');
        
        if (!$auth_header || strpos($auth_header, 'Basic ') !== 0) {
            return new WP_Error(
                'rest_forbidden',
                'Authentication required',
                array('status' => 401)
            );
        }

        // Extract username and password from Basic Auth
        $auth_data = base64_decode(substr($auth_header, 6));
        list($username, $password) = explode(':', $auth_data);

        // Verify using WordPress Application Passwords
        $user = wp_authenticate_application_password(null, $username, $password);
        
        if (is_wp_error($user)) {
            return new WP_Error(
                'rest_forbidden',
                'Invalid credentials',
                array('status' => 401)
            );
        }

        return true;
    }

    public function handle_callback($request) {
        // Get data from request body
        $params = $request->get_json_params();
        
        // Validate required fields
        if (!isset($params['status']) || !isset($params['message'])) {
            return new \WP_Error(
                'missing_fields',
                'Missing required fields',
                ['status' => 400]
            );
        }

        // Store callback data in transient
        $callback_data = [
            'status' => $params['status'],
            'message' => $params['message']
        ];
        
        set_transient('n8n_callback_data', $callback_data, 5 * MINUTE_IN_SECONDS);

        return rest_ensure_response([
            'success' => true,
            'message' => 'Callback received successfully'
        ]);
    }
}

// Initialize the callback handler
new N8N_Callback_Handler(); 