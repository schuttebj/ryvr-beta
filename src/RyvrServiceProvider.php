<?php
declare(strict_types=1);

namespace Ryvr;

/**
 * Main service provider for Ryvr plugin.
 * Initializes all components and registers hooks.
 *
 * @since 1.0.0
 */
class RyvrServiceProvider
{
    /**
     * Initialize the plugin.
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function init(): void
    {
        // Load text domain
        add_action('init', [$this, 'load_text_domain']);
        
        // Initialize components after 'init' action to avoid early loading issues
        add_action('init', [$this, 'init_components'], 10);
        
        // Register assets
        add_action('admin_enqueue_scripts', [$this, 'register_assets']);
        
        // Initialize Action Scheduler if available
        if (class_exists('ActionScheduler')) {
            $this->init_scheduler();
        }
    }
    
    /**
     * Initialize plugin components.
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function init_components(): void
    {
        // Initialize admin components
        $this->init_admin();
        
        // Initialize API components
        $this->init_api();
        
        // Initialize connectors
        $this->init_connectors();
        
        // Initialize workflow engine
        $this->init_engine();
    }
    
    /**
     * Load plugin text domain.
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function load_text_domain(): void
    {
        load_plugin_textdomain('ryvr', false, dirname(RYVR_PLUGIN_BASENAME) . '/languages');
    }
    
    /**
     * Initialize admin components.
     *
     * @return void
     *
     * @since 1.0.0
     */
    private function init_admin(): void
    {
        // Load admin menu
        $menu_file = RYVR_PLUGIN_DIR . 'src/Admin/Menu.php';
        if (file_exists($menu_file)) {
            require_once $menu_file;
            $menu = new Admin\Menu();
            $menu->register();
        }
        
        // Load settings
        $settings_file = RYVR_PLUGIN_DIR . 'src/Admin/Settings.php';
        if (file_exists($settings_file)) {
            require_once $settings_file;
            $settings = new Admin\Settings();
            $settings->register();
        }
        
        // Load workflow builder
        $builder_file = RYVR_PLUGIN_DIR . 'src/Admin/WorkflowBuilder.php';
        if (file_exists($builder_file)) {
            require_once $builder_file;
            $builder = new Admin\WorkflowBuilder();
            $builder->register();
        }
    }
    
    /**
     * Initialize API components.
     *
     * @return void
     *
     * @since 1.0.0
     */
    private function init_api(): void
    {
        // Register API endpoints
        $endpoints_file = RYVR_PLUGIN_DIR . 'src/API/Endpoints.php';
        if (file_exists($endpoints_file)) {
            require_once $endpoints_file;
            $endpoints = new API\Endpoints();
            $endpoints->register();
        }
    }
    
    /**
     * Initialize connectors.
     *
     * @return void
     *
     * @since 1.0.0
     */
    private function init_connectors(): void
    {
        // Load connector interface first
        $interface_file = RYVR_PLUGIN_DIR . 'src/Connectors/RyvrConnectorInterface.php';
        if (file_exists($interface_file)) {
            require_once $interface_file;
        }
        
        // Load abstract connector
        $abstract_file = RYVR_PLUGIN_DIR . 'src/Connectors/AbstractConnector.php';
        if (file_exists($abstract_file)) {
            require_once $abstract_file;
        }
        
        // Load individual connectors
        $openai_file = RYVR_PLUGIN_DIR . 'src/Connectors/OpenAI/OpenAIConnector.php';
        if (file_exists($openai_file)) {
            require_once $openai_file;
        }
        
        $dataforseo_file = RYVR_PLUGIN_DIR . 'src/Connectors/DataForSEO/DataForSEOConnector.php';
        if (file_exists($dataforseo_file)) {
            require_once $dataforseo_file;
        }
        
        // Register connector manager
        $manager_file = RYVR_PLUGIN_DIR . 'src/Connectors/Manager.php';
        if (file_exists($manager_file)) {
            require_once $manager_file;
            
            try {
                $manager = new Connectors\Manager();
                $manager->register_connectors();
            } catch (\Throwable $e) {
                // Log error but don't break the plugin
                error_log('Ryvr: Failed to initialize connectors: ' . $e->getMessage());
            }
        }
    }
    
