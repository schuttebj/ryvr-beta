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
        
        // Initialize admin components
        $this->init_admin();
        
        // Initialize API components
        $this->init_api();
        
        // Initialize connectors
        $this->init_connectors();
        
        // Initialize workflow engine
        $this->init_engine();
        
        // Register assets
        add_action('admin_enqueue_scripts', [$this, 'register_assets']);
        
        // Initialize Action Scheduler if available
        if (class_exists('ActionScheduler')) {
            $this->init_scheduler();
        }
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
        require_once RYVR_PLUGIN_DIR . 'src/Admin/Menu.php';
        $menu = new Admin\Menu();
        $menu->register();
        
        // Load settings
        require_once RYVR_PLUGIN_DIR . 'src/Admin/Settings.php';
        $settings = new Admin\Settings();
        $settings->register();
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
        require_once RYVR_PLUGIN_DIR . 'src/API/Endpoints.php';
        $endpoints = new API\Endpoints();
        $endpoints->register();
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
        // Register connector manager
        require_once RYVR_PLUGIN_DIR . 'src/Connectors/Manager.php';
        $manager = new Connectors\Manager();
        $manager->register_connectors();
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
        // Load workflow manager
        require_once RYVR_PLUGIN_DIR . 'src/Workflows/Manager.php';
        $workflow_manager = new Workflows\Manager();
        $workflow_manager->register_workflows();
        
        // Load workflow engine
        require_once RYVR_PLUGIN_DIR . 'src/Engine/Runner.php';
        $runner = new Engine\Runner();
        
        // Register hooks for cron triggers
        add_action('ryvr_run_workflow', [$runner, 'run'], 10, 2);
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
            'i18n' => [
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
            ],
        ]);
    }
} 