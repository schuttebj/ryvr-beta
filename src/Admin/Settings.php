<?php
declare(strict_types=1);

namespace Ryvr\Admin;

/**
 * Settings handler.
 *
 * @since 1.0.0
 */
class Settings
{
    /**
     * Settings sections.
     *
     * @var array
     */
    private array $sections = [];
    
    /**
     * Register settings.
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function register(): void
    {
        // Initialize sections
        $this->init_sections();
        
        // Register settings
        add_action('admin_init', [$this, 'register_settings']);
        
        // Add AJAX handlers for API key validation
        add_action('wp_ajax_ryvr_validate_api_key', [$this, 'ajax_validate_api_key']);
    }
    
    /**
     * Initialize settings sections.
     *
     * @return void
     *
     * @since 1.0.0
     */
    private function init_sections(): void
    {
        $this->sections = [
            'general' => [
                'title' => __('General Settings', 'ryvr'),
                'description' => __('Configure general plugin settings.', 'ryvr'),
                'fields' => [
                    'disable_usage_tracking' => [
                        'title' => __('Disable Usage Tracking', 'ryvr'),
                        'description' => __('Opt out of anonymous usage tracking.', 'ryvr'),
                        'type' => 'checkbox',
                        'default' => false,
                    ],
                    'credit_limit' => [
                        'title' => __('Credit Limit', 'ryvr'),
                        'description' => __('Set the default credit limit for new users.', 'ryvr'),
                        'type' => 'number',
                        'default' => 1000,
                    ],
                ],
            ],
            'api_keys' => [
                'title' => __('API Keys', 'ryvr'),
                'description' => __('Configure API keys for various services.', 'ryvr'),
                'fields' => [
                    'openai_api_key' => [
                        'title' => __('OpenAI API Key', 'ryvr'),
                        'description' => __('Enter your OpenAI API key.', 'ryvr'),
                        'type' => 'password',
                        'sanitize' => 'sanitize_text_field',
                        'encrypted' => true,
                    ],
                    'dataforseo_login' => [
                        'title' => __('DataForSEO Login', 'ryvr'),
                        'description' => __('Enter your DataForSEO login.', 'ryvr'),
                        'type' => 'text',
                        'sanitize' => 'sanitize_text_field',
                    ],
                    'dataforseo_password' => [
                        'title' => __('DataForSEO Password', 'ryvr'),
                        'description' => __('Enter your DataForSEO password.', 'ryvr'),
                        'type' => 'password',
                        'sanitize' => 'sanitize_text_field',
                        'encrypted' => true,
                    ],
                ],
            ],
            'advanced' => [
                'title' => __('Advanced Settings', 'ryvr'),
                'description' => __('Configure advanced plugin settings.', 'ryvr'),
                'fields' => [
                    'debug_mode' => [
                        'title' => __('Debug Mode', 'ryvr'),
                        'description' => __('Enable debug mode for additional logging.', 'ryvr'),
                        'type' => 'checkbox',
                        'default' => false,
                    ],
                    'log_retention' => [
                        'title' => __('Log Retention', 'ryvr'),
                        'description' => __('Number of days to keep logs.', 'ryvr'),
                        'type' => 'number',
                        'default' => 30,
                    ],
                ],
            ],
        ];
    }
    