    /**
     * Initialize workflow engine.
     *
     * @return void
     *
     * @since 1.0.0
     */
    private function init_engine(): void
    {
        // Load async task manager for DataForSEO operations
        $async_manager_file = RYVR_PLUGIN_DIR . 'src/Engine/AsyncTaskManager.php';
        if (file_exists($async_manager_file)) {
            require_once $async_manager_file;
            $async_manager = new Engine\AsyncTaskManager();
            $async_manager->register();
        }
        
        // Load workflow runner for execution
        $runner_file = RYVR_PLUGIN_DIR . 'src/Engine/Runner.php';
        if (file_exists($runner_file)) {
            require_once $runner_file;
            $runner = new Engine\Runner();
            
            // Register hooks for workflow execution
            add_action('ryvr_run_workflow', [$runner, 'run'], 10, 2);
        }
    }
    
    /**
     * Initialize Action Scheduler.
     *
     * @return void
     *
     * @since 1.0.0
     */
    private function init_scheduler(): void
    {
        // Custom Action Scheduler configuration
        add_filter('action_scheduler_retention_period', function () {
            return 7 * DAY_IN_SECONDS; // 7 days
        });
    }
    
    /**
     * Register admin assets.
     *
     * @param string $hook_suffix The current admin page.
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function register_assets(string $hook_suffix): void
    {
        // Check if we're on the Ryvr admin page
        if (strpos($hook_suffix, 'ryvr') === false) {
            return;
        }
        
        // Register and enqueue styles
        wp_register_style(
            'ryvr-admin',
            RYVR_PLUGIN_URL . 'assets/css/admin.css',
            [],
            RYVR_VERSION
        );
        wp_enqueue_style('ryvr-admin');
        
        // Register and enqueue scripts
        wp_register_script(
            'ryvr-admin',
            RYVR_PLUGIN_URL . 'assets/js/admin.js',
            ['jquery'],
            RYVR_VERSION,
            true
        );
        wp_enqueue_script('ryvr-admin');
        
        // Localize script with translation strings and plugin data
        wp_localize_script('ryvr-admin', 'ryvrData', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ryvr-admin-nonce'),
            'pluginUrl' => RYVR_PLUGIN_URL,
            'i18n' => $this->get_i18n_strings(),
        ]);
    }
    
    /**
     * Get internationalization strings for JavaScript.
     *
     * @return array
     *
     * @since 1.0.0
     */
    private function get_i18n_strings(): array
    {
        return [
            'errorLoadingConnector' => __('Error loading connector data.', 'ryvr'),
            'errorSavingCredentials' => __('Error saving credentials.', 'ryvr'),
            'errorDeletingCredentials' => __('Error deleting credentials.', 'ryvr'),
            'errorTestingConnection' => __('Error testing connection.', 'ryvr'),
            'configuration' => __('Configuration', 'ryvr'),
            'noActionsFound' => __('No actions found for this connector.', 'ryvr'),
            'errorLoadingActions' => __('Error loading actions.', 'ryvr'),
            'invalidJson' => __('Invalid JSON. Please check your syntax.', 'ryvr'),
            'workflowSaved' => __('Workflow saved successfully.', 'ryvr'),
            'errorSavingWorkflow' => __('Error saving workflow.', 'ryvr'),
            'confirmDeleteWorkflow' => __('Are you sure you want to delete this workflow? This action cannot be undone.', 'ryvr'),
            'errorDeletingWorkflow' => __('Error deleting workflow.', 'ryvr'),
            'errorRunningWorkflow' => __('Error running workflow.', 'ryvr'),
            'invalidWorkflowJson' => __('Invalid workflow JSON. Please check your syntax.', 'ryvr'),
            'workflowValid' => __('Workflow is valid!', 'ryvr'),
            'validationErrors' => __('Workflow validation failed with the following errors:', 'ryvr'),
            'missingWorkflowId' => __('Workflow ID is required.', 'ryvr'),
            'missingWorkflowName' => __('Workflow name is required.', 'ryvr'),
            'missingWorkflowSteps' => __('Workflow must have at least one step.', 'ryvr'),
            'missingStepId' => __('Step %d must have an ID.', 'ryvr'),
            'missingStepType' => __('Step %d must have a type.', 'ryvr'),
            'missingStepConnector' => __('Step %d must specify a connector.', 'ryvr'),
            'missingStepAction' => __('Step %d must specify an action.', 'ryvr'),
            'missingStepCondition' => __('Step %d must have a condition.', 'ryvr'),
            'missingStepTemplate' => __('Step %d must have a template.', 'ryvr'),
            'actions' => __('Actions', 'ryvr'),
            'parameters' => __('Parameters', 'ryvr'),
            'name' => __('Name', 'ryvr'),
            'type' => __('Type', 'ryvr'),
            'description' => __('Description', 'ryvr'),
            'insertIntoWorkflow' => __('Insert into Workflow', 'ryvr'),
        ];
    }
} 