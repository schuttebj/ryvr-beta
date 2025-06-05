<?php
declare(strict_types=1);

namespace Ryvr\Engine;

use Ryvr\Connectors\Manager as ConnectorManager;

/**
 * Flow engine for managing workflow execution and task connections.
 * Handles data flow between steps with mapping, validation, and transformation.
 *
 * @since 1.0.0
 */
class FlowEngine
{
    /**
     * Connector manager instance.
     *
     * @var ConnectorManager
     */
    private $connector_manager;
    
    /**
     * Data processor instance.
     *
     * @var DataProcessor
     */
    private $data_processor;
    
    /**
     * Workflow execution context.
     *
     * @var array
     */
    private $context = [];
    
    /**
     * Step execution results.
     *
     * @var array
     */
    private $step_results = [];
    
    /**
     * Flow connections between steps.
     *
     * @var array
     */
    private $connections = [];
    
    /**
     * Constructor.
     *
     * @param ConnectorManager|null $connector_manager Connector manager instance.
     * @param DataProcessor|null $data_processor Data processor instance.
     */
    public function __construct(
        ConnectorManager $connector_manager = null,
        DataProcessor $data_processor = null
    ) {
        $this->connector_manager = $connector_manager ?? new ConnectorManager();
        $this->data_processor = $data_processor ?? new DataProcessor();
    }
    
    /**
     * Execute a workflow with its flow connections.
     *
     * @param array $workflow_definition Complete workflow definition.
     * @param array $input_data Initial input data.
     *
     * @return array Execution result with step outputs and final result.
     *
     * @throws \Exception If execution fails.
     */
    public function execute_workflow(array $workflow_definition, array $input_data = []): array
    {
        // Initialize execution context
        $this->context = $input_data;
        $this->step_results = [];
        $this->connections = $workflow_definition['connections'] ?? [];
        
        $steps = $workflow_definition['steps'] ?? [];
        $nodes = $workflow_definition['nodes'] ?? [];
        
        // Use nodes format if available, otherwise fall back to steps
        if (!empty($nodes)) {
            return $this->execute_node_workflow($nodes, $input_data);
        } else {
            return $this->execute_step_workflow($steps, $input_data);
        }
    }
    
    /**
     * Execute workflow using node-based format.
     *
     * @param array $nodes Workflow nodes.
     * @param array $input_data Input data.
     *
     * @return array Execution result.
     */
    private function execute_node_workflow(array $nodes, array $input_data): array
    {
        // Build execution order from connections
        $execution_order = $this->build_execution_order($nodes);
        
        foreach ($execution_order as $node_id) {
            $node = $this->find_node_by_id($nodes, $node_id);
            
            if (!$node) {
                continue;
            }
            
            try {
                $step_result = $this->execute_node($node);
                $this->step_results[$node_id] = $step_result;
                
                // Process connected nodes
                $this->process_node_connections($node_id, $step_result);
                
            } catch (\Exception $e) {
                $this->step_results[$node_id] = [
                    'success' => false,
                    'error' => $e->getMessage()
                ];
                
                // Check if we should continue or abort
                $error_handling = $node['error_handling'] ?? 'abort';
                if ($error_handling === 'abort') {
                    throw $e;
                }
            }
        }
        
        return [
            'success' => true,
            'step_results' => $this->step_results,
            'context' => $this->context
        ];
    }
    
    /**
     * Execute workflow using step-based format.
     *
     * @param array $steps Workflow steps.
     * @param array $input_data Input data.
     *
     * @return array Execution result.
     */
    private function execute_step_workflow(array $steps, array $input_data): array
    {
        foreach ($steps as $step) {
            $step_id = $step['id'];
            
            try {
                $step_result = $this->execute_step($step);
                $this->step_results[$step_id] = $step_result;
                
                // Update context with result
                $this->context[$step_id] = $step_result;
                
            } catch (\Exception $e) {
                $this->step_results[$step_id] = [
                    'success' => false,
                    'error' => $e->getMessage()
                ];
                
                $error_handling = $step['error_handling'] ?? 'abort';
                if ($error_handling === 'abort') {
                    throw $e;
                }
            }
        }
        
        return [
            'success' => true,
            'step_results' => $this->step_results,
            'context' => $this->context
        ];
    }
    
