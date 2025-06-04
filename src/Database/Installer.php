<?php
declare(strict_types=1);

namespace Ryvr\Database;

/**
 * Database installer for Ryvr.
 * Handles creating and updating database tables.
 *
 * @since 1.0.0
 */
class Installer
{
    /**
     * Create database tables.
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function create_tables(): void
    {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $tables = [
            $this->get_workflows_table_sql($charset_collate),
            $this->get_tasks_table_sql($charset_collate),
            $this->get_runs_table_sql($charset_collate),
            $this->get_logs_table_sql($charset_collate),
            $this->get_api_keys_table_sql($charset_collate),
            $this->get_settings_table_sql($charset_collate),
            $this->get_async_tasks_table_sql($charset_collate),
        ];
        
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        
        foreach ($tables as $table_sql) {
            dbDelta($table_sql);
        }
    }
    
    /**
     * Get SQL for creating the workflows table.
     *
     * @param string $charset_collate The charset collate string.
     *
     * @return string The SQL for creating the table.
     *
     * @since 1.0.0
     */
    private function get_workflows_table_sql(string $charset_collate): string
    {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'ryvr_workflows';
        
        return "CREATE TABLE $table_name (
            id varchar(100) NOT NULL,
            name varchar(255) NOT NULL,
            description text NULL,
            definition longtext NOT NULL,
            status varchar(20) NOT NULL DEFAULT 'active',
            user_id bigint(20) unsigned NOT NULL DEFAULT 0,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY user_id (user_id),
            KEY status (status)
        ) $charset_collate;";
    }
    
    /**
     * Get SQL for creating the tasks table.
     *
     * @param string $charset_collate The charset collate string.
     *
     * @return string The SQL for creating the table.
     *
     * @since 1.0.0
     */
    private function get_tasks_table_sql(string $charset_collate): string
    {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'ryvr_tasks';
        
        return "CREATE TABLE $table_name (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            workflow_id bigint(20) unsigned NOT NULL,
            title varchar(255) NOT NULL,
            task_type varchar(50) NOT NULL,
            task_data longtext NOT NULL,
            status varchar(20) NOT NULL DEFAULT 'active',
            user_id bigint(20) unsigned NOT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY workflow_id (workflow_id),
            KEY user_id (user_id),
            KEY status (status)
        ) $charset_collate;";
    }
    
    /**
     * Get SQL for creating the runs table.
     *
     * @param string $charset_collate The charset collate string.
     *
     * @return string The SQL for creating the table.
     *
     * @since 1.0.0
     */
    private function get_runs_table_sql(string $charset_collate): string
    {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'ryvr_runs';
        
        return "CREATE TABLE $table_name (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            workflow_id bigint(20) unsigned NOT NULL,
            task_id bigint(20) unsigned NULL,
            status varchar(20) NOT NULL DEFAULT 'pending',
            trigger_type varchar(50) NOT NULL,
            context_data longtext NULL,
            result_data longtext NULL,
            started_at datetime NULL,
            completed_at datetime NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY workflow_id (workflow_id),
            KEY task_id (task_id),
            KEY status (status)
        ) $charset_collate;";
    }
    
    /**
     * Get SQL for creating the logs table.
     *
     * @param string $charset_collate The charset collate string.
     *
     * @return string The SQL for creating the table.
     *
     * @since 1.0.0
     */
    private function get_logs_table_sql(string $charset_collate): string
    {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'ryvr_logs';
        
        return "CREATE TABLE $table_name (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            run_id bigint(20) unsigned NULL,
            log_level varchar(20) NOT NULL,
            message text NOT NULL,
            context longtext NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY run_id (run_id),
            KEY log_level (log_level)
        ) $charset_collate;";
    }
    
    /**
     * Get SQL for creating the API keys table.
     *
     * @param string $charset_collate The charset collate string.
     *
     * @return string The SQL for creating the table.
     *
     * @since 1.0.0
     */
    private function get_api_keys_table_sql(string $charset_collate): string
    {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'ryvr_api_keys';
        
        return "CREATE TABLE $table_name (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            connector_slug varchar(100) NOT NULL,
            user_id bigint(20) unsigned NOT NULL,
            auth_meta longtext NOT NULL,
            is_shared tinyint(1) NOT NULL DEFAULT 0,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY connector_slug (connector_slug),
            KEY user_id (user_id)
        ) $charset_collate;";
    }
    
    /**
     * Get SQL for creating the settings table.
     *
     * @param string $charset_collate The charset collate string.
     *
     * @return string The SQL for creating the table.
     *
     * @since 1.0.0
     */
    private function get_settings_table_sql(string $charset_collate): string
    {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'ryvr_settings';
        
        return "CREATE TABLE $table_name (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            setting_key varchar(100) NOT NULL,
            setting_value longtext NOT NULL,
            user_id bigint(20) unsigned NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY setting_key_user_id (setting_key, user_id),
            KEY setting_key (setting_key)
        ) $charset_collate;";
    }
    
    /**
     * Get SQL for creating the async tasks table.
     *
     * @param string $charset_collate The charset collate string.
     *
     * @return string The SQL for creating the table.
     *
     * @since 1.0.0
     */
    private function get_async_tasks_table_sql(string $charset_collate): string
    {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'ryvr_async_tasks';
        
        return "CREATE TABLE $table_name (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            external_task_id varchar(255) NOT NULL,
            connector_id varchar(100) NOT NULL,
            action_id varchar(100) NOT NULL,
            workflow_run_id bigint(20) unsigned NOT NULL,
            status varchar(20) NOT NULL DEFAULT 'pending',
            check_interval int(11) NOT NULL DEFAULT 30,
            next_check datetime NULL,
            result_data longtext NULL,
            error_message text NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            completed_at datetime NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY external_task_id (external_task_id),
            KEY workflow_run_id (workflow_run_id),
            KEY status (status),
            KEY next_check (next_check)
        ) $charset_collate;";
    }
} 