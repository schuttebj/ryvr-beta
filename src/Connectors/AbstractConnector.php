<?php
declare(strict_types=1);

namespace Ryvr\Connectors;

use Ryvr\Admin\Settings;
use Ryvr\Security\Encryption;

/**
 * Abstract base class for Ryvr connectors.
 *
 * @since 1.0.0
 */
abstract class AbstractConnector implements RyvrConnectorInterface
{
    /**
     * Get the connector icon URL.
     *
     * @return string URL to the connector icon.
     *
     * @since 1.0.0
     */
    public function get_icon_url(): string
    {
        // Default icon if none is provided
        return RYVR_PLUGIN_URL . 'assets/images/connectors/' . $this->get_id() . '.svg';
    }
    
    /**
     * Helper method to securely store authentication credentials.
     *
     * @param int   $user_id    User ID.
     * @param array $credentials Authentication credentials.
     *
     * @return bool Whether the credentials were stored successfully.
     *
     * @since 1.0.0
     */
    protected function store_credentials(int $user_id, array $credentials): bool
    {
        global $wpdb;
        
        // Encrypt sensitive data
        $encrypted_data = [];
        
        foreach ($credentials as $key => $value) {
            if (is_string($value)) {
                $encrypted_data[$key] = Encryption::encrypt($value);
            } else {
                $encrypted_data[$key] = $value;
            }
        }
        
        $table_name = $wpdb->prefix . 'ryvr_api_keys';
        
        // Check if credentials already exist for this user and connector
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$table_name} WHERE connector_slug = %s AND user_id = %d",
            $this->get_id(),
            $user_id
        ));
        
        $data = [
            'connector_slug' => $this->get_id(),
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
        
        return $result !== false;
    }
    
    /**
     * Helper method to retrieve authentication credentials.
     *
     * @param int $user_id User ID.
     *
     * @return array|null Authentication credentials or null if not found.
     *
     * @since 1.0.0
     */
    protected function get_credentials(int $user_id): ?array
    {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'ryvr_api_keys';
        
        $result = $wpdb->get_var($wpdb->prepare(
            "SELECT auth_meta FROM {$table_name} WHERE connector_slug = %s AND user_id = %d",
            $this->get_id(),
            $user_id
        ));
        
        if (!$result) {
            return null;
        }
        
        $credentials = json_decode($result, true);
        
        if (!is_array($credentials)) {
            return null;
        }
        
        // Decrypt sensitive data
        foreach ($credentials as $key => $value) {
            if (is_string($value)) {
                $credentials[$key] = Encryption::decrypt($value);
            }
        }
        
        return $credentials;
    }
    
    /**
     * Helper method to delete authentication credentials.
     *
     * @param int $user_id User ID.
     *
     * @return bool Whether the credentials were deleted successfully.
     *
     * @since 1.0.0
     */
    protected function delete_credentials(int $user_id): bool
    {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'ryvr_api_keys';
        
        $result = $wpdb->delete(
            $table_name,
            [
                'connector_slug' => $this->get_id(),
                'user_id' => $user_id,
            ]
        );
        
        return $result !== false;
    }
    
    /**
     * Helper method to log connector actions.
     *
     * @param string $message Log message.
     * @param array  $context Additional context.
     * @param string $level   Log level.
     *
     * @return void
     *
     * @since 1.0.0
     */
    protected function log(string $message, array $context = [], string $level = 'info'): void
    {
        global $wpdb;
        
        // Only log if debug mode is enabled
        $debug_mode = Settings::get_setting('debug_mode', false);
        
        if (!$debug_mode && $level === 'debug') {
            return;
        }
        
        $table_name = $wpdb->prefix . 'ryvr_logs';
        
        $wpdb->insert(
            $table_name,
            [
                'log_level' => $level,
                'message' => $message,
                'context' => json_encode($context),
            ]
        );
    }
    
    /**
     * Default implementation of register_trigger.
     * Child classes should override if they have specific trigger handling.
     *
     * @param string   $trigger_id Trigger identifier.
     * @param callable $callback   Callback to execute when the trigger fires.
     * @param array    $params     Trigger parameters.
     * @param array    $auth       Authentication credentials.
     *
     * @return bool Whether the trigger was registered successfully.
     *
     * @since 1.0.0
     */
    public function register_trigger(string $trigger_id, callable $callback, array $params, array $auth): bool
    {
        // Default implementation does nothing
        return false;
    }
} 