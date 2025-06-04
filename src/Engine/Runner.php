<?php
declare(strict_types=1);

namespace Ryvr\Engine;

/**
 * Workflow execution runner.
 * Handles scheduled and triggered workflow execution.
 *
 * @since 1.0.0
 */
class Runner
{
    /**
     * Run a workflow.
     *
     * @param string $workflow_id  Workflow ID.
     * @param array  $input        Input data for the workflow.
     *
     * @return array Workflow execution result.
     *
     * @since 1.0.0
     */
    public function run(string $workflow_id, array $input = []): array
    {
        try {
            // Load workflow from database
            global $wpdb;
            
            $workflow = $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT * FROM {$wpdb->prefix}ryvr_workflows WHERE id = %s",
                    $workflow_id
                )
            );
            
            if (!$workflow) {
                throw new \Exception("Workflow not found: {$workflow_id}");
            }
            
            // Parse workflow definition
            $definition = json_decode($workflow->definition, true);
            if (!$definition) {
                throw new \Exception("Invalid workflow definition");
            }
            
            // Execute workflow nodes
            return $this->execute_workflow_nodes($definition, $input);
            
        } catch (\Exception $e) {
            error_log('Ryvr: Workflow execution failed: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Execute workflow nodes.
     *
     * @param array $definition
     * @param array $input
     * @return array
     */
    private function execute_workflow_nodes(array $definition, array $input): array
    {
        $results = [];
        $context = $input;
        
        // Process nodes from the workflow builder format
        if (isset($definition['nodes'])) {
            foreach ($definition['nodes'] as $node) {
                try {
                    $result = $this->execute_node($node, $context);
                    $results[$node['id']] = $result;
                    
                    // Update context with result for next nodes
                    $context = array_merge($context, $result);
                    
                } catch (\Exception $e) {
                    error_log("Ryvr: Node execution failed for {$node['id']}: " . $e->getMessage());
                    $results[$node['id']] = [
                        'success' => false,
                        'error' => $e->getMessage()
                    ];
                }
            }
        }
        
        return [
            'success' => true,
            'results' => $results,
            'context' => $context
        ];
    }
    
    /**
     * Execute a single node.
     *
     * @param array $node
     * @param array $context
     * @return array
     */
    private function execute_node(array $node, array $context): array
    {
        $connector_id = $node['connectorId'];
        $action_id = $node['actionId'];
        $parameters = $node['parameters'] ?? [];
        
        // Get connector instance
        $connector = $this->get_connector($connector_id);
        if (!$connector) {
            throw new \Exception("Connector not found: {$connector_id}");
        }
        
        // Get authentication
        $auth = $this->get_auth_for_connector($connector_id);
        
        // Execute action
        return $connector->execute_action($action_id, $parameters, $auth);
    }
    
    /**
     * Get connector instance.
     *
     * @param string $connector_id
     * @return mixed|null
     */
    private function get_connector(string $connector_id)
    {
        switch ($connector_id) {
            case 'openai':
                if (class_exists('\Ryvr\Connectors\OpenAI\OpenAIConnector')) {
                    return new \Ryvr\Connectors\OpenAI\OpenAIConnector();
                }
                break;
            case 'dataforseo':
                if (class_exists('\Ryvr\Connectors\DataForSEO\DataForSEOConnector')) {
                    return new \Ryvr\Connectors\DataForSEO\DataForSEOConnector();
                }
                break;
        }
        
        return null;
    }
    
    /**
     * Get authentication for connector.
     *
     * @param string $connector_id
     * @return array
     */
    private function get_auth_for_connector(string $connector_id): array
    {
        // Get stored credentials for the current user
        global $wpdb;
        
        $user_id = get_current_user_id();
        if (!$user_id) {
            return [];
        }
        
        $auth_data = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT auth_meta FROM {$wpdb->prefix}ryvr_api_keys WHERE connector_slug = %s AND user_id = %d",
                $connector_id,
                $user_id
            )
        );
        
        if (!$auth_data) {
            return [];
        }
        
        $credentials = json_decode($auth_data, true);
        if (!is_array($credentials)) {
            return [];
        }
        
        // Decrypt credentials if needed
        // TODO: Implement proper decryption
        return $credentials;
    }
    
    /**
     * Schedule a workflow to run.
     *
     * @param string $workflow_id   Workflow ID.
     * @param array  $input         Input data for the workflow.
     * @param int    $timestamp     Timestamp to run the workflow.
     *
     * @return bool Whether the workflow was scheduled successfully.
     *
     * @since 1.0.0
     */
    public function schedule(string $workflow_id, array $input = [], int $timestamp = 0): bool
    {
        // If no timestamp is provided, run immediately
        if ($timestamp <= 0) {
            $timestamp = time();
        }
        
        // Check if Action Scheduler is available
        if (function_exists('as_schedule_single_action')) {
            return as_schedule_single_action(
                $timestamp,
                'ryvr_run_workflow',
                [$workflow_id, $input],
                'ryvr'
            );
        }
        
        // Fallback to WordPress cron
        return wp_schedule_single_event(
            $timestamp,
            'ryvr_run_workflow',
            [$workflow_id, $input]
        );
    }
    
    /**
     * Continue workflow execution after async task completion.
     *
     * @param int $workflow_run_id
     * @param array $task_result
     * @return void
     */
    public function continue_after_async_task(int $workflow_run_id, array $task_result): void
    {
        // TODO: Implement continuation logic for async tasks
        error_log("Ryvr: Continuing workflow {$workflow_run_id} after async task completion");
    }
} 