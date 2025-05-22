<?php
declare(strict_types=1);

namespace Ryvr\Workflows;

use Ryvr\Connectors\Manager as ConnectorManager;

/**
 * JSON workflow implementation.
 *
 * @since 1.0.0
 */
class JSONWorkflow extends AbstractWorkflow
{
    /**
     * Connector manager.
     *
     * @var \Ryvr\Connectors\Manager
     */
    protected $connector_manager;
    
    /**
     * Workflow constructor.
     *
     * @param array                    $definition        Workflow definition.
     * @param \Ryvr\Connectors\Manager $connector_manager Connector manager.
     *
     * @since 1.0.0
     */
    public function __construct(array $definition = [], ConnectorManager $connector_manager = null)
    {
        parent::__construct($definition);
        
        $this->connector_manager = $connector_manager ?? new ConnectorManager();
    }
    
    /**
     * Create a workflow from a JSON string.
     *
     * @param string                   $json             JSON string.
     * @param \Ryvr\Connectors\Manager $connector_manager Connector manager.
     *
     * @return self
     *
     * @throws \Exception If JSON is invalid.
     *
     * @since 1.0.0
     */
    public static function from_json(string $json, ConnectorManager $connector_manager = null): self
    {
        $definition = json_decode($json, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception(
                sprintf(__('Invalid JSON: %s', 'ryvr'), json_last_error_msg())
            );
        }
        
        return new self($definition, $connector_manager);
    }
    
    /**
     * Execute a single workflow step.
     *
     * @param array $step Step definition.
     *
     * @return mixed Result of the step execution.
     *
     * @throws \Exception If execution fails.
     *
     * @since 1.0.0
     */
    protected function execute_step(array $step)
    {
        $step_id = $step['id'];
        $step_type = $step['type'];
        
        // Handle different step types
        switch ($step_type) {
            case 'decision':
                return $this->execute_decision_step($step);
                
            case 'action':
                return $this->execute_action_step($step);
                
            case 'transformer':
                return $this->execute_transformer_step($step);
                
            default:
                throw new \Exception(
                    sprintf(__('Unknown step type: %s', 'ryvr'), $step_type)
                );
        }
    }
    
    /**
     * Execute a decision step.
     *
     * @param array $step Step definition.
     *
     * @return array Result of the step execution.
     *
     * @throws \Exception If execution fails.
     *
     * @since 1.0.0
     */
    protected function execute_decision_step(array $step): array
    {
        $condition = $step['condition'] ?? '';
        
        if (empty($condition)) {
            throw new \Exception(
                sprintf(__('Decision step %s must have a condition', 'ryvr'), $step['id'])
            );
        }
        
        // TODO: Implement a more robust condition evaluation
        // For now, we'll use a simple eval-based approach
        $condition = $this->render_template($condition, $this->context);
        
        // Simple condition evaluation
        $result = false;
        
        try {
            // Note: This is a simple implementation for demonstration
            // In a production environment, we should use a proper expression evaluator
            $code = sprintf('return %s;', $condition);
            $result = eval($code);
        } catch (\Throwable $e) {
            throw new \Exception(
                sprintf(__('Error evaluating condition: %s', 'ryvr'), $e->getMessage())
            );
        }
        
        return [
            'result' => $result,
            'condition' => $condition,
        ];
    }
    
    /**
     * Execute an action step.
     *
     * @param array $step Step definition.
     *
     * @return array Result of the step execution.
     *
     * @throws \Exception If execution fails.
     *
     * @since 1.0.0
     */
    protected function execute_action_step(array $step): array
    {
        $connector_id = $step['connector'] ?? '';
        $action_id = $step['action'] ?? '';
        $params = $step['params'] ?? [];
        
        if (empty($connector_id)) {
            throw new \Exception(
                sprintf(__('Action step %s must specify a connector', 'ryvr'), $step['id'])
            );
        }
        
        if (empty($action_id)) {
            throw new \Exception(
                sprintf(__('Action step %s must specify an action', 'ryvr'), $step['id'])
            );
        }
        
        // Get the connector
        $connector = $this->connector_manager->get_connector($connector_id);
        
        if (!$connector) {
            throw new \Exception(
                sprintf(__('Connector not found: %s', 'ryvr'), $connector_id)
            );
        }
        
        // Prepare parameters using current context
        $prepared_params = [];
        
        foreach ($params as $key => $value) {
            if (is_string($value) && strpos($value, '{{') !== false) {
                $prepared_params[$key] = $this->render_template($value, $this->context);
            } else {
                $prepared_params[$key] = $value;
            }
        }
        
        // Get authentication credentials for the connector
        $auth = $this->get_connector_auth($connector_id);
        
        // Execute the action
        return $connector->execute_action($action_id, $prepared_params, $auth);
    }
    
    /**
     * Execute a transformer step.
     *
     * @param array $step Step definition.
     *
     * @return array Result of the step execution.
     *
     * @throws \Exception If execution fails.
     *
     * @since 1.0.0
     */
    protected function execute_transformer_step(array $step): array
    {
        $template = $step['template'] ?? '';
        $input = $step['input'] ?? [];
        
        if (empty($template)) {
            throw new \Exception(
                sprintf(__('Transformer step %s must have a template', 'ryvr'), $step['id'])
            );
        }
        
        // Prepare input using current context
        $prepared_input = [];
        
        foreach ($input as $key => $value) {
            if (is_string($value) && strpos($value, '{{') !== false) {
                $prepared_input[$key] = $this->render_template($value, $this->context);
            } else {
                $prepared_input[$key] = $value;
            }
        }
        
        // Create a temporary context with the input
        $temp_context = array_merge($this->context, ['input' => $prepared_input]);
        
        // Render the template
        $result = $this->render_template($template, $temp_context);
        
        return [
            'result' => $result,
        ];
    }
    
    /**
     * Get authentication credentials for a connector.
     *
     * @param string $connector_id Connector ID.
     *
     * @return array Authentication credentials.
     *
     * @since 1.0.0
     */
    protected function get_connector_auth(string $connector_id): array
    {
        global $wpdb;
        
        // Get API keys from database
        $table_name = $wpdb->prefix . 'ryvr_api_keys';
        $result = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT auth_meta FROM {$table_name} WHERE connector_slug = %s AND (user_id = %d OR is_shared = 1) LIMIT 1",
                $connector_id,
                get_current_user_id()
            ),
            ARRAY_A
        );
        
        if (!$result || empty($result['auth_meta'])) {
            return [];
        }
        
        // Decrypt auth meta
        $auth_meta = json_decode($result['auth_meta'], true);
        
        if (!$auth_meta || !is_array($auth_meta)) {
            return [];
        }
        
        $auth = [];
        
        // Decrypt sensitive values if encryption is available
        if (class_exists('\\Ryvr\\Security\\Encryption')) {
            foreach ($auth_meta as $key => $value) {
                if (is_string($value) && strpos($value, 'ryvr_encrypted:') === 0) {
                    $encrypted = substr($value, 15); // Remove 'ryvr_encrypted:' prefix
                    try {
                        $auth[$key] = \Ryvr\Security\Encryption::decrypt($encrypted);
                    } catch (\Exception $e) {
                        $auth[$key] = '';
                    }
                } else {
                    $auth[$key] = $value;
                }
            }
        } else {
            $auth = $auth_meta;
        }
        
        return $auth;
    }
} 