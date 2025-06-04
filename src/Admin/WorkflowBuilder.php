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
        add_action('wp_ajax_ryvr_save_workflow', [$this, 'save_workflow']);
        add_action('wp_ajax_ryvr_load_workflow', [$this, 'load_workflow']);
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
        if (strpos($hook_suffix, 'ryvr-builder') === false) {
            return;
        }

        wp_enqueue_style(
            'ryvr-admin', 
            RYVR_PLUGIN_URL . 'assets/css/admin.css',
            [],
            RYVR_VERSION
        );

        wp_enqueue_script(
            'ryvr-workflow-builder',
            RYVR_PLUGIN_URL . 'assets/js/workflow-builder.js',
            ['jquery'],
            RYVR_VERSION,
            true
        );

        wp_localize_script('ryvr-workflow-builder', 'ryvrWorkflowBuilder', [
            'nonce' => wp_create_nonce('ryvr_workflow_builder'),
            'ajax_url' => admin_url('admin-ajax.php'),
        ]);
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
            
            fetch('/wp-admin/admin-ajax.php', {
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
            
            fetch('/wp-admin/admin-ajax.php', {
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
        <?php
    }

    /**
     * AJAX handler to get available connectors.
     *
     * @return void
     */
    public function get_connectors(): void
    {
        check_ajax_referer('ryvr_workflow_builder', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have permission to access this endpoint.', 'ryvr'));
        }

        try {
            // Use global connector manager
            global $ryvr_connector_manager;
            
            if (!$ryvr_connector_manager) {
                // Fallback: create manager if not available
                require_once RYVR_PLUGIN_DIR . 'src/Connectors/Manager.php';
                $ryvr_connector_manager = new \Ryvr\Connectors\Manager();
            }
            
            $connectors = [];
            $available_connectors = $ryvr_connector_manager->get_connectors();
            
            foreach ($available_connectors as $connector_id => $connector) {
                $connectors[$connector_id] = [
                    'metadata' => $connector->get_metadata(),
                    'actions' => $connector->get_actions()
                ];
            }

            wp_send_json_success($connectors);

        } catch (\Exception $e) {
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
            // Check if OpenAI connector exists and has auth configured
            if (!class_exists('\Ryvr\Connectors\OpenAI\OpenAIConnector')) {
                wp_send_json_error('OpenAI connector not available');
                return;
            }

            $openai = new \Ryvr\Connectors\OpenAI\OpenAIConnector();
            
            // Try to get models if auth is configured
            // For now, return default models
            $models = $openai->get_available_models();

            wp_send_json_success($models);

        } catch (\Exception $e) {
            // Fallback to default models
            $default_models = [
                ['id' => 'gpt-4o', 'name' => 'GPT-4o', 'category' => 'chat'],
                ['id' => 'gpt-4o-mini', 'name' => 'GPT-4o Mini', 'category' => 'chat'],
                ['id' => 'gpt-4-turbo', 'name' => 'GPT-4 Turbo', 'category' => 'chat'],
                ['id' => 'gpt-3.5-turbo', 'name' => 'GPT-3.5 Turbo', 'category' => 'chat'],
            ];
            
            wp_send_json_success($default_models);
        }
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
} 