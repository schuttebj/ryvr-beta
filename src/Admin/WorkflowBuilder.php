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
        add_action('wp_ajax_ryvr_test_task', [$this, 'test_task']);
        add_action('wp_ajax_ryvr_generate_json_schema', [$this, 'generate_json_schema']);
        
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

        try {
            // Try to get models from OpenAI connector
            global $ryvr_connector_manager;
            
            if ($ryvr_connector_manager) {
                $connectors = $ryvr_connector_manager->get_connectors();
                
                if (isset($connectors['openai'])) {
                    $openai_connector = $connectors['openai'];
                    $models = $openai_connector->get_available_models();
                    
                    if (!empty($models)) {
                        wp_send_json_success($models);
                        return;
                    }
                }
            }

            // Fallback to default models
            $default_models = [
                ['id' => 'gpt-4o', 'name' => 'GPT-4o', 'category' => 'chat'],
                ['id' => 'gpt-4o-mini', 'name' => 'GPT-4o Mini', 'category' => 'chat'],
                ['id' => 'gpt-4-turbo', 'name' => 'GPT-4 Turbo', 'category' => 'chat'],
                ['id' => 'gpt-3.5-turbo', 'name' => 'GPT-3.5 Turbo', 'category' => 'chat'],
                ['id' => 'gpt-4', 'name' => 'GPT-4', 'category' => 'chat'],
                ['id' => 'text-embedding-3-large', 'name' => 'Text Embedding 3 Large', 'category' => 'embeddings'],
                ['id' => 'text-embedding-3-small', 'name' => 'Text Embedding 3 Small', 'category' => 'embeddings'],
                ['id' => 'dall-e-3', 'name' => 'DALL-E 3', 'category' => 'image'],
                ['id' => 'dall-e-2', 'name' => 'DALL-E 2', 'category' => 'image'],
                ['id' => 'whisper-1', 'name' => 'Whisper', 'category' => 'audio'],
                ['id' => 'tts-1', 'name' => 'TTS', 'category' => 'audio'],
                ['id' => 'tts-1-hd', 'name' => 'TTS HD', 'category' => 'audio'],
                ['id' => 'text-davinci-003', 'name' => 'Text Davinci 003', 'category' => 'completion'],
            ];
            
            wp_send_json_success($default_models);

        } catch (\Exception $e) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Ryvr: Error loading OpenAI models: ' . $e->getMessage());
            }
            
            // Fallback to default models on error
            $default_models = [
                ['id' => 'gpt-4o', 'name' => 'GPT-4o', 'category' => 'chat'],
                ['id' => 'gpt-4o-mini', 'name' => 'GPT-4o Mini', 'category' => 'chat'],
                ['id' => 'gpt-4-turbo', 'name' => 'GPT-4 Turbo', 'category' => 'chat'],
                ['id' => 'gpt-3.5-turbo', 'name' => 'GPT-3.5 Turbo', 'category' => 'chat'],
                ['id' => 'gpt-4', 'name' => 'GPT-4', 'category' => 'chat'],
                ['id' => 'text-embedding-3-large', 'name' => 'Text Embedding 3 Large', 'category' => 'embeddings'],
                ['id' => 'text-embedding-3-small', 'name' => 'Text Embedding 3 Small', 'category' => 'embeddings'],
                ['id' => 'dall-e-3', 'name' => 'DALL-E 3', 'category' => 'image'],
                ['id' => 'dall-e-2', 'name' => 'DALL-E 2', 'category' => 'image'],
                ['id' => 'whisper-1', 'name' => 'Whisper', 'category' => 'audio'],
                ['id' => 'tts-1', 'name' => 'TTS', 'category' => 'audio'],
                ['id' => 'tts-1-hd', 'name' => 'TTS HD', 'category' => 'audio'],
                ['id' => 'text-davinci-003', 'name' => 'Text Davinci 003', 'category' => 'completion'],
            ];
            
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

    /**
     * AJAX handler to test a task.
     *
     * @return void
     */
    public function test_task(): void
    {
        check_ajax_referer('ryvr_workflow_builder', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have permission to access this endpoint.', 'ryvr'));
        }

        $connector_id = sanitize_text_field($_POST['connector_id'] ?? '');
        $action_id = sanitize_text_field($_POST['action_id'] ?? '');
        $parameters = isset($_POST['parameters']) ? (array) $_POST['parameters'] : [];
        $test_mode = isset($_POST['test_mode']) && $_POST['test_mode'];

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

            // In test mode, return sample data instead of actual execution
            if ($test_mode) {
                $sample_data = $this->generate_sample_data($connector_id, $action_id, $actions[$action_id]);
                wp_send_json_success($sample_data);
                return;
            }

            // For actual execution, try to execute the real action
            try {
                $start_time = microtime(true);
                
                if ($connector_id === 'openai') {
                    $result = $this->execute_openai_action($action_id, $parameters);
                } else {
                    // For other connectors, execute actual action if connector supports it
                    $auth_credentials = $this->get_connector_auth($connector_id);
                    if ($auth_credentials) {
                        $result = $connector->execute_action($action_id, $parameters, $auth_credentials);
                    } else {
                        // Fallback to sample data if no auth
                        $result = $this->generate_sample_data($connector_id, $action_id, $actions[$action_id]);
                    }
                }
                
                $execution_time = round((microtime(true) - $start_time) * 1000, 2) . 'ms';
                
                wp_send_json_success([
                    'status' => 'success',
                    'message' => 'Test execution completed',
                    'data' => $result,
                    'execution_time' => $execution_time,
                    'test_mode' => false
                ]);
                
            } catch (\Exception $e) {
                // If real execution fails, return error with sample data
                wp_send_json_success([
                    'status' => 'error',
                    'message' => $e->getMessage(),
                    'data' => $this->generate_sample_data($connector_id, $action_id, $actions[$action_id]),
                    'execution_time' => '0ms',
                    'test_mode' => true,
                    'note' => 'Returned sample data due to execution error'
                ]);
            }

        } catch (\Exception $e) {
            wp_send_json_error('Test execution failed: ' . $e->getMessage());
        }
    }

    /**
     * Execute OpenAI action with real parameters.
     *
     * @param string $action_id   Action ID.
     * @param array  $parameters  Action parameters.
     *
     * @return array API response.
     */
    private function execute_openai_action(string $action_id, array $parameters): array
    {
        global $ryvr_connector_manager;
        
        if (!$ryvr_connector_manager) {
            throw new \Exception('Connector manager not available');
        }
        
        $connectors = $ryvr_connector_manager->get_connectors();
        if (!isset($connectors['openai'])) {
            throw new \Exception('OpenAI connector not available');
        }
        
        $openai_connector = $connectors['openai'];
        $auth_credentials = $this->get_connector_auth('openai');
        
        if (!$auth_credentials) {
            throw new \Exception('OpenAI API credentials not configured. Please configure your API key in the settings.');
        }
        
        // Process and validate parameters
        $processed_params = $this->process_openai_parameters($action_id, $parameters);
        
        return $openai_connector->execute_action($action_id, $processed_params, $auth_credentials);
    }
    
    /**
     * Process OpenAI parameters for API call.
     *
     * @param string $action_id   Action ID.
     * @param array  $parameters  Raw parameters from form.
     *
     * @return array Processed parameters.
     */
    private function process_openai_parameters(string $action_id, array $parameters): array
    {
        $processed = [];
        
        foreach ($parameters as $key => $value) {
            if ($value === '' || $value === null) {
                continue; // Skip empty values
            }
            
            // Handle JSON parsing for messages
            if ($key === 'messages' && is_string($value)) {
                try {
                    $processed[$key] = json_decode($value, true);
                    if (json_last_error() !== JSON_ERROR_NONE) {
                        throw new \Exception('Invalid JSON format for messages parameter');
                    }
                } catch (\Exception $e) {
                    throw new \Exception('Messages parameter must be valid JSON: ' . $e->getMessage());
                }
            } else {
                $processed[$key] = $value;
            }
        }
        
        return $processed;
    }
    
    /**
     * Get authentication credentials for a connector.
     *
     * @param string $connector_id Connector ID.
     *
     * @return array|null Authentication credentials or null if not configured.
     */
    private function get_connector_auth(string $connector_id): ?array
    {
        // This would typically get credentials from WordPress options/settings
        // For now, we'll try to get them from the settings system
        
        $settings = get_option('ryvr_settings', []);
        
        if (isset($settings['connectors'][$connector_id])) {
            return $settings['connectors'][$connector_id];
        }
        
        // Try alternative storage location
        $connector_settings = get_option("ryvr_{$connector_id}_settings");
        if ($connector_settings) {
            return $connector_settings;
        }
        
        return null;
    }

    /**
     * AJAX handler to generate JSON schema using AI.
     *
     * @return void
     */
    public function generate_json_schema(): void
    {
        check_ajax_referer('ryvr_workflow_builder', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have permission to access this endpoint.', 'ryvr'));
        }

        $prompt = sanitize_textarea_field($_POST['prompt'] ?? '');

        if (empty($prompt)) {
            wp_send_json_error('Prompt is required');
            return;
        }

        try {
            // Try to use OpenAI to generate the schema
            $schema = $this->generate_schema_with_ai($prompt);
            wp_send_json_success($schema);
        } catch (\Exception $e) {
            // Fallback to predefined schemas based on keywords
            $schema = $this->generate_schema_fallback($prompt);
            wp_send_json_success($schema);
        }
    }

    /**
     * Generate JSON schema using OpenAI.
     *
     * @param string $prompt User prompt describing desired output.
     *
     * @return array JSON schema.
     */
    private function generate_schema_with_ai(string $prompt): array
    {
        global $ryvr_connector_manager;
        
        if (!$ryvr_connector_manager) {
            throw new \Exception('Connector manager not available');
        }
        
        $connectors = $ryvr_connector_manager->get_connectors();
        if (!isset($connectors['openai'])) {
            throw new \Exception('OpenAI connector not available');
        }
        
        $openai_connector = $connectors['openai'];
        $auth_credentials = $this->get_connector_auth('openai');
        
        if (!$auth_credentials) {
            throw new \Exception('OpenAI not configured');
        }

        $messages = [
            [
                'role' => 'system',
                'content' => 'You are a JSON schema generator. Create a valid JSON schema (type: object) based on the user\'s description. Return only the JSON schema, no explanations. The schema should include "type", "properties", and optionally "required" fields.'
            ],
            [
                'role' => 'user',
                'content' => "Create a JSON schema for: {$prompt}"
            ]
        ];

        $response = $openai_connector->execute_action('chat_completion', [
            'model' => 'gpt-4o-mini',
            'messages' => $messages,
            'temperature' => 0.3,
            'max_tokens' => 1000
        ], $auth_credentials);

        if (!isset($response['choices'][0]['message']['content'])) {
            throw new \Exception('No response from OpenAI');
        }

        $schema_json = trim($response['choices'][0]['message']['content']);
        
        // Remove markdown code blocks if present
        $schema_json = preg_replace('/^```json\s*/', '', $schema_json);
        $schema_json = preg_replace('/\s*```$/', '', $schema_json);
        
        $schema = json_decode($schema_json, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception('Invalid JSON from AI response');
        }

        return $schema;
    }

    /**
     * Generate fallback schema based on keywords.
     *
     * @param string $prompt User prompt.
     *
     * @return array JSON schema.
     */
    private function generate_schema_fallback(string $prompt): array
    {
        $prompt_lower = strtolower($prompt);
        
        // Blog post schema
        if (strpos($prompt_lower, 'blog') !== false || strpos($prompt_lower, 'post') !== false || strpos($prompt_lower, 'article') !== false) {
            return [
                'type' => 'object',
                'properties' => [
                    'title' => ['type' => 'string'],
                    'content' => ['type' => 'string'],
                    'summary' => ['type' => 'string'],
                    'tags' => ['type' => 'array', 'items' => ['type' => 'string']],
                    'category' => ['type' => 'string'],
                    'author' => ['type' => 'string']
                ],
                'required' => ['title', 'content']
            ];
        }
        
        // SEO keywords schema
        if (strpos($prompt_lower, 'keyword') !== false || strpos($prompt_lower, 'seo') !== false) {
            return [
                'type' => 'object',
                'properties' => [
                    'keywords' => [
                        'type' => 'array',
                        'items' => [
                            'type' => 'object',
                            'properties' => [
                                'keyword' => ['type' => 'string'],
                                'search_volume' => ['type' => 'number'],
                                'difficulty' => ['type' => 'number'],
                                'intent' => ['type' => 'string']
                            ]
                        ]
                    ],
                    'total_keywords' => ['type' => 'number']
                ],
                'required' => ['keywords']
            ];
        }
        
        // Product schema
        if (strpos($prompt_lower, 'product') !== false || strpos($prompt_lower, 'item') !== false) {
            return [
                'type' => 'object',
                'properties' => [
                    'name' => ['type' => 'string'],
                    'description' => ['type' => 'string'],
                    'price' => ['type' => 'number'],
                    'currency' => ['type' => 'string'],
                    'category' => ['type' => 'string'],
                    'in_stock' => ['type' => 'boolean'],
                    'rating' => ['type' => 'number']
                ],
                'required' => ['name', 'price']
            ];
        }
        
        // Generic list schema
        if (strpos($prompt_lower, 'list') !== false || strpos($prompt_lower, 'array') !== false) {
            return [
                'type' => 'object',
                'properties' => [
                    'items' => [
                        'type' => 'array',
                        'items' => [
                            'type' => 'object',
                            'properties' => [
                                'name' => ['type' => 'string'],
                                'value' => ['type' => 'string'],
                                'category' => ['type' => 'string']
                            ]
                        ]
                    ],
                    'total_count' => ['type' => 'number']
                ],
                'required' => ['items']
            ];
        }
        
        // Default generic schema
        return [
            'type' => 'object',
            'properties' => [
                'data' => ['type' => 'string'],
                'status' => ['type' => 'string'],
                'timestamp' => ['type' => 'string']
            ],
            'required' => ['data']
        ];
    }

    /**
     * Generate sample data for testing purposes.
     *
     * @param string $connector_id Connector ID.
     * @param string $action_id Action ID.
     * @param array $action Action configuration.
     *
     * @return array Sample data.
     */
    private function generate_sample_data(string $connector_id, string $action_id, array $action): array
    {
        // Generate connector-specific sample data
        switch ($connector_id) {
            case 'openai':
                return $this->generate_openai_sample($action_id);
            case 'google_analytics':
                return $this->generate_ga_sample($action_id);
            case 'wordpress':
                return $this->generate_wp_sample($action_id);
            case 'ahrefs':
                return $this->generate_ahrefs_sample($action_id);
            default:
                return $this->generate_generic_sample($action_id);
        }
    }

    /**
     * Generate OpenAI-specific sample data.
     *
     * @param string $action_id Action ID.
     *
     * @return array Sample data.
     */
    private function generate_openai_sample(string $action_id): array
    {
        switch ($action_id) {
            case 'chat_completion':
                return [
                    'id' => 'chatcmpl-test123',
                    'object' => 'chat.completion',
                    'created' => time(),
                    'model' => 'gpt-4',
                    'choices' => [
                        [
                            'index' => 0,
                            'message' => [
                                'role' => 'assistant',
                                'content' => 'This is a sample response from OpenAI.'
                            ],
                            'finish_reason' => 'stop'
                        ]
                    ],
                    'usage' => [
                        'prompt_tokens' => 20,
                        'completion_tokens' => 12,
                        'total_tokens' => 32
                    ]
                ];
            case 'embeddings':
                return [
                    'object' => 'list',
                    'data' => [
                        [
                            'object' => 'embedding',
                            'embedding' => array_fill(0, 1536, 0.123456),
                            'index' => 0
                        ]
                    ],
                    'model' => 'text-embedding-ada-002',
                    'usage' => [
                        'prompt_tokens' => 8,
                        'total_tokens' => 8
                    ]
                ];
            default:
                return ['content' => 'Sample OpenAI response'];
        }
    }

    /**
     * Generate Google Analytics sample data.
     *
     * @param string $action_id Action ID.
     *
     * @return array Sample data.
     */
    private function generate_ga_sample(string $action_id): array
    {
        return [
            'reports' => [
                [
                    'columnHeader' => [
                        'dimensions' => ['ga:date'],
                        'metricHeader' => [
                            'metricHeaderEntries' => [
                                ['name' => 'ga:sessions', 'type' => 'INTEGER'],
                                ['name' => 'ga:users', 'type' => 'INTEGER']
                            ]
                        ]
                    ],
                    'data' => [
                        'rows' => [
                            [
                                'dimensions' => ['20240115'],
                                'metrics' => [
                                    ['values' => ['1234', '987']]
                                ]
                            ]
                        ],
                        'totals' => [
                            ['values' => ['1234', '987']]
                        ]
                    ]
                ]
            ]
        ];
    }

    /**
     * Generate WordPress sample data.
     *
     * @param string $action_id Action ID.
     *
     * @return array Sample data.
     */
    private function generate_wp_sample(string $action_id): array
    {
        switch ($action_id) {
            case 'create_post':
                return [
                    'ID' => 123,
                    'post_title' => 'Sample Post Title',
                    'post_content' => 'Sample post content',
                    'post_status' => 'publish',
                    'post_type' => 'post',
                    'post_author' => 1,
                    'post_date' => current_time('mysql'),
                    'guid' => home_url('/?p=123'),
                    'post_name' => 'sample-post-title'
                ];
            case 'get_posts':
                return [
                    [
                        'ID' => 123,
                        'post_title' => 'Sample Post 1',
                        'post_excerpt' => 'Sample excerpt...',
                        'post_status' => 'publish',
                        'post_date' => '2024-01-15 10:30:00'
                    ],
                    [
                        'ID' => 124,
                        'post_title' => 'Sample Post 2',
                        'post_excerpt' => 'Another excerpt...',
                        'post_status' => 'publish',
                        'post_date' => '2024-01-14 15:45:00'
                    ]
                ];
            default:
                return ['success' => true, 'message' => 'WordPress action completed'];
        }
    }

    /**
     * Generate Ahrefs sample data.
     *
     * @param string $action_id Action ID.
     *
     * @return array Sample data.
     */
    private function generate_ahrefs_sample(string $action_id): array
    {
        return [
            'domain' => 'example.com',
            'ahrefs_rank' => 12345,
            'domain_rating' => 65,
            'backlinks' => 89543,
            'referring_domains' => 2341,
            'organic_keywords' => 45678,
            'organic_traffic' => 123456
        ];
    }

    /**
     * Generate generic sample data.
     *
     * @param string $action_id Action ID.
     *
     * @return array Sample data.
     */
    private function generate_generic_sample(string $action_id): array
    {
        return [
            'action_id' => $action_id,
            'status' => 'success',
            'timestamp' => current_time('c'),
            'data' => [
                'id' => 'sample_' . uniqid(),
                'title' => 'Sample Data Title',
                'description' => 'This is sample data for testing purposes',
                'value' => 42,
                'tags' => ['tag1', 'tag2', 'tag3'],
                'metadata' => [
                    'source' => 'test',
                    'type' => 'sample',
                    'priority' => 'normal'
                ]
            ]
        ];
    }
} 