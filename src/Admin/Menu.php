<?php
declare(strict_types=1);

namespace Ryvr\Admin;

/**
 * Admin menu handler.
 *
 * @since 1.0.0
 */
class Menu
{
    /**
     * Register admin menus.
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function register(): void
    {
        add_action('admin_menu', [$this, 'add_menu_pages']);
    }
    
    /**
     * Add menu pages.
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function add_menu_pages(): void
    {
        // Main menu
        add_menu_page(
            __('Ryvr', 'ryvr'),
            __('Ryvr', 'ryvr'),
            'view_ryvr_dashboard',
            'ryvr',
            [$this, 'render_dashboard_page'],
            'dashicons-chart-area',
            30
        );
        
        // Dashboard submenu
        add_submenu_page(
            'ryvr',
            __('Dashboard', 'ryvr'),
            __('Dashboard', 'ryvr'),
            'view_ryvr_dashboard',
            'ryvr',
            [$this, 'render_dashboard_page']
        );
        
        // Connectors submenu
        add_submenu_page(
            'ryvr',
            __('Connectors', 'ryvr'),
            __('Connectors', 'ryvr'),
            'view_ryvr_connectors',
            'ryvr-connectors',
            [$this, 'render_connectors_page']
        );
        
        // Logs submenu
        add_submenu_page(
            'ryvr',
            __('Logs', 'ryvr'),
            __('Logs', 'ryvr'),
            'view_ryvr_logs',
            'ryvr-logs',
            [$this, 'render_logs_page']
        );
        
        // Settings submenu
        add_submenu_page(
            'ryvr',
            __('Settings', 'ryvr'),
            __('Settings', 'ryvr'),
            'view_ryvr_settings',
            'ryvr-settings',
            [$this, 'render_settings_page']
        );
        
        // Conditionally add Agency management for admins
        if (current_user_can('manage_ryvr_agencies')) {
            add_submenu_page(
                'ryvr',
                __('Agencies', 'ryvr'),
                __('Agencies', 'ryvr'),
                'manage_ryvr_agencies',
                'ryvr-agencies',
                [$this, 'render_agencies_page']
            );
        }
        
        // Conditionally add User management for admins and agencies
        if (current_user_can('manage_ryvr_users')) {
            add_submenu_page(
                'ryvr',
                __('Users', 'ryvr'),
                __('Users', 'ryvr'),
                'manage_ryvr_users',
                'ryvr-users',
                [$this, 'render_users_page']
            );
        }
    }
    
    /**
     * Render dashboard page.
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function render_dashboard_page(): void
    {
        // Include dashboard view
        require_once RYVR_PLUGIN_DIR . 'views/admin/dashboard.php';
    }
    
    /**
     * Render connectors page.
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function render_connectors_page(): void
    {
        // Include connectors view
        require_once RYVR_PLUGIN_DIR . 'views/admin/connectors.php';
    }
    
    /**
     * Render logs page.
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function render_logs_page(): void
    {
        // Include logs view
        require_once RYVR_PLUGIN_DIR . 'views/admin/logs.php';
    }
    
    /**
     * Render settings page.
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function render_settings_page(): void
    {
        // Include settings view
        require_once RYVR_PLUGIN_DIR . 'views/admin/settings.php';
    }
    
    /**
     * Render agencies page.
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function render_agencies_page(): void
    {
        // Include agencies view
        require_once RYVR_PLUGIN_DIR . 'views/admin/agencies.php';
    }
    
    /**
     * Render users page.
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function render_users_page(): void
    {
        // Include users view
        require_once RYVR_PLUGIN_DIR . 'views/admin/users.php';
    }
} 