    /**
     * Execute a single node.
     *
     * @param array $node Node definition.
     *
     * @return array Execution result.
     *
     * @throws \Exception If execution fails.
     */
    private function execute_node(array $node): array
    {
        $connector_id = $node['connectorId'] ?? '';
        $action_id = $node['actionId'] ?? '';
        $parameters = $node['parameters'] ?? [];
        
        if (empty($connector_id) || empty($action_id)) {
            throw new \Exception(
                sprintf(__('Node %s missing connector or action', 'ryvr'), $node['id'])
            );
        }
        
        // Get connector instance
        $connector = $this->connector_manager->get_connector($connector_id);
        if (!$connector) {
            throw new \Exception(
                sprintf(__('Connector not found: %s', 'ryvr'), $connector_id)
            );
        }
        
        // Process parameters with context substitution
        $processed_parameters = $this->process_parameters($parameters, $this->context);
        
        // Get authentication
        $auth = $this->get_connector_auth($connector_id);
        
        // Execute the action
        return $connector->execute_action($action_id, $processed_parameters, $auth);
    }
    
    /**
     * Execute a single step.
     *
     * @param array $step Step definition.
     *
     * @return array Execution result.
     *
     * @throws \Exception If execution fails.
     */
    private function execute_step(array $step): array
    {
        $step_type = $step['type'] ?? '';
        
        switch ($step_type) {
            case 'action':
            case 'connector':
                return $this->execute_connector_step($step);
                
            case 'decision':
                return $this->execute_decision_step($step);
                
            case 'transformer':
                return $this->execute_transformer_step($step);
                
            case 'validator':
                return $this->execute_validator_step($step);
                
            case 'mapper':
                return $this->execute_mapper_step($step);
                
            default:
                throw new \Exception(
                    sprintf(__('Unknown step type: %s', 'ryvr'), $step_type)
                );
        }
    }
    
    /**
     * Execute a connector/action step.
     *
     * @param array $step Step definition.
     *
     * @return array Execution result.
     */
    private function execute_connector_step(array $step): array
    {
        $connector_id = $step['connector'] ?? '';
        $action_id = $step['action'] ?? '';
        $parameters = $step['params'] ?? $step['parameters'] ?? [];
        
        if (empty($connector_id) || empty($action_id)) {
            throw new \Exception(
                sprintf(__('Step %s missing connector or action', 'ryvr'), $step['id'])
            );
        }
        
        $connector = $this->connector_manager->get_connector($connector_id);
        if (!$connector) {
            throw new \Exception(
                sprintf(__('Connector not found: %s', 'ryvr'), $connector_id)
            );
        }
        
        $processed_parameters = $this->process_parameters($parameters, $this->context);
        $auth = $this->get_connector_auth($connector_id);
        
        return $connector->execute_action($action_id, $processed_parameters, $auth);
    }
    
    /**
     * Execute a decision step.
     *
     * @param array $step Step definition.
     *
     * @return array Execution result.
     */
    private function execute_decision_step(array $step): array
    {
        $condition = $step['condition'] ?? '';
        
        if (empty($condition)) {
            throw new \Exception(
                sprintf(__('Decision step %s missing condition', 'ryvr'), $step['id'])
            );
        }
        
        // Process condition with context
        $processed_condition = $this->process_template($condition, $this->context);
        
        // Evaluate condition (simple evaluation for now)
        $result = $this->evaluate_condition($processed_condition);
        
        return [
            'condition' => $processed_condition,
            'result' => $result,
            'next_step' => $result ? ($step['true_path'] ?? null) : ($step['false_path'] ?? null)
        ];
    }
    
