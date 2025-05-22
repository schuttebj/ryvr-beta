<?php
declare(strict_types=1);

namespace Ryvr\Connectors;

/**
 * Interface for all Ryvr connectors.
 *
 * @since 1.0.0
 */
interface RyvrConnectorInterface
{
    /**
     * Get the connector ID.
     *
     * @return string Unique connector identifier.
     *
     * @since 1.0.0
     */
    public function get_id(): string;
    
    /**
     * Get the connector name.
     *
     * @return string Human-readable connector name.
     *
     * @since 1.0.0
     */
    public function get_name(): string;
    
    /**
     * Get the connector description.
     *
     * @return string Human-readable connector description.
     *
     * @since 1.0.0
     */
    public function get_description(): string;
    
    /**
     * Get the connector icon URL.
     *
     * @return string URL to the connector icon.
     *
     * @since 1.0.0
     */
    public function get_icon_url(): string;
    
    /**
     * Get the authentication fields required by this connector.
     *
     * @return array List of authentication field definitions.
     *
     * @since 1.0.0
     */
    public function get_auth_fields(): array;
    
    /**
     * Validate authentication credentials.
     *
     * @param array $credentials Authentication credentials.
     *
     * @return bool Whether the credentials are valid.
     *
     * @since 1.0.0
     */
    public function validate_auth(array $credentials): bool;
    
    /**
     * Get the available actions for this connector.
     *
     * @return array List of available action definitions.
     *
     * @since 1.0.0
     */
    public function get_actions(): array;
    
    /**
     * Get the available triggers for this connector.
     *
     * @return array List of available trigger definitions.
     *
     * @since 1.0.0
     */
    public function get_triggers(): array;
    
    /**
     * Execute an action.
     *
     * @param string $action_id Action identifier.
     * @param array  $params    Action parameters.
     * @param array  $auth      Authentication credentials.
     *
     * @return array Result of the action execution.
     *
     * @throws \Exception If the action execution fails.
     *
     * @since 1.0.0
     */
    public function execute_action(string $action_id, array $params, array $auth): array;
    
    /**
     * Register a trigger.
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
    public function register_trigger(string $trigger_id, callable $callback, array $params, array $auth): bool;
} 