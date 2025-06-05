<?php
declare(strict_types=1);

namespace Ryvr\Admin;

/**
 * Workflow Builder Admin Page
 *
 * @since 1.0.0
 */
class WorkflowBuilder
{
    /**
     * Register hooks and actions.
     *
     * @return void
     */
    public function register(): void
    {
        add_action('admin_menu', [$this, 'add_admin_page']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_scripts']);
        
        // AJAX endpoints
        add_action('wp_ajax_ryvr_get_connectors', [$this, 'get_connectors']);
        add_action('wp_ajax_ryvr_get_openai_models', [$this, 'get_openai_models']);
        add_action('wp_ajax_ryvr_get_sample_workflows', [$this, 'get_sample_workflows']);
        add_action('wp_ajax_ryvr_save_workflow', [$this, 'save_workflow']);
        add_action('wp_ajax_ryvr_load_workflow', [$this, 'load_workflow']);
        add_action('wp_ajax_ryvr_get_connector_output_schema', [$this, 'get_connector_output_schema']);
        add_action('wp_ajax_ryvr_get_connector_input_schema', [$this, 'get_connector_input_schema']);
        
        // Add debug logging
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Ryvr: WorkflowBuilder AJAX handlers registered');
        }
    }

    /**
     * Add admin page.
     *
     * @return void
     */
    public function add_admin_page(): void
    {
        add_submenu_page(
            'ryvr',
            __('Workflow Builder', 'ryvr'),
            __('Builder', 'ryvr'),
            'manage_options',
            'ryvr-builder',
            [$this, 'render_page']
        );
    }

    /**
     * Enqueue scripts and styles.
     *
     * @param string $hook_suffix
     * @return void
     */
    public function enqueue_scripts(string $hook_suffix): void
    {
        // Debug logging
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Ryvr: WorkflowBuilder enqueue_scripts called with hook: ' . $hook_suffix);
        }
        
        // Check if we're on the workflow builder page
        $is_builder_page = (
            strpos($hook_suffix, 'ryvr-builder') !== false ||
            strpos($hook_suffix, 'ryvr_page_ryvr-builder') !== false ||
            (isset($_GET['page']) && $_GET['page'] === 'ryvr-builder')
        );
        