    /**
     * Execute a transformer step.
     *
     * @param array $step Step definition.
     *
     * @return array Execution result.
     */
    private function execute_transformer_step(array $step): array
    {
        $template = $step['template'] ?? '';
        $transformations = $step['transformations'] ?? [];
        
        if (!empty($template)) {
            // Template-based transformation
            $result = $this->process_template($template, $this->context);
            return ['result' => $result];
        } elseif (!empty($transformations)) {
            // Rule-based transformation
            $data = $step['input_data'] ?? $this->context;
            $transformed = $this->data_processor->apply_transformations($data, $transformations);
            return ['result' => $transformed];
        }
        
        throw new \Exception(
            sprintf(__('Transformer step %s missing template or transformations', 'ryvr'), $step['id'])
        );
    }
    
    /**
     * Execute a validator step.
     *
     * @param array $step Step definition.
     *
     * @return array Execution result.
     */
    private function execute_validator_step(array $step): array
    {
        $validation_rules = $step['validation_rules'] ?? [];
        $data = $step['input_data'] ?? $this->context;
        
        $validation_result = $this->data_processor->validate_data($data, $validation_rules);
        
        if (!$validation_result['valid']) {
            $error_handling = $step['error_handling'] ?? 'abort';
            
            if ($error_handling === 'abort') {
                throw new \Exception(
                    sprintf(
                        __('Validation failed in step %s: %s', 'ryvr'),
                        $step['id'],
                        implode(', ', $validation_result['errors'])
                    )
                );
            }
        }
        
        return $validation_result;
    }
    
    /**
     * Execute a mapper step.
     *
     * @param array $step Step definition.
     *
     * @return array Execution result.
     */
    private function execute_mapper_step(array $step): array
    {
        $mapping = $step['mapping'] ?? [];
        $source_data = $step['input_data'] ?? $this->context;
        
        $mapped_data = $this->data_processor->apply_field_mapping($source_data, $mapping);
        
        return ['result' => $mapped_data];
    }
    
    /**
     * Process node connections and data flow.
     *
     * @param string $node_id Source node ID.
     * @param array $step_result Step execution result.
     */
    private function process_node_connections(string $node_id, array $step_result): void
    {
        foreach ($this->connections as $connection) {
            if ($connection['source'] === $node_id) {
                $target_node_id = $connection['target'];
                $mapping = $connection['mapping'] ?? [];
                
                // Apply data mapping for this connection
                if (!empty($mapping)) {
                    $mapped_data = $this->data_processor->apply_field_mapping($step_result, $mapping);
                    $this->context["{$target_node_id}_input"] = $mapped_data;
                } else {
                    $this->context["{$target_node_id}_input"] = $step_result;
                }
            }
        }
        
        // Always update global context
        $this->context[$node_id] = $step_result;
    }
    
    /**
     * Build execution order from nodes and connections.
     *
     * @param array $nodes Workflow nodes.
     *
     * @return array Ordered list of node IDs.
     */
    private function build_execution_order(array $nodes): array
    {
        // Simple topological sort based on connections
        $order = [];
        $visited = [];
        $node_ids = array_column($nodes, 'id');
        
        // Find start nodes (nodes with no incoming connections)
        $start_nodes = [];
        foreach ($node_ids as $node_id) {
            $has_incoming = false;
            foreach ($this->connections as $connection) {
                if ($connection['target'] === $node_id) {
                    $has_incoming = true;
                    break;
                }
            }
            if (!$has_incoming) {
                $start_nodes[] = $node_id;
            }
        }
        
        // If no clear start nodes, use first node
        if (empty($start_nodes) && !empty($node_ids)) {
            $start_nodes = [$node_ids[0]];
        }
        
        // Simple DFS to build order
        foreach ($start_nodes as $start_node) {
            $this->visit_node($start_node, $visited, $order);
        }
        
        // Add any remaining unvisited nodes
        foreach ($node_ids as $node_id) {
            if (!in_array($node_id, $visited)) {
                $order[] = $node_id;
            }
        }
        
        return $order;
    }
    
