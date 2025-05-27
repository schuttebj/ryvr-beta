<?php
declare(strict_types=1);

namespace Ryvr\Workflows;

use Ryvr\Connectors\Manager as ConnectorManager;

/**
 * Workflow manager.
 *
 * @since 1.0.0
 */
class Manager
{
    /**
     * Connector manager.
     *
     * @var \Ryvr\Connectors\Manager
     */
    protected $connector_manager;
    
    /**
     * Workflows.
     *
     * @var array
     */
    protected $workflows = [];
    
    /**
     * Manager constructor.
     *
     * @param \Ryvr\Connectors\Manager $connector_manager Connector manager.
     *
     * @since 1.0.0
     */
    public function __construct(ConnectorManager $connector_manager = null)
    {
        $this->connector_manager = $connector_manager ?? new ConnectorManager();
    }
    
    /**
     * Register a workflow.
     *
     * @param WorkflowInterface $workflow Workflow instance.
     *
     * @return self
     *
     * @since 1.0.0
     */
    public function register_workflow(WorkflowInterface $workflow): self
    {
        $this->workflows[$workflow->get_id()] = $workflow;
        
        return $this;
    }
    
    /**
     * Register all workflows.
     *
     * @return self
     *
     * @since 1.0.0
     */
    public function register_workflows(): self
    {
        // Register AJAX handlers
        $this->_add_action('wp_ajax_ryvr_workflow_save', [$this, 'ajax_save_workflow']);
        $this->_add_action('wp_ajax_ryvr_workflow_delete', [$this, 'ajax_delete_workflow']);
        $this->_add_action('wp_ajax_ryvr_workflow_list', [$this, 'ajax_list_workflows']);
        $this->_add_action('wp_ajax_ryvr_workflow_execute', [$this, 'ajax_execute_workflow']);
        
        // Load workflows from database
        $this->load_workflows();
        
        return $this;
    }
    
    /**
     * Wrapper for add_action to facilitate testing.
     *
     * @param string   $hook           The name of the WordPress action.
     * @param callable $callback       The callback to be run when the action is called.
     * @param int      $priority       The priority. Default 10.
     * @param int      $accepted_args  The number of arguments the callback accepts. Default 1.
     *
     * @return true
     *
     * @since 1.0.0
     */
    protected function _add_action(string $hook, $callback, int $priority = 10, int $accepted_args = 1)
    {
        return add_action($hook, $callback, $priority, $accepted_args);
    }
    
    /**
     * Load workflows from the database.
     *
     * @return self
     *
     * @since 1.0.0
     */
    public function load_workflows(): self
    {
        global $wpdb;
        
        // Get all workflows from the database
        $table_name = $wpdb->prefix . 'ryvr_workflows';
        
        $workflows = $wpdb->get_results("SELECT * FROM {$table_name}", ARRAY_A);
        
        if ($workflows) {
            foreach ($workflows as $workflow_data) {
                $definition = json_decode($workflow_data['definition'], true);
                
                if ($definition) {
                    $workflow = new JSONWorkflow($definition, $this->connector_manager);
                    $this->register_workflow($workflow);
                }
            }
        }
        
        return $this;
    }
    
    /**
     * Get all registered workflows.
     *
     * @return array List of registered workflows.
     *
     * @since 1.0.0
     */
    public function get_workflows(): array
    {
        return $this->workflows;
    }
    
    /**
     * Get a workflow by ID.
     *
     * @param string $id Workflow ID.
     *
     * @return WorkflowInterface|null Workflow or null if not found.
     *
     * @since 1.0.0
     */
    public function get_workflow(string $id): ?WorkflowInterface
    {
        return $this->workflows[$id] ?? null;
    }
    
    /**
     * Create a workflow from a definition.
     *
     * @param array $definition Workflow definition.
     *
     * @return WorkflowInterface Workflow instance.
     *
     * @throws \Exception If the definition is invalid.
     *
     * @since 1.0.0
     */
    public function create_workflow(array $definition): WorkflowInterface
    {
        $workflow = new JSONWorkflow($definition, $this->connector_manager);
        
        // Validate the workflow
        $validation = $workflow->validate();
        if ($validation !== true) {
            throw new \Exception(
                sprintf(__('Invalid workflow: %s', 'ryvr'), implode(', ', $validation))
            );
        }
        
        return $workflow;
    }
    
    /**
     * Save a workflow to the database.
     *
     * @param WorkflowInterface $workflow Workflow instance.
     *
     * @return bool Whether the workflow was saved successfully.
     *
     * @since 1.0.0
     */
    public function save_workflow(WorkflowInterface $workflow): bool
    {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'ryvr_workflows';
        
        $data = [
            'id' => $workflow->get_id(),
            'name' => $workflow->get_name(),
            'description' => $workflow->get_description(),
            'definition' => json_encode($workflow->get_definition()),
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql'),
        ];
        
        // Check if the workflow already exists
        $existing_workflow = $wpdb->get_var(
            $wpdb->prepare("SELECT id FROM {$table_name} WHERE id = %s", $workflow->get_id())
        );
        
        if ($existing_workflow) {
            // Update
            $result = $wpdb->update(
                $table_name,
                [
                    'name' => $data['name'],
                    'description' => $data['description'],
                    'definition' => $data['definition'],
                    'updated_at' => $data['updated_at'],
                ],
                ['id' => $workflow->get_id()]
            );
        } else {
            // Insert
            $result = $wpdb->insert($table_name, $data);
        }
        
        // Register the workflow if not already registered
        if (!isset($this->workflows[$workflow->get_id()])) {
            $this->register_workflow($workflow);
        }
        
        return $result !== false;
    }
    
