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
        // Debug logging
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Ryvr: ajax_validate_auth called');
            error_log('Ryvr: POST data: ' . print_r($_POST, true));
        }
        
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'ryvr-admin-nonce')) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Ryvr: Nonce verification failed in validate_auth');
            }
            wp_send_json_error(['message' => __('Security check failed.', 'ryvr')]);
        }
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Ryvr: Permission check failed in validate_auth');
            }
            wp_send_json_error(['message' => __('You do not have permission to perform this action.', 'ryvr')]);
        }
        
        // Get connector and credentials
        $connector_id = sanitize_text_field($_POST['connector_id'] ?? '');
        $credentials = isset($_POST['credentials']) ? json_decode(stripslashes($_POST['credentials']), true) : [];
        $user_id = get_current_user_id();
        
        if (empty($connector_id) || !is_array($credentials) || !$user_id) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Ryvr: Invalid request parameters in validate_auth');
            }
            wp_send_json_error(['message' => __('Invalid request parameters.', 'ryvr')]);
        }
        
        // Handle [USE_SAVED] markers by replacing with actual saved credentials
        $has_saved_markers = false;
        foreach ($credentials as $key => $value) {
            if ($value === '[USE_SAVED]') {
                $has_saved_markers = true;
                break;
            }
        }
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('');
            error_log('=== Manager Credential Processing Debug ===');
            error_log('Ryvr: Processing credentials for connector: ' . $connector_id);
            error_log('Ryvr: Has [USE_SAVED] markers: ' . ($has_saved_markers ? 'YES' : 'NO'));
            error_log('Ryvr: Input credentials keys: ' . print_r(array_keys($credentials), true));
            foreach ($credentials as $key => $value) {
                if (strpos($key, 'password') !== false) {
                    error_log('Ryvr: Input credential[' . $key . '] = ' . ($value === '[USE_SAVED]' ? '[USE_SAVED]' : '[' . strlen($value) . ' chars]'));
                } else {
                    error_log('Ryvr: Input credential[' . $key . '] = ' . $value);
                }
            }
        }
        
        if ($has_saved_markers) {
            global $wpdb;
            $table_name = $wpdb->prefix . 'ryvr_api_keys';
            
            $result = $wpdb->get_var($wpdb->prepare(
                "SELECT auth_meta FROM {$table_name} WHERE connector_slug = %s AND user_id = %d",
                $connector_id,
                $user_id
            ));
            
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Ryvr: Database query result: ' . ($result ? 'FOUND' : 'NOT FOUND'));
                if ($result) {
                    error_log('Ryvr: Raw saved data length: ' . strlen($result));
                }
            }
            
            if ($result) {
                $saved_encrypted = json_decode($result, true);
                if (is_array($saved_encrypted)) {
                    require_once RYVR_PLUGIN_DIR . 'src/Security/Encryption.php';
                    
                    if (defined('WP_DEBUG') && WP_DEBUG) {
                        error_log('Ryvr: Saved encrypted keys: ' . print_r(array_keys($saved_encrypted), true));
                    }
                    
                    foreach ($credentials as $key => $value) {
                        if ($value === '[USE_SAVED]' && isset($saved_encrypted[$key])) {
                            $decrypted = \Ryvr\Security\Encryption::decrypt($saved_encrypted[$key]);
                            if ($decrypted !== false) {
                                $credentials[$key] = $decrypted;
                                if (defined('WP_DEBUG') && WP_DEBUG) {
                                    if (strpos($key, 'password') !== false) {
                                        error_log('Ryvr: Replaced [USE_SAVED] for ' . $key . ' with [' . strlen($decrypted) . ' chars]');
                                    } else {
                                        error_log('Ryvr: Replaced [USE_SAVED] for ' . $key . ' with: ' . $decrypted);
                                    }
                                }
                            } else {
                                if (defined('WP_DEBUG') && WP_DEBUG) {
                                    error_log('Ryvr: Failed to decrypt saved value for: ' . $key);
                                }
                            }
                        }
                    }
                } else {
                    if (defined('WP_DEBUG') && WP_DEBUG) {
                        error_log('Ryvr: Failed to decode saved credentials JSON');
                    }
                }
            }
        }
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('');
            error_log('--- Final Credentials for Validation ---');
            foreach ($credentials as $key => $value) {
                if (strpos($key, 'password') !== false) {
                    error_log('Ryvr: Final credential[' . $key . '] = [' . strlen($value) . ' chars] ' . (empty($value) ? 'EMPTY' : 'NOT EMPTY'));
                } else {
                    error_log('Ryvr: Final credential[' . $key . '] = ' . $value);
                }
            }
            error_log('=== End Manager Credential Processing Debug ===');
            error_log('');
        }
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Ryvr: Validating credentials for connector: ' . $connector_id);
            error_log('Ryvr: Credentials keys: ' . print_r(array_keys($credentials), true));
        }
        
        $connector = $this->get_connector($connector_id);
        
        if (!$connector) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Ryvr: Connector not found in validate_auth: ' . $connector_id);
            }
            wp_send_json_error(['message' => __('Connector not found.', 'ryvr')]);
        }
        
        // Validate credentials
        try {
            $is_valid = $connector->validate_auth($credentials);
            
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Ryvr: Validation result for ' . $connector_id . ': ' . ($is_valid ? 'SUCCESS' : 'FAILED'));
            }
            
            if ($is_valid) {
                wp_send_json_success(['message' => __('Authentication successful.', 'ryvr')]);
            } else {
                wp_send_json_error(['message' => __('Authentication failed. Please check your credentials.', 'ryvr')]);
            }
        } catch (\Exception $e) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Ryvr: Exception during validation: ' . $e->getMessage());
            }
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
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('');
            error_log('=== Manager Credential Saving Debug ===');
            error_log('Ryvr: Saving credentials for connector: ' . $connector_id);
            error_log('Ryvr: User ID: ' . $user_id);
            error_log('Ryvr: Input credentials keys: ' . print_r(array_keys($credentials), true));
            foreach ($credentials as $key => $value) {
                if (strpos($key, 'password') !== false) {
                    error_log('Ryvr: Input credential[' . $key . '] = ' . ($value === '[KEEP_EXISTING]' ? '[KEEP_EXISTING]' : '[' . strlen($value) . ' chars]'));
                } else {
                    error_log('Ryvr: Input credential[' . $key . '] = ' . $value);
                }
            }
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'ryvr_api_keys';
        
        // Check if credentials already exist
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$table_name} WHERE connector_slug = %s AND user_id = %d",
            $connector_id,
            $user_id
        ));
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Ryvr: Existing credentials ID: ' . ($existing ? $existing : 'NONE'));
        }
        
        // Encrypt sensitive data
        require_once RYVR_PLUGIN_DIR . 'src/Security/Encryption.php';
        $encrypted_data = [];
        
        // Get existing credentials if we need to keep some values
        $existing_credentials = [];
        if ($existing) {
            $existing_result = $wpdb->get_var($wpdb->prepare(
                "SELECT auth_meta FROM {$table_name} WHERE id = %d",
                $existing
            ));
            
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Ryvr: Found existing credentials data: ' . ($existing_result ? 'YES' : 'NO'));
            }
            
            if ($existing_result) {
                $existing_encrypted = json_decode($existing_result, true);
                if (is_array($existing_encrypted)) {
                    foreach ($existing_encrypted as $key => $value) {
                        if (is_string($value)) {
                            $decrypted = \Ryvr\Security\Encryption::decrypt($value);
                            if ($decrypted !== false) {
                                $existing_credentials[$key] = $decrypted;
                                if (defined('WP_DEBUG') && WP_DEBUG) {
                                    if (strpos($key, 'password') !== false) {
                                        error_log('Ryvr: Loaded existing credential[' . $key . '] = [' . strlen($decrypted) . ' chars]');
                                    } else {
                                        error_log('Ryvr: Loaded existing credential[' . $key . '] = ' . $decrypted);
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
        
        foreach ($credentials as $key => $value) {
            if ($value === '[KEEP_EXISTING]' && isset($existing_credentials[$key])) {
                // Use existing value for this field
                $encrypted_data[$key] = \Ryvr\Security\Encryption::encrypt($existing_credentials[$key]);
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    if (strpos($key, 'password') !== false) {
                        error_log('Ryvr: Keeping existing value for ' . $key . ' [' . strlen($existing_credentials[$key]) . ' chars]');
                    } else {
                        error_log('Ryvr: Keeping existing value for ' . $key . ' = ' . $existing_credentials[$key]);
                    }
                }
            } elseif (is_string($value) && $value !== '[KEEP_EXISTING]') {
                $encrypted_data[$key] = \Ryvr\Security\Encryption::encrypt($value);
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    if (strpos($key, 'password') !== false) {
                        error_log('Ryvr: Encrypting new value for ' . $key . ' [' . strlen($value) . ' chars]');
                    } else {
                        error_log('Ryvr: Encrypting new value for ' . $key . ' = ' . $value);
                    }
                }
            } else {
                $encrypted_data[$key] = $value;
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log('Ryvr: Storing raw value for ' . $key . ' = ' . $value);
                }
            }
        }
        
        $data = [
            'connector_slug' => $connector_id,
            'user_id' => $user_id,
            'auth_meta' => json_encode($encrypted_data),
            'is_shared' => false,
        ];
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('');
            error_log('--- Database Save Operation ---');
            error_log('Ryvr: Data to save: connector_slug=' . $connector_id . ', user_id=' . $user_id . ', is_shared=false');
            error_log('Ryvr: Encrypted data keys: ' . print_r(array_keys($encrypted_data), true));
        }
        
        if ($existing) {
            // Update existing
            $result = $wpdb->update(
                $table_name,
                $data,
                [
                    'id' => $existing,
                ]
            );
            
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Ryvr: Update operation result: ' . ($result !== false ? 'SUCCESS' : 'FAILED'));
                if ($result === false) {
                    error_log('Ryvr: Database error: ' . $wpdb->last_error);
                }
            }
        } else {
            // Insert new
            $result = $wpdb->insert($table_name, $data);
            
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Ryvr: Insert operation result: ' . ($result !== false ? 'SUCCESS' : 'FAILED'));
                if ($result !== false) {
                    error_log('Ryvr: New record ID: ' . $wpdb->insert_id);
                } else {
                    error_log('Ryvr: Database error: ' . $wpdb->last_error);
                }
            }
        }
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('=== End Manager Credential Saving Debug ===');
            error_log('');
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
        // Debug logging
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Ryvr: ajax_get_auth_fields called');
            error_log('Ryvr: POST data: ' . print_r($_POST, true));
        }
        
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'ryvr-admin-nonce')) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Ryvr: Nonce verification failed');
            }
            wp_send_json_error(['message' => __('Security check failed.', 'ryvr')]);
        }
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Ryvr: User permission check failed');
            }
            wp_send_json_error(['message' => __('You do not have permission to perform this action.', 'ryvr')]);
        }
        
        // Get connector ID
        $connector_id = sanitize_text_field($_POST['connector_id'] ?? '');
        
        if (empty($connector_id)) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Ryvr: Missing connector_id');
            }
            wp_send_json_error(['message' => __('Invalid request parameters.', 'ryvr')]);
        }
        
        $connector = $this->get_connector($connector_id);
        
        if (!$connector) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Ryvr: Connector not found: ' . $connector_id);
                error_log('Ryvr: Available connectors: ' . print_r(array_keys($this->connectors), true));
            }
            wp_send_json_error(['message' => __('Connector not found.', 'ryvr')]);
        }
        
        // Get auth fields
        $fields = $connector->get_auth_fields();
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Ryvr: Auth fields for ' . $connector_id . ': ' . print_r($fields, true));
        }
        
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
                                $saved_credentials[$key] = $decrypted ? '[SAVED]' : '';
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
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Ryvr: Sending success response with ' . count($fields) . ' fields');
        }
        
        wp_send_json_success([
            'fields' => $fields,
            'saved_credentials' => $saved_credentials,
        ]);
    }
} 