    /**
     * Visit node for topological sort.
     *
     * @param string $node_id Node ID to visit.
     * @param array $visited Visited nodes list.
     * @param array $order Execution order list.
     */
    private function visit_node(string $node_id, array &$visited, array &$order): void
    {
        if (in_array($node_id, $visited)) {
            return;
        }
        
        $visited[] = $node_id;
        
        // Visit connected nodes first
        foreach ($this->connections as $connection) {
            if ($connection['source'] === $node_id) {
                $this->visit_node($connection['target'], $visited, $order);
            }
        }
        
        $order[] = $node_id;
    }
    
    /**
     * Find node by ID.
     *
     * @param array $nodes Node list.
     * @param string $node_id Node ID.
     *
     * @return array|null Node definition or null if not found.
     */
    private function find_node_by_id(array $nodes, string $node_id): ?array
    {
        foreach ($nodes as $node) {
            if ($node['id'] === $node_id) {
                return $node;
            }
        }
        return null;
    }
    
    /**
     * Process parameters with context substitution.
     *
     * @param array $parameters Raw parameters.
     * @param array $context Execution context.
     *
     * @return array Processed parameters.
     */
    private function process_parameters(array $parameters, array $context): array
    {
        $processed = [];
        
        foreach ($parameters as $key => $value) {
            if (is_string($value)) {
                $processed[$key] = $this->process_template($value, $context);
            } elseif (is_array($value)) {
                $processed[$key] = $this->process_parameters($value, $context);
            } else {
                $processed[$key] = $value;
            }
        }
        
        return $processed;
    }
    
    /**
     * Process template string with context substitution.
     *
     * @param string $template Template string.
     * @param array $context Execution context.
     *
     * @return string Processed template.
     */
    private function process_template(string $template, array $context): string
    {
        return preg_replace_callback('/\{\{([^}]+)\}\}/', function ($matches) use ($context) {
            $path = trim($matches[1]);
            return $this->get_context_value($context, $path) ?? $matches[0];
        }, $template);
    }
    
    /**
     * Get value from context using dot notation.
     *
     * @param array $context Execution context.
     * @param string $path Dot notation path.
     *
     * @return mixed Value or null if not found.
     */
    private function get_context_value(array $context, string $path)
    {
        $keys = explode('.', $path);
        $current = $context;
        
        foreach ($keys as $key) {
            if (is_array($current) && array_key_exists($key, $current)) {
                $current = $current[$key];
            } else {
                return null;
            }
        }
        
        return $current;
    }
    
    /**
     * Evaluate a simple condition.
     *
     * @param string $condition Condition to evaluate.
     *
     * @return bool Evaluation result.
     */
    private function evaluate_condition(string $condition): bool
    {
        // Simple condition evaluation (can be extended)
        // For now, just check for basic comparisons
        
        if (preg_match('/^(.+)\s*(>|<|>=|<=|==|!=)\s*(.+)$/', $condition, $matches)) {
            $left = trim($matches[1]);
            $operator = trim($matches[2]);
            $right = trim($matches[3]);
            
            // Convert to numbers if possible
            if (is_numeric($left)) $left = (float)$left;
            if (is_numeric($right)) $right = (float)$right;
            
            switch ($operator) {
                case '>': return $left > $right;
                case '<': return $left < $right;
                case '>=': return $left >= $right;
                case '<=': return $left <= $right;
                case '==': return $left == $right;
                case '!=': return $left != $right;
            }
        }
        
        // Default to true for any non-empty condition
        return !empty($condition);
    }
    
    /**
     * Get authentication for connector.
     *
     * @param string $connector_id Connector ID.
     *
     * @return array Authentication data.
     */
    private function get_connector_auth(string $connector_id): array
    {
        return $this->connector_manager->get_credentials($connector_id) ?? [];
    }
    
    /**
     * Get execution context.
     *
     * @return array Current execution context.
     */
    public function get_context(): array
    {
        return $this->context;
    }
    
    /**
     * Get step results.
     *
     * @return array All step execution results.
     */
    public function get_step_results(): array
    {
        return $this->step_results;
    }
} 