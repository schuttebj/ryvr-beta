<?php
declare(strict_types=1);

namespace Ryvr\API;

/**
 * API Endpoints for Ryvr plugin.
 *
 * @since 1.0.0
 */
class Endpoints
{
    /**
     * Register API endpoints.
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function register(): void
    {
        add_action('rest_api_init', [$this, 'register_routes']);
    }
    
    /**
     * Register REST API routes.
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function register_routes(): void
    {
        // Register namespace
        $namespace = 'ryvr/v1';
        
        // Test endpoint
        register_rest_route($namespace, '/test', [
            'methods' => 'GET',
            'callback' => [$this, 'test_endpoint'],
            'permission_callback' => [$this, 'check_permissions'],
        ]);
        
        // Connectors endpoint
        register_rest_route($namespace, '/connectors', [
            'methods' => 'GET',
            'callback' => [$this, 'get_connectors'],
            'permission_callback' => [$this, 'check_permissions'],
        ]);
        
        // Workflows endpoint
        register_rest_route($namespace, '/workflows', [
            [
                'methods' => 'GET',
                'callback' => [$this, 'get_workflows'],
                'permission_callback' => [$this, 'check_permissions'],
            ],
            [
                'methods' => 'POST',
                'callback' => [$this, 'create_workflow'],
                'permission_callback' => [$this, 'check_permissions'],
            ],
        ]);
        
        // Individual workflow endpoint
        register_rest_route($namespace, '/workflows/(?P<id>[a-zA-Z0-9_-]+)', [
            [
                'methods' => 'GET',
                'callback' => [$this, 'get_workflow'],
                'permission_callback' => [$this, 'check_permissions'],
            ],
            [
                'methods' => 'PUT',
                'callback' => [$this, 'update_workflow'],
                'permission_callback' => [$this, 'check_permissions'],
            ],
            [
                'methods' => 'DELETE',
                'callback' => [$this, 'delete_workflow'],
                'permission_callback' => [$this, 'check_permissions'],
            ],
        ]);
    }
    
    /**
     * Check permissions for API endpoints.
     *
     * @return bool
     *
     * @since 1.0.0
     */
    public function check_permissions(): bool
    {
        return current_user_can('manage_options');
    }
    
    /**
     * Test endpoint.
     *
     * @return \WP_REST_Response
     *
     * @since 1.0.0
     */
    public function test_endpoint(): \WP_REST_Response
    {
        return new \WP_REST_Response([
            'message' => 'Ryvr API is working',
            'version' => RYVR_VERSION,
            'timestamp' => current_time('mysql'),
        ]);
    }
    
    /**
     * Get available connectors.
     *
     * @return \WP_REST_Response
     *
     * @since 1.0.0
     */
    public function get_connectors(): \WP_REST_Response
    {
        // TODO: Implement connector listing
        return new \WP_REST_Response([
            'connectors' => [],
        ]);
    }
    
    /**
     * Get workflows.
     *
     * @return \WP_REST_Response
     *
     * @since 1.0.0
     */
    public function get_workflows(): \WP_REST_Response
    {
        // TODO: Implement workflow listing
        return new \WP_REST_Response([
            'workflows' => [],
        ]);
    }
    
    /**
     * Create a new workflow.
     *
     * @param \WP_REST_Request $request Request object.
     *
     * @return \WP_REST_Response
     *
     * @since 1.0.0
     */
    public function create_workflow(\WP_REST_Request $request): \WP_REST_Response
    {
        // TODO: Implement workflow creation
        return new \WP_REST_Response([
            'message' => 'Workflow creation not yet implemented',
        ], 501);
    }
    
    /**
     * Get a specific workflow.
     *
     * @param \WP_REST_Request $request Request object.
     *
     * @return \WP_REST_Response
     *
     * @since 1.0.0
     */
    public function get_workflow(\WP_REST_Request $request): \WP_REST_Response
    {
        $id = $request->get_param('id');
        
        // TODO: Implement workflow retrieval
        return new \WP_REST_Response([
            'message' => "Workflow {$id} retrieval not yet implemented",
        ], 501);
    }
    
    /**
     * Update a workflow.
     *
     * @param \WP_REST_Request $request Request object.
     *
     * @return \WP_REST_Response
     *
     * @since 1.0.0
     */
    public function update_workflow(\WP_REST_Request $request): \WP_REST_Response
    {
        $id = $request->get_param('id');
        
        // TODO: Implement workflow update
        return new \WP_REST_Response([
            'message' => "Workflow {$id} update not yet implemented",
        ], 501);
    }
    
    /**
     * Delete a workflow.
     *
     * @param \WP_REST_Request $request Request object.
     *
     * @return \WP_REST_Response
     *
     * @since 1.0.0
     */
    public function delete_workflow(\WP_REST_Request $request): \WP_REST_Response
    {
        $id = $request->get_param('id');
        
        // TODO: Implement workflow deletion
        return new \WP_REST_Response([
            'message' => "Workflow {$id} deletion not yet implemented",
        ], 501);
    }
} 