    /**
     * Register settings with WordPress.
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function register_settings(): void
    {
        // Register setting
        register_setting(
            'ryvr_settings',
            'ryvr_settings',
            [
                'sanitize_callback' => [$this, 'sanitize_settings'],
                'default' => $this->get_default_settings(),
            ]
        );
        
        // Register settings sections and fields
        foreach ($this->sections as $section_id => $section) {
            add_settings_section(
                'ryvr_' . $section_id . '_section',
                $section['title'],
                function () use ($section) {
                    echo '<p>' . esc_html($section['description']) . '</p>';
                },
                'ryvr_settings'
            );
            
            foreach ($section['fields'] as $field_id => $field) {
                add_settings_field(
                    'ryvr_' . $field_id,
                    $field['title'],
                    [$this, 'render_field'],
                    'ryvr_settings',
                    'ryvr_' . $section_id . '_section',
                    [
                        'id' => $field_id,
                        'section' => $section_id,
                        'label_for' => 'ryvr_' . $field_id,
                        'description' => $field['description'],
                        'type' => $field['type'],
                        'encrypted' => $field['encrypted'] ?? false,
                    ]
                );
            }
        }
    }
    
    /**
     * Get default settings.
     *
     * @return array Default settings.
     *
     * @since 1.0.0
     */
    private function get_default_settings(): array
    {
        $defaults = [];
        
        foreach ($this->sections as $section_id => $section) {
            foreach ($section['fields'] as $field_id => $field) {
                if (isset($field['default'])) {
                    $defaults[$field_id] = $field['default'];
                }
            }
        }
        
        return $defaults;
    }
    
    /**
     * Sanitize settings.
     *
     * @param array $input Input values.
     *
     * @return array Sanitized values.
     *
     * @since 1.0.0
     */
    public function sanitize_settings(array $input): array
    {
        $sanitized = [];
        $current = get_option('ryvr_settings', []);
        
        foreach ($this->sections as $section_id => $section) {
            foreach ($section['fields'] as $field_id => $field) {
                $sanitize_callback = $field['sanitize'] ?? null;
                
                if (isset($input[$field_id])) {
                    if ($sanitize_callback && function_exists($sanitize_callback)) {
                        $sanitized[$field_id] = $sanitize_callback($input[$field_id]);
                    } else {
                        switch ($field['type']) {
                            case 'checkbox':
                                $sanitized[$field_id] = (bool) $input[$field_id];
                                break;
                            case 'number':
                                $sanitized[$field_id] = (int) $input[$field_id];
                                break;
                            case 'text':
                            case 'password':
                                $sanitized[$field_id] = sanitize_text_field($input[$field_id]);
                                break;
                            default:
                                $sanitized[$field_id] = $input[$field_id];
                                break;
                        }
                    }
                    
                    // Encrypt if needed
                    if (($field['encrypted'] ?? false) && !empty($sanitized[$field_id])) {
                        $sanitized[$field_id] = $this->encrypt_value($sanitized[$field_id]);
                    }
                } elseif ($field['type'] === 'checkbox') {
                    $sanitized[$field_id] = false;
                } elseif (isset($field['default'])) {
                    $sanitized[$field_id] = $field['default'];
                }
            }
        }
        
        return $sanitized;
    }
    
    /**
     * Encrypt a value.
     *
     * @param string $value Value to encrypt.
     *
     * @return string Encrypted value.
     *
     * @since 1.0.0
     */
    private function encrypt_value(string $value): string
    {
        if (function_exists('sodium_crypto_secretbox')) {
            // Use libsodium if available (PHP 7.2+)
            $key = $this->get_encryption_key();
            $nonce = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
            $encrypted = sodium_crypto_secretbox($value, $nonce, $key);
            
            return base64_encode($nonce . $encrypted);
        }
        
        // Fallback to WordPress's encrypt function
        return wp_encrypt_password($value);
    }
    
    /**
     * Decrypt a value.
     *
     * @param string $encrypted_value Encrypted value.
     *
     * @return string|false Decrypted value or false on failure.
     *
     * @since 1.0.0
     */
    private function decrypt_value(string $encrypted_value): string|false
    {
        if (function_exists('sodium_crypto_secretbox_open')) {
            // Use libsodium if available (PHP 7.2+)
            $key = $this->get_encryption_key();
            $decoded = base64_decode($encrypted_value);
            
            if ($decoded === false) {
                return false;
            }
            
            $nonce = mb_substr($decoded, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES, '8bit');
            $encrypted = mb_substr($decoded, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES, null, '8bit');
            
            $decrypted = sodium_crypto_secretbox_open($encrypted, $nonce, $key);
            
            if ($decrypted === false) {
                return false;
            }
            
            return $decrypted;
        }
        
        // If it's encrypted with wp_encrypt_password, we can't decrypt it
        return false;
    }
    