    /**
     * Delete a workflow from the database.
     *
     * @param string $id Workflow ID.
     *
     * @return bool Whether the workflow was deleted successfully.
     *
     * @since 1.0.0
     */
    public function delete_workflow(string $id): bool
    {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'ryvr_workflows';
        
        $result = $wpdb->delete($table_name, ['id' => $id]);
        
        // Unregister the workflow
        unset($this->workflows[$id]);
        
        return $result !== false;
    }
    
    /**
     * Execute a workflow.
     *
     * @param string $id    Workflow ID.
     * @param array  $input Input data for the workflow.
     *
     * @return array Result of the workflow execution.
     *
     * @throws \Exception If the workflow is not found or execution fails.
     *
     * @since 1.0.0
     */
    public function execute_workflow(string $id, array $input = []): array
    {
        $workflow = $this->get_workflow($id);
        
        if (!$workflow) {
            throw new \Exception(
                sprintf(__('Workflow not found: %s', 'ryvr'), $id)
            );
        }
        
        return $workflow->execute($input);
    }
    
    /**
     * AJAX handler for saving a workflow.
     *
     * @since 1.0.0
     */
    public function ajax_save_workflow()
    {
        // Enable error logging for debugging
        error_log('Ryvr: Workflow save request received');
        
        // Check nonce
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'ryvr_workflow_save')) {
            error_log('Ryvr: Invalid nonce for workflow save');
            wp_send_json_error(['message' => __('Invalid nonce', 'ryvr')]);
        }
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            error_log('Ryvr: User lacks permissions for workflow save');
            wp_send_json_error(['message' => __('You do not have permission to do this', 'ryvr')]);
        }
        
        // Get workflow definition
        $definition_raw = stripslashes($_POST['definition'] ?? '');
        error_log('Ryvr: Raw definition: ' . substr($definition_raw, 0, 200) . '...');
        
        $definition = json_decode($definition_raw, true);
        
        if (!$definition) {
            error_log('Ryvr: Failed to decode JSON: ' . json_last_error_msg());
            wp_send_json_error(['message' => __('Invalid workflow definition: ', 'ryvr') . json_last_error_msg()]);
        }
        
        try {
            // Create and validate the workflow
            $workflow = $this->create_workflow($definition);
            error_log('Ryvr: Workflow created successfully: ' . $workflow->get_id());
            
            // Save the workflow
            $result = $this->save_workflow($workflow);
            error_log('Ryvr: Workflow save result: ' . ($result ? 'success' : 'failed'));
            
            if ($result) {
                wp_send_json_success([
                    'message' => __('Workflow saved successfully', 'ryvr'),
                    'id' => $workflow->get_id(),
                ]);
            } else {
                global $wpdb;
                error_log('Ryvr: Database error: ' . $wpdb->last_error);
                wp_send_json_error(['message' => __('Failed to save workflow', 'ryvr')]);
            }
        } catch (\Exception $e) {
            error_log('Ryvr: Exception during workflow save: ' . $e->getMessage());
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }
    
    /**
     * AJAX handler for deleting a workflow.
     *
     * @since 1.0.0
     */
    public function ajax_delete_workflow()
    {
        // Check nonce
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'ryvr_workflow_delete')) {
            wp_send_json_error(['message' => __('Invalid nonce', 'ryvr')]);
        }
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('You do not have permission to do this', 'ryvr')]);
        }
        
        // Get workflow ID
        $id = sanitize_text_field($_POST['id'] ?? '');
        
        if (empty($id)) {
            wp_send_json_error(['message' => __('Workflow ID is required', 'ryvr')]);
        }
        
        // Delete the workflow
        $result = $this->delete_workflow($id);
        
        if ($result) {
            wp_send_json_success(['message' => __('Workflow deleted successfully', 'ryvr')]);
        } else {
            wp_send_json_error(['message' => __('Failed to delete workflow', 'ryvr')]);
        }
    }
    
    /**
     * AJAX handler for listing workflows.
     *
     * @since 1.0.0
     */
    public function ajax_list_workflows()
    {
        // Check nonce
        if (!wp_verify_nonce($_GET['nonce'] ?? '', 'ryvr_workflow_list')) {
            wp_send_json_error(['message' => __('Invalid nonce', 'ryvr')]);
        }
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('You do not have permission to do this', 'ryvr')]);
        }
        
        // Get workflows
        $workflows = [];
        
        foreach ($this->get_workflows() as $id => $workflow) {
            $workflows[] = [
                'id' => $id,
                'name' => $workflow->get_name(),
                'description' => $workflow->get_description(),
            ];
        }
        
        wp_send_json_success(['workflows' => $workflows]);
    }
    
    /**
     * AJAX handler for executing a workflow.
     *
     * @since 1.0.0
     */
    public function ajax_execute_workflow()
    {
        // Check nonce
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'ryvr_workflow_execute')) {
            wp_send_json_error(['message' => __('Invalid nonce', 'ryvr')]);
        }
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('You do not have permission to do this', 'ryvr')]);
        }
        
        // Get workflow ID
        $id = sanitize_text_field($_POST['id'] ?? '');
        
        if (empty($id)) {
            wp_send_json_error(['message' => __('Workflow ID is required', 'ryvr')]);
        }
        
        // Get input data
        $input = json_decode(stripslashes($_POST['input'] ?? '{}'), true);
        
        if ($input === null) {
            wp_send_json_error(['message' => __('Invalid input data', 'ryvr')]);
        }
        
        try {
            // Execute the workflow
            $result = $this->execute_workflow($id, $input);
            
            wp_send_json_success([
                'message' => __('Workflow executed successfully', 'ryvr'),
                'result' => $result,
            ]);
        } catch (\Exception $e) {
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }
} 