        if (!$is_builder_page) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Ryvr: Not on workflow builder page, skipping script enqueue. Page: ' . ($_GET['page'] ?? 'none'));
            }
            return;
        }

        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Ryvr: Enqueueing workflow builder scripts and styles');
        }

        wp_enqueue_style(
            'ryvr-admin', 
            RYVR_PLUGIN_URL . 'assets/css/admin.css',
            [],
            RYVR_VERSION
        );

        // Enqueue field mapping styles
        wp_enqueue_style(
            'ryvr-field-mapping',
            RYVR_PLUGIN_URL . 'assets/css/field-mapping.css',
            ['ryvr-admin'],
            RYVR_VERSION
        );

        wp_enqueue_script(
            'ryvr-workflow-builder',
            RYVR_PLUGIN_URL . 'assets/js/workflow-builder.js',
            ['jquery'],
            RYVR_VERSION,
            true
        );

        // Enqueue field mapping functionality
        wp_enqueue_script(
            'ryvr-field-mapping',
            RYVR_PLUGIN_URL . 'assets/js/field-mapping.js',
            ['jquery', 'ryvr-workflow-builder'],
            RYVR_VERSION,
            true
        );

        // Localize script with debug logging
        $localize_data = [
            'nonce' => wp_create_nonce('ryvr_workflow_builder'),
            'ajax_url' => admin_url('admin-ajax.php'),
        ];
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Ryvr: Localizing script with data: ' . print_r($localize_data, true));
        }

        wp_localize_script('ryvr-workflow-builder', 'ryvrWorkflowBuilder', $localize_data);
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Ryvr: wp_localize_script completed');
        }
    }

    /**
     * Render the workflow builder page.
     *
     * @return void
     */
    public function render_page(): void
    {
        ?>
        <div class="ryvr-admin-wrap">
            <div class="ryvr-admin-header">
                <h1><?php _e('Workflow Builder', 'ryvr'); ?></h1>
                <p class="ryvr-subtitle">
                    <?php _e('Build automated marketing workflows using drag-and-drop tasks.', 'ryvr'); ?>
                </p>
                <div style="margin-top: 16px;">
                    <button type="button" class="ryvr-btn ryvr-btn-primary" onclick="saveWorkflow()">
                        💾 Save Workflow
                    </button>
                    <button type="button" class="ryvr-btn ryvr-btn-secondary" onclick="loadWorkflow()">
                        📁 Load Workflow
                    </button>
                    <button type="button" class="ryvr-btn ryvr-btn-secondary" onclick="loadSampleWorkflows()">
                        📋 Load Template
                    </button>
                    <button type="button" class="ryvr-btn ryvr-btn-secondary" onclick="exportWorkflow()">
                        📤 Export JSON
                    </button>
                </div>
            </div>

            <div class="ryvr-workflow-builder-container">
                <!-- Workflow builder will be initialized here by JavaScript -->
            </div>
        </div>

        <script>
        function saveWorkflow() {
            if (!ryvrWorkflowBuilderInstance) return;
            
            const workflowData = ryvrWorkflowBuilderInstance.exportWorkflow();
            const workflowName = prompt('Enter workflow name:');
            
            if (!workflowName) return;
            
            fetch(ryvrWorkflowBuilder.ajax_url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'ryvr_save_workflow',
                    nonce: ryvrWorkflowBuilder.nonce,
                    name: workflowName,
                    workflow_data: workflowData
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Workflow saved successfully!');
                } else {
                    alert('Failed to save workflow: ' + (data.data || 'Unknown error'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Failed to save workflow');
            });
        }
        
        function loadWorkflow() {
            const workflowId = prompt('Enter workflow ID to load:');
            if (!workflowId) return;
            
            fetch(ryvrWorkflowBuilder.ajax_url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'ryvr_load_workflow',
                    nonce: ryvrWorkflowBuilder.nonce,
                    workflow_id: workflowId
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success && ryvrWorkflowBuilderInstance) {
                    ryvrWorkflowBuilderInstance.loadWorkflow(data.data.workflow_data);
                    alert('Workflow loaded successfully!');
                } else {
                    alert('Failed to load workflow: ' + (data.data || 'Unknown error'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Failed to load workflow');
            });
        }
        
        function loadSampleWorkflows() {
            fetch(ryvrWorkflowBuilder.ajax_url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'ryvr_get_sample_workflows',
                    nonce: ryvrWorkflowBuilder.nonce
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showSampleWorkflowModal(data.data);
                } else {
                    alert('Failed to load sample workflows: ' + (data.data || 'Unknown error'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Failed to load sample workflows');
            });
        }
        
        function showSampleWorkflowModal(workflows) {
            // Create modal if it doesn't exist
            let modal = document.getElementById('ryvr-sample-workflows-modal');
            if (!modal) {
                modal = document.createElement('div');
                modal.id = 'ryvr-sample-workflows-modal';
                modal.className = 'ryvr-modal';
                modal.style.display = 'none';
                document.body.appendChild(modal);
            }
            
            const workflowOptions = Object.entries(workflows).map(([key, workflow]) => `
                <div class="sample-workflow-option" data-workflow-key="${key}">
                    <h4>${workflow.name}</h4>
                    <p>${workflow.description}</p>
                    <button class="ryvr-btn ryvr-btn-primary" onclick="loadSampleWorkflow('${key}')">Load Template</button>
                </div>
            `).join('');
            
            modal.innerHTML = `
                <div class="ryvr-modal-content">
                    <div class="ryvr-modal-header">
                        <h3>Select Workflow Template</h3>
                        <button class="ryvr-modal-close" onclick="closeSampleWorkflowModal()">&times;</button>
                    </div>
                    <div class="ryvr-modal-body">
                        <div class="sample-workflows-grid">
                            ${workflowOptions}
                        </div>
                    </div>
                </div>
            `;
            
            modal.style.display = 'block';
        }
        
        function loadSampleWorkflow(workflowKey) {
            fetch(ryvrWorkflowBuilder.ajax_url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'ryvr_get_sample_workflows',
                    nonce: ryvrWorkflowBuilder.nonce,
                    workflow_key: workflowKey
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success && ryvrWorkflowBuilderInstance) {
                    const workflow = data.data[workflowKey];
                    if (workflow) {
                        ryvrWorkflowBuilderInstance.loadWorkflow(JSON.stringify(workflow));
                        closeSampleWorkflowModal();
                        alert(`Template "${workflow.name}" loaded successfully!`);
                    }
                } else {
                    alert('Failed to load sample workflow: ' + (data.data || 'Unknown error'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Failed to load sample workflow');
            });
        }
        
        function closeSampleWorkflowModal() {
            const modal = document.getElementById('ryvr-sample-workflows-modal');
            if (modal) {
                modal.style.display = 'none';
            }
        }
        
        function exportWorkflow() {
            if (!ryvrWorkflowBuilderInstance) return;
            
            const workflowData = ryvrWorkflowBuilderInstance.exportWorkflow();
            const blob = new Blob([workflowData], { type: 'application/json' });
            const url = URL.createObjectURL(blob);
            
            const a = document.createElement('a');
            a.href = url;
            a.download = 'ryvr-workflow-' + Date.now() + '.json';
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            URL.revokeObjectURL(url);
        }
        </script>
        
        <!-- Debug Script for Localization Issues -->
        <script>
        console.log('=== Ryvr Workflow Builder Debug ===');
        console.log('ryvrWorkflowBuilder object:', typeof ryvrWorkflowBuilder !== 'undefined' ? ryvrWorkflowBuilder : 'UNDEFINED');
        console.log('jQuery loaded:', typeof jQuery !== 'undefined');
        console.log('Document ready state:', document.readyState);
        
        // Fallback localization if wp_localize_script failed
        if (typeof ryvrWorkflowBuilder === 'undefined') {
            console.warn('Creating fallback ryvrWorkflowBuilder object');
            window.ryvrWorkflowBuilder = {
                nonce: '<?php echo wp_create_nonce('ryvr_workflow_builder'); ?>',
                ajax_url: '<?php echo admin_url('admin-ajax.php'); ?>'
            };
        } else if (!ryvrWorkflowBuilder.nonce || !ryvrWorkflowBuilder.ajax_url) {
            console.warn('ryvrWorkflowBuilder exists but missing data, fixing...');
            ryvrWorkflowBuilder.nonce = '<?php echo wp_create_nonce('ryvr_workflow_builder'); ?>';
            ryvrWorkflowBuilder.ajax_url = '<?php echo admin_url('admin-ajax.php'); ?>';
        }
        
        // Try to detect if scripts are loading
        document.addEventListener('DOMContentLoaded', function() {
            console.log('DOM Content Loaded');
            console.log('ryvrWorkflowBuilder after DOM load:', typeof ryvrWorkflowBuilder !== 'undefined' ? ryvrWorkflowBuilder : 'STILL UNDEFINED');
            
            if (typeof ryvrWorkflowBuilder === 'undefined') {
                console.error('PROBLEM: ryvrWorkflowBuilder object not found!');
                console.log('This means wp_localize_script failed. Check:');
                console.log('1. Script is being enqueued');
                console.log('2. Script handle matches wp_localize_script');
                console.log('3. Hook priority issues');
            } else {
                console.log('✓ ryvrWorkflowBuilder object available:', ryvrWorkflowBuilder);
            }
        });
        </script>
        <?php
    }

    /**
     * AJAX handler to get available connectors.
     *
     * @return void
     */
    public function get_connectors(): void
    {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Ryvr: get_connectors AJAX handler called');
            error_log('Ryvr: POST data: ' . print_r($_POST, true));
        }
        
        // Check if nonce is provided
        if (!isset($_POST['nonce'])) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Ryvr: No nonce provided in request');
            }
            wp_send_json_error('No nonce provided');
            return;
        }
        
        // Check nonce
        if (!wp_verify_nonce($_POST['nonce'], 'ryvr_workflow_builder')) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Ryvr: Nonce verification failed. Provided: ' . $_POST['nonce']);
            }
            wp_send_json_error('Invalid nonce');
            return;
        }

        if (!current_user_can('manage_options')) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Ryvr: User does not have manage_options capability');
            }
            wp_send_json_error('Insufficient permissions');
            return;
        }

        try {
            // Use global connector manager
            global $ryvr_connector_manager;
            
            if (!$ryvr_connector_manager) {
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log('Ryvr: Global connector manager not found, creating fallback');
                }
                // Fallback: create manager if not available
                require_once RYVR_PLUGIN_DIR . 'src/Connectors/Manager.php';
                $ryvr_connector_manager = new \Ryvr\Connectors\Manager();
            }
            
            $connectors = [];
            $available_connectors = $ryvr_connector_manager->get_connectors();
            
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Ryvr: Found ' . count($available_connectors) . ' connectors');
            }
            
            foreach ($available_connectors as $connector_id => $connector) {
                $connectors[$connector_id] = [
                    'metadata' => $connector->get_metadata(),
                    'actions' => $connector->get_actions()
                ];
            }

            wp_send_json_success($connectors);

        } catch (\Exception $e) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Ryvr: Exception in get_connectors: ' . $e->getMessage());
                error_log('Ryvr: Exception trace: ' . $e->getTraceAsString());
            }
            wp_send_json_error('Failed to load connectors: ' . $e->getMessage());
        }
    }

    /**
     * AJAX handler to get available OpenAI models.
     *
     * @return void
     */
    public function get_openai_models(): void
    {
        check_ajax_referer('ryvr_workflow_builder', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have permission to access this endpoint.', 'ryvr'));
        }

        // Always return default models for now until OpenAI connector is properly configured
        $default_models = [
            ['id' => 'gpt-4o', 'name' => 'GPT-4o', 'category' => 'chat'],
            ['id' => 'gpt-4o-mini', 'name' => 'GPT-4o Mini', 'category' => 'chat'],
            ['id' => 'gpt-4-turbo', 'name' => 'GPT-4 Turbo', 'category' => 'chat'],
            ['id' => 'gpt-3.5-turbo', 'name' => 'GPT-3.5 Turbo', 'category' => 'chat'],
            ['id' => 'gpt-4', 'name' => 'GPT-4', 'category' => 'chat'],
            ['id' => 'text-davinci-003', 'name' => 'Text Davinci 003', 'category' => 'completion'],
        ];
        
        try {
            // Future: Try to get live models if OpenAI is configured
            // For now, just return the default models
            wp_send_json_success($default_models);

        } catch (\Exception $e) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Ryvr: Error loading OpenAI models: ' . $e->getMessage());
            }
            
            // Always fallback to default models
            wp_send_json_success($default_models);
        }
    }

    /**
     * AJAX handler to get sample workflows.
     *
     * @return void
     */
    public function get_sample_workflows(): void
    {
        check_ajax_referer('ryvr_workflow_builder', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have permission to access this endpoint.', 'ryvr'));
        }

        try {
            $sample_workflows_file = RYVR_PLUGIN_DIR . 'examples/sample-workflows.json';
            
            if (!file_exists($sample_workflows_file)) {
                wp_send_json_error('Sample workflows file not found');
                return;
            }
            
            $sample_workflows_content = file_get_contents($sample_workflows_file);
            $sample_workflows = json_decode($sample_workflows_content, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                wp_send_json_error('Invalid sample workflows JSON: ' . json_last_error_msg());
                return;
            }
            
            // Convert old format to new workflow builder format
            $converted_workflows = [];
            foreach ($sample_workflows as $key => $workflow) {
                $converted_workflows[$key] = $this->convertWorkflowFormat($workflow);
            }
            
            // If a specific workflow key is requested, return just that workflow
            $workflow_key = sanitize_text_field($_POST['workflow_key'] ?? '');
            if (!empty($workflow_key) && isset($converted_workflows[$workflow_key])) {
                wp_send_json_success([$workflow_key => $converted_workflows[$workflow_key]]);
                return;
            }
            
            wp_send_json_success($converted_workflows);

        } catch (\Exception $e) {
            wp_send_json_error('Failed to load sample workflows: ' . $e->getMessage());
        }
    }
    
    /**
     * Convert old workflow format to new node-based format.
     *
     * @param array $workflow
     * @return array
     */
    private function convertWorkflowFormat(array $workflow): array
    {
        $nodes = [];
        $connections = [];
        
        if (isset($workflow['steps'])) {
            $x = 100;
            $y = 100;
            
            foreach ($workflow['steps'] as $step) {
                $nodeId = 'node-' . $step['id'] . '-' . time() . '-' . rand(1000, 9999);
                
                $nodes[] = [
                    'id' => $nodeId,
                    'connectorId' => $step['connector'],
                    'actionId' => $step['action'],
                    'x' => $x,
                    'y' => $y,
                    'parameters' => $step['params'] ?? [],
                    'originalStepId' => $step['id'] // Keep reference for edge mapping
                ];
                
                $x += 300; // Space nodes horizontally
                if ($x > 800) {
                    $x = 100;
                    $y += 200;
                }
            }
            
            // Convert edges to connections
            if (isset($workflow['edges'])) {
                foreach ($workflow['edges'] as $edge) {
                    $sourceStepId = $edge[0];
                    $targetStepId = $edge[1];
                    
                    // Find the corresponding node IDs
                    $sourceNodeId = null;
                    $targetNodeId = null;
                    
                    foreach ($nodes as $node) {
                        if ($node['originalStepId'] === $sourceStepId) {
                            $sourceNodeId = $node['id'];
                        }
                        if ($node['originalStepId'] === $targetStepId) {
                            $targetNodeId = $node['id'];
                        }
                    }
                    
                    if ($sourceNodeId && $targetNodeId) {
                        $connections[] = [
                            'id' => "connection-{$sourceNodeId}-{$targetNodeId}",
                            'source' => $sourceNodeId,
                            'target' => $targetNodeId,
                            'mapping' => []
                        ];
                    }
                }
            }
            
            // Remove the originalStepId from nodes
            foreach ($nodes as &$node) {
                unset($node['originalStepId']);
            }
        }
        
        return [
            'id' => $workflow['id'],
            'name' => $workflow['name'],
            'description' => $workflow['description'],
            'nodes' => $nodes,
            'connections' => $connections
        ];
    }

    /**
     * AJAX handler to save a workflow.
     *
     * @return void
     */
    public function save_workflow(): void
    {
        check_ajax_referer('ryvr_workflow_builder', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have permission to access this endpoint.', 'ryvr'));
        }

        $name = sanitize_text_field($_POST['name'] ?? '');
        $workflow_data = wp_unslash($_POST['workflow_data'] ?? '');

        if (empty($name) || empty($workflow_data)) {
            wp_send_json_error('Missing required fields');
            return;
        }

        // Validate JSON
        $parsed = json_decode($workflow_data, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            wp_send_json_error('Invalid JSON data');
            return;
        }

        try {
            global $wpdb;
            
            $workflow_id = sanitize_title($name) . '-' . time();
            
            $result = $wpdb->insert(
                $wpdb->prefix . 'ryvr_workflows',
                [
                    'id' => $workflow_id,
                    'name' => $name,
                    'description' => 'Workflow created via builder',
                    'definition' => $workflow_data,
                    'status' => 'draft',
                    'user_id' => get_current_user_id(),
                    'created_at' => current_time('mysql'),
                    'updated_at' => current_time('mysql')
                ],
                ['%s', '%s', '%s', '%s', '%s', '%d', '%s', '%s']
            );

            if ($result === false) {
                wp_send_json_error('Failed to save workflow to database');
                return;
            }

            wp_send_json_success([
                'workflow_id' => $workflow_id,
                'message' => 'Workflow saved successfully'
            ]);

        } catch (\Exception $e) {
            wp_send_json_error('Database error: ' . $e->getMessage());
        }
    }

    /**
     * AJAX handler to load a workflow.
     *
     * @return void
     */
    public function load_workflow(): void
    {
        check_ajax_referer('ryvr_workflow_builder', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have permission to access this endpoint.', 'ryvr'));
        }

        $workflow_id = sanitize_text_field($_POST['workflow_id'] ?? '');

        if (empty($workflow_id)) {
            wp_send_json_error('Missing workflow ID');
            return;
        }

        try {
            global $wpdb;
            
            $workflow = $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT * FROM {$wpdb->prefix}ryvr_workflows WHERE id = %s",
                    $workflow_id
                )
            );

            if (!$workflow) {
                wp_send_json_error('Workflow not found');
                return;
            }

            wp_send_json_success([
                'workflow_id' => $workflow->id,
                'name' => $workflow->name,
                'description' => $workflow->description,
                'workflow_data' => $workflow->definition,
                'status' => $workflow->status
            ]);

        } catch (\Exception $e) {
            wp_send_json_error('Database error: ' . $e->getMessage());
        }
    }

    /**
     * Get connector output schema for field mapping.
     *
     * @return void
     */
    public function get_connector_output_schema(): void
    {
        check_ajax_referer('ryvr_workflow_builder', 'nonce');

        $connector_id = sanitize_text_field($_POST['connector_id'] ?? '');
        $action_id = sanitize_text_field($_POST['action_id'] ?? '');

        if (empty($connector_id) || empty($action_id)) {
            wp_send_json_error('Missing connector ID or action ID');
            return;
        }

        try {
            $connector_manager = new \Ryvr\Connectors\Manager();
            $connector = $connector_manager->get_connector($connector_id);

            if (!$connector) {
                wp_send_json_error('Connector not found');
                return;
            }

            $actions = $connector->get_actions();
            if (!isset($actions[$action_id])) {
                wp_send_json_error('Action not found');
                return;
            }

            $action = $actions[$action_id];
            $output_schema = $action['output_schema'] ?? [];

            // If output schema is available, extract field types
            if (!empty($output_schema) && isset($output_schema['properties'])) {
                $fields = [];
                $this->extract_schema_fields($output_schema['properties'], $fields);
                wp_send_json_success($fields);
            } else {
                // Default fallback schema
                wp_send_json_success([
                    'data' => 'object',
                    'status' => 'string',
                    'message' => 'string',
                    'success' => 'boolean'
                ]);
            }

        } catch (\Exception $e) {
            wp_send_json_error('Error getting output schema: ' . $e->getMessage());
        }
    }

    /**
     * Get connector input schema for field mapping.
     *
     * @return void
     */
    public function get_connector_input_schema(): void
    {
        check_ajax_referer('ryvr_workflow_builder', 'nonce');

        $connector_id = sanitize_text_field($_POST['connector_id'] ?? '');
        $action_id = sanitize_text_field($_POST['action_id'] ?? '');

        if (empty($connector_id) || empty($action_id)) {
            wp_send_json_error('Missing connector ID or action ID');
            return;
        }

        try {
            $connector_manager = new \Ryvr\Connectors\Manager();
            $connector = $connector_manager->get_connector($connector_id);

            if (!$connector) {
                wp_send_json_error('Connector not found');
                return;
            }

            $actions = $connector->get_actions();
            if (!isset($actions[$action_id])) {
                wp_send_json_error('Action not found');
                return;
            }

            $action = $actions[$action_id];
            $fields = $action['fields'] ?? [];

            // Convert action fields to schema format
            $input_fields = [];
            foreach ($fields as $field_id => $field_config) {
                $field_type = $field_config['type'] ?? 'string';
                $input_fields[$field_id] = $field_type;
            }

            wp_send_json_success($input_fields);

        } catch (\Exception $e) {
            wp_send_json_error('Error getting input schema: ' . $e->getMessage());
        }
    }

    /**
     * Extract fields from schema properties recursively.
     *
     * @param array $properties Schema properties.
     * @param array &$fields Fields array to populate.
     * @param string $prefix Field prefix for nested properties.
     *
     * @return void
     */
    private function extract_schema_fields(array $properties, array &$fields, string $prefix = ''): void
    {
        foreach ($properties as $property_name => $property_config) {
            $field_name = $prefix ? $prefix . '.' . $property_name : $property_name;
            $field_type = $property_config['type'] ?? 'string';

            if ($field_type === 'object' && isset($property_config['properties'])) {
                // Recursively extract nested object properties
                $this->extract_schema_fields($property_config['properties'], $fields, $field_name);
            } elseif ($field_type === 'array' && isset($property_config['items']['properties'])) {
                // Handle array of objects
                $this->extract_schema_fields($property_config['items']['properties'], $fields, $field_name . '[]');
            } else {
                $fields[$field_name] = $field_type;
            }
        }
    }
} 