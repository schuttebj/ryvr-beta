<?php
declare(strict_types=1);

namespace Ryvr\Connectors;

/**
 * Connector manager for Ryvr.
 *
 * @since 1.0.0
 */
class Manager
{
    /**
     * Registered connectors.
     *
     * @var array
     */
    private array $connectors = [];
    
    /**
     * Constructor.
     *
     * @since 1.0.0
     */
    public function __construct()
    {
        // Initialize connectors
        $this->init_connectors();
    }
    
    /**
     * Initialize built-in connectors.
     *
     * @return void
     *
     * @since 1.0.0
     */
    private function init_connectors(): void
    {
        // Load base connector classes first
        $this->load_base_classes();
        
        // Load built-in connectors
        $this->load_connector('OpenAI', 'OpenAIConnector');
        $this->load_connector('DataForSEO', 'DataForSEOConnector');
    }
    
    /**
     * Load base connector classes.
     *
     * @return void
     *
     * @since 1.0.0
     */
    private function load_base_classes(): void
    {
        // Load interface first
        $interface_file = RYVR_PLUGIN_DIR . 'src/Connectors/RyvrConnectorInterface.php';
        if (file_exists($interface_file)) {
            require_once $interface_file;
        }
        
        // Load abstract connector
        $abstract_file = RYVR_PLUGIN_DIR . 'src/Connectors/AbstractConnector.php';
        if (file_exists($abstract_file)) {
            require_once $abstract_file;
        }
    }
    
    /**
     * Load and register a connector.
     *
     * @param string $directory Connector directory name.
     * @param string $class_name Connector class name.
     *
     * @return void
     *
     * @since 1.0.0
     */
    private function load_connector(string $directory, string $class_name): void
    {
        $file_path = RYVR_PLUGIN_DIR . "src/Connectors/{$directory}/{$class_name}.php";
        
        if (file_exists($file_path)) {
            require_once $file_path;
            
            $full_class_name = "\\Ryvr\\Connectors\\{$directory}\\{$class_name}";
            
            if (class_exists($full_class_name)) {
                $this->register_connector(new $full_class_name());
            }
        }
    }
    
    /**
     * Register connectors.
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function register_connectors(): void
    {
        // Register AJAX handlers for connector actions
        add_action('wp_ajax_ryvr_connector_validate_auth', [$this, 'ajax_validate_auth']);
        add_action('wp_ajax_ryvr_connector_save_auth', [$this, 'ajax_save_auth']);
        add_action('wp_ajax_ryvr_connector_delete_auth', [$this, 'ajax_delete_auth']);
        add_action('wp_ajax_ryvr_connector_get_actions', [$this, 'ajax_get_actions']);
        add_action('wp_ajax_ryvr_connector_get_auth_fields', [$this, 'ajax_get_auth_fields']);
        
        // Allow third-party plugins to register their connectors
        do_action('ryvr_register_connectors', $this);
    }
    
    /**
     * Register a connector.
     *
     * @param RyvrConnectorInterface $connector Connector instance.
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function register_connector(RyvrConnectorInterface $connector): void
    {
        $this->connectors[$connector->get_id()] = $connector;
    }
    
    /**
     * Get all registered connectors.
     *
     * @return array List of registered connectors.
     *
     * @since 1.0.0
     */
    public function get_connectors(): array
    {
        return $this->connectors;
    }
    
    /**
     * Get a specific connector by ID.
     *
     * @param string $connector_id Connector ID.
     *
     * @return RyvrConnectorInterface|null Connector instance or null if not found.
     *
     * @since 1.0.0
     */
    public function get_connector(string $connector_id): ?RyvrConnectorInterface
    {
        return $this->connectors[$connector_id] ?? null;
    }
    
