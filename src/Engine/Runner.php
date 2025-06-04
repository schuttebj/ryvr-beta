<?php
declare(strict_types=1);

namespace Ryvr\Engine;

use Ryvr\Workflows\Manager as WorkflowManager;

/**
 * Workflow execution runner.
 * Handles scheduled and triggered workflow execution.
 *
 * @since 1.0.0
 */
class Runner
{
    /**
     * Workflow manager instance.
     *
     * @var \Ryvr\Workflows\Manager
     */
    protected $workflow_manager;
    
    /**
     * Runner constructor.
     *
     * @param \Ryvr\Workflows\Manager|null $workflow_manager Workflow manager instance.
     *
     * @since 1.0.0
     */
    public function __construct(\Ryvr\Workflows\Manager $workflow_manager = null)
    {
        $this->workflow_manager = $workflow_manager ?? new WorkflowManager();
    }
    
    /**
     * Run a workflow.
     *
     * This method is called by the WordPress cron system or directly when 
     * a workflow is triggered manually.
     *
     * @param string $workflow_id  Workflow ID.
     * @param array  $input        Input data for the workflow.
     *
     * @return array Workflow execution result.
     *
     * @throws \Exception If execution fails.
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
        
        // Get authentication (placeholder - would need to get from settings/user)
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
     * TODO: Implement proper auth retrieval from settings/user
     *
     * @param string $connector_id
     * @return array
     */
    private function get_auth_for_connector(string $connector_id): array
    {
        // Placeholder - would need to implement proper auth retrieval
        return [];
    }
    
    /**
     * Schedule a workflow to run.
     *
     * @param string $workflow_id   Workflow ID.
     * @param array  $input         Input data for the workflow.
     * @param int    $timestamp     Timestamp to run the workflow.
     * @param string $unique_id     Optional unique ID for the event.
     *
     * @return bool Whether the workflow was scheduled successfully.
     *
     * @since 1.0.0
     */
    public function schedule(string $workflow_id, array $input = [], int $timestamp = 0, string $unique_id = ''): bool
    {
        // If no timestamp is provided, run immediately
        if ($timestamp <= 0) {
            $timestamp = time();
        }
        
        // Check if Action Scheduler is available
        if (function_exists('as_schedule_single_action')) {
            // Generate a unique ID if none provided
            if (empty($unique_id)) {
                $unique_id = uniqid('ryvr_workflow_', true);
            }
            
            // Schedule the workflow execution
            return as_schedule_single_action(
                $timestamp,
                'ryvr_run_workflow',
                [
                    'workflow_id' => $workflow_id,
                    'input' => $input,
                ],
                'ryvr',
                $unique_id
            );
        }
        
        // Fallback to WordPress cron if Action Scheduler is not available
        return wp_schedule_single_event(
            $timestamp,
            'ryvr_run_workflow',
            [
                $workflow_id,
                $input,
            ]
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