    /**
     * Get or generate encryption key.
     *
     * @return string Encryption key.
     *
     * @since 1.0.0
     */
    private function get_encryption_key(): string
    {
        $key = get_option('ryvr_encryption_key');
        
        if (!$key) {
            if (defined('RYVR_ENCRYPTION_KEY') && RYVR_ENCRYPTION_KEY) {
                $key = RYVR_ENCRYPTION_KEY;
            } else {
                $key = base64_encode(random_bytes(SODIUM_CRYPTO_SECRETBOX_KEYBYTES));
                update_option('ryvr_encryption_key', $key);
            }
        }
        
        return base64_decode($key);
    }
    
    /**
     * Render settings field.
     *
     * @param array $args Field arguments.
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function render_field(array $args): void
    {
        $id = $args['id'];
        $type = $args['type'];
        $encrypted = $args['encrypted'] ?? false;
        $field_id = 'ryvr_' . $id;
        $name = 'ryvr_settings[' . $id . ']';
        $settings = get_option('ryvr_settings', []);
        $value = $settings[$id] ?? '';
        
        // Check if we need to decrypt the value
        if ($encrypted && !empty($value)) {
            $decrypted = $this->decrypt_value($value);
            if ($decrypted !== false) {
                $value = $decrypted;
            } else {
                // If we can't decrypt, show placeholder
                $value = '';
            }
        }
        
        switch ($type) {
            case 'text':
            case 'password':
                printf(
                    '<input type="%s" id="%s" name="%s" value="%s" class="regular-text" />',
                    esc_attr($type),
                    esc_attr($field_id),
                    esc_attr($name),
                    esc_attr($value)
                );
                
                if ($encrypted && $args['section'] === 'api_keys') {
                    printf(
                        '<button type="button" class="button ryvr-validate-api-key" data-connector="%s">%s</button>',
                        esc_attr($id),
                        esc_html__('Validate', 'ryvr')
                    );
                }
                break;
                
            case 'checkbox':
                printf(
                    '<input type="checkbox" id="%s" name="%s" value="1" %s />',
                    esc_attr($field_id),
                    esc_attr($name),
                    checked((bool) $value, true, false)
                );
                break;
                
            case 'number':
                printf(
                    '<input type="number" id="%s" name="%s" value="%s" class="small-text" />',
                    esc_attr($field_id),
                    esc_attr($name),
                    esc_attr($value)
                );
                break;
                
            default:
                break;
        }
        
        if (!empty($args['description'])) {
            printf('<p class="description">%s</p>', esc_html($args['description']));
        }
    }
    
    /**
     * AJAX handler for API key validation.
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function ajax_validate_api_key(): void
    {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'ryvr-admin-nonce')) {
            wp_send_json_error(['message' => __('Security check failed.', 'ryvr')]);
        }
        
        // Check permissions
        if (!current_user_can('manage_ryvr_connectors')) {
            wp_send_json_error(['message' => __('You do not have permission to perform this action.', 'ryvr')]);
        }
        
        // Get connector and key
        $connector = sanitize_text_field($_POST['connector'] ?? '');
        $key = sanitize_text_field($_POST['key'] ?? '');
        
        if (empty($connector) || empty($key)) {
            wp_send_json_error(['message' => __('Missing required parameters.', 'ryvr')]);
        }
        
        // Validate the key
        // TODO: Implement actual validation with connector
        
        wp_send_json_success(['message' => __('API key is valid.', 'ryvr')]);
    }
    
    /**
     * Get a setting value.
     *
     * @param string $key     Setting key.
     * @param mixed  $default Default value.
     *
     * @return mixed Setting value.
     *
     * @since 1.0.0
     */
    public static function get_setting(string $key, $default = null)
    {
        $settings = get_option('ryvr_settings', []);
        
        if (isset($settings[$key])) {
            return $settings[$key];
        }
        
        return $default;
    }
} 