    /**
     * AJAX handler for validating authentication.
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function ajax_validate_auth(): void
    {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'ryvr-admin-nonce')) {
            wp_send_json_error(['message' => __('Security check failed.', 'ryvr')]);
        }
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('You do not have permission to perform this action.', 'ryvr')]);
        }
        
        // Get connector and credentials
        $connector_id = sanitize_text_field($_POST['connector_id'] ?? '');
        $credentials = isset($_POST['credentials']) ? json_decode(stripslashes($_POST['credentials']), true) : [];
        
        if (empty($connector_id) || !is_array($credentials)) {
            wp_send_json_error(['message' => __('Invalid request parameters.', 'ryvr')]);
        }
        
        $connector = $this->get_connector($connector_id);
        
        if (!$connector) {
            wp_send_json_error(['message' => __('Connector not found.', 'ryvr')]);
        }
        
        // Validate credentials
        $is_valid = $connector->validate_auth($credentials);
        
        if ($is_valid) {
            wp_send_json_success(['message' => __('Authentication successful.', 'ryvr')]);
        } else {
            wp_send_json_error(['message' => __('Authentication failed. Please check your credentials.', 'ryvr')]);
        }
    }
    
    /**
     * AJAX handler for saving authentication.
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function ajax_save_auth(): void
    {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'ryvr-admin-nonce')) {
            wp_send_json_error(['message' => __('Security check failed.', 'ryvr')]);
        }
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('You do not have permission to perform this action.', 'ryvr')]);
        }
        
        // Get connector, credentials, and user ID
        $connector_id = sanitize_text_field($_POST['connector_id'] ?? '');
        $credentials = isset($_POST['credentials']) ? json_decode(stripslashes($_POST['credentials']), true) : [];
        $user_id = get_current_user_id();
        
        if (empty($connector_id) || !is_array($credentials) || !$user_id) {
            wp_send_json_error(['message' => __('Invalid request parameters.', 'ryvr')]);
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'ryvr_api_keys';
        
        // Check if credentials already exist
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$table_name} WHERE connector_slug = %s AND user_id = %d",
            $connector_id,
            $user_id
        ));
        
        // Encrypt sensitive data
        require_once RYVR_PLUGIN_DIR . 'src/Security/Encryption.php';
        $encrypted_data = [];
        
        foreach ($credentials as $key => $value) {
            if (is_string($value)) {
                $encrypted_data[$key] = \Ryvr\Security\Encryption::encrypt($value);
            } else {
                $encrypted_data[$key] = $value;
            }
        }
        
        $data = [
            'connector_slug' => $connector_id,
            'user_id' => $user_id,
            'auth_meta' => json_encode($encrypted_data),
            'is_shared' => false,
        ];
        
        if ($existing) {
            // Update existing
            $result = $wpdb->update(
                $table_name,
                $data,
                [
                    'id' => $existing,
                ]
            );
        } else {
            // Insert new
            $result = $wpdb->insert($table_name, $data);
        }
        
        if ($result !== false) {
            wp_send_json_success(['message' => __('Authentication saved successfully.', 'ryvr')]);
        } else {
            wp_send_json_error(['message' => __('Failed to save authentication.', 'ryvr')]);
        }
    }
    
    /**
     * AJAX handler for deleting authentication.
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function ajax_delete_auth(): void
    {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'ryvr-admin-nonce')) {
            wp_send_json_error(['message' => __('Security check failed.', 'ryvr')]);
        }
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('You do not have permission to perform this action.', 'ryvr')]);
        }
        
        // Get connector ID and user ID
        $connector_id = sanitize_text_field($_POST['connector_id'] ?? '');
        $user_id = get_current_user_id();
        
        if (empty($connector_id) || !$user_id) {
            wp_send_json_error(['message' => __('Invalid request parameters.', 'ryvr')]);
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'ryvr_api_keys';
        
        $result = $wpdb->delete(
            $table_name,
            [
                'connector_slug' => $connector_id,
                'user_id' => $user_id,
            ]
        );
        
        if ($result !== false) {
            wp_send_json_success(['message' => __('Authentication deleted successfully.', 'ryvr')]);
        } else {
            wp_send_json_error(['message' => __('Failed to delete authentication.', 'ryvr')]);
        }
    }
    
    /**
     * AJAX handler for getting connector actions.
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function ajax_get_actions(): void
    {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'ryvr-admin-nonce')) {
            wp_send_json_error(['message' => __('Security check failed.', 'ryvr')]);
        }
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('You do not have permission to perform this action.', 'ryvr')]);
        }
        
        // Get connector ID
        $connector_id = sanitize_text_field($_POST['connector_id'] ?? '');
        
        if (empty($connector_id)) {
            wp_send_json_error(['message' => __('Invalid request parameters.', 'ryvr')]);
        }
        
        $connector = $this->get_connector($connector_id);
        
        if (!$connector) {
            wp_send_json_error(['message' => __('Connector not found.', 'ryvr')]);
        }
        
        // Get actions and triggers
        $actions = $connector->get_actions();
        $triggers = $connector->get_triggers();
        
        wp_send_json_success([
            'actions' => $actions,
            'triggers' => $triggers,
        ]);
    }
    
    /**
     * AJAX handler for getting connector auth fields.
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function ajax_get_auth_fields(): void
    {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'ryvr-admin-nonce')) {
            wp_send_json_error(['message' => __('Security check failed.', 'ryvr')]);
        }
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('You do not have permission to perform this action.', 'ryvr')]);
        }
        
        // Get connector ID
        $connector_id = sanitize_text_field($_POST['connector_id'] ?? '');
        
        if (empty($connector_id)) {
            wp_send_json_error(['message' => __('Invalid request parameters.', 'ryvr')]);
        }
        
        $connector = $this->get_connector($connector_id);
        
        if (!$connector) {
            wp_send_json_error(['message' => __('Connector not found.', 'ryvr')]);
        }
        
        // Get auth fields
        $fields = $connector->get_auth_fields();
        
        // Get saved credentials
        global $wpdb;
        $table_name = $wpdb->prefix . 'ryvr_api_keys';
        $user_id = get_current_user_id();
        
        $result = $wpdb->get_var($wpdb->prepare(
            "SELECT auth_meta FROM {$table_name} WHERE connector_slug = %s AND user_id = %d",
            $connector_id,
            $user_id
        ));
        
        $saved_credentials = [];
        
        if ($result) {
            $encrypted_credentials = json_decode($result, true);
            
            if (is_array($encrypted_credentials)) {
                require_once RYVR_PLUGIN_DIR . 'src/Security/Encryption.php';
                
                foreach ($encrypted_credentials as $key => $value) {
                    if (is_string($value)) {
                        $decrypted = \Ryvr\Security\Encryption::decrypt($value);
                        
                        if ($decrypted !== false) {
                            // Don't send actual values for password fields
                            if (isset($fields[$key]) && $fields[$key]['type'] === 'password') {
                                $saved_credentials[$key] = '';
                            } else {
                                $saved_credentials[$key] = $decrypted;
                            }
                        } else {
                            $saved_credentials[$key] = $value;
                        }
                    } else {
                        $saved_credentials[$key] = $value;
                    }
                }
            }
        }
        
        wp_send_json_success([
            'fields' => $fields,
            'saved_credentials' => $saved_credentials,
        ]);
    }
} 