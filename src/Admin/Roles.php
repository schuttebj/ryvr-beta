<?php
declare(strict_types=1);

namespace Ryvr\Admin;

/**
 * Roles and capabilities manager.
 *
 * @since 1.0.0
 */
class Roles
{
    /**
     * Create or update roles and capabilities.
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function create_roles(): void
    {
        // Add admin capabilities to administrator role
        $administrator = get_role('administrator');
        
        if ($administrator instanceof \WP_Role) {
            foreach ($this->get_admin_capabilities() as $cap) {
                $administrator->add_cap($cap);
            }
        }
        
        // Create Ryvr agency role
        add_role(
            'ryvr_agency',
            __('Ryvr Agency', 'ryvr'),
            $this->get_agency_capabilities()
        );
        
        // Create Ryvr user role
        add_role(
            'ryvr_user',
            __('Ryvr User', 'ryvr'),
            $this->get_user_capabilities()
        );
    }
    
    /**
     * Get admin capabilities.
     *
     * @return array Admin capabilities.
     *
     * @since 1.0.0
     */
    private function get_admin_capabilities(): array
    {
        return [
            // General Ryvr capabilities
            'manage_ryvr',
            'view_ryvr_dashboard',
            
            // Workflow capabilities
            'create_ryvr_workflows',
            'edit_ryvr_workflows',
            'delete_ryvr_workflows',
            'run_ryvr_workflows',
            'view_ryvr_workflows',
            
            // Connector capabilities
            'manage_ryvr_connectors',
            'edit_ryvr_connectors',
            'delete_ryvr_connectors',
            'view_ryvr_connectors',
            
            // Agency capabilities
            'manage_ryvr_agencies',
            'view_ryvr_agencies',
            
            // User capabilities
            'manage_ryvr_users',
            'view_ryvr_users',
            
            // Settings capabilities
            'manage_ryvr_settings',
            'view_ryvr_settings',
            
            // Advanced capabilities
            'manage_ryvr_api',
            'view_ryvr_logs',
            'delete_ryvr_logs',
        ];
    }
    
    /**
     * Get agency capabilities.
     *
     * @return array Agency capabilities.
     *
     * @since 1.0.0
     */
    private function get_agency_capabilities(): array
    {
        return [
            // General Ryvr capabilities
            'manage_ryvr',
            'view_ryvr_dashboard',
            
            // Workflow capabilities
            'create_ryvr_workflows',
            'edit_ryvr_workflows',
            'delete_ryvr_workflows',
            'run_ryvr_workflows',
            'view_ryvr_workflows',
            
            // Connector capabilities
            'manage_ryvr_connectors',
            'edit_ryvr_connectors',
            'view_ryvr_connectors',
            
            // User capabilities
            'manage_ryvr_users',
            'view_ryvr_users',
            
            // Settings capabilities
            'view_ryvr_settings',
            
            // Advanced capabilities
            'view_ryvr_logs',
            
            // WordPress capabilities
            'read',
        ];
    }
    
    /**
     * Get user capabilities.
     *
     * @return array User capabilities.
     *
     * @since 1.0.0
     */
    private function get_user_capabilities(): array
    {
        return [
            // General Ryvr capabilities
            'view_ryvr_dashboard',
            
            // Workflow capabilities
            'create_ryvr_workflows',
            'edit_ryvr_workflows',
            'run_ryvr_workflows',
            'view_ryvr_workflows',
            
            // Connector capabilities
            'edit_ryvr_connectors',
            'view_ryvr_connectors',
            
            // WordPress capabilities
            'read',
        ];
    }
    
    /**
     * Remove roles and capabilities.
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function remove_roles(): void
    {
        // Remove Ryvr roles
        remove_role('ryvr_agency');
        remove_role('ryvr_user');
        
        // Remove capabilities from administrator role
        $administrator = get_role('administrator');
        
        if ($administrator instanceof \WP_Role) {
            foreach ($this->get_admin_capabilities() as $cap) {
                $administrator->remove_cap($cap);
            }
        }
    }
} 