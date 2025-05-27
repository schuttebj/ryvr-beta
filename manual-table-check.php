<?php
/**
 * Add this to your WordPress admin to check/create Ryvr tables
 * Add to functions.php temporarily or create as a plugin
 */

add_action('admin_menu', function() {
    add_submenu_page(
        'tools.php',
        'Ryvr Table Check',
        'Ryvr Table Check', 
        'manage_options',
        'ryvr-table-check',
        'ryvr_table_check_page'
    );
});

function ryvr_table_check_page() {
    global $wpdb;
    
    echo '<div class="wrap">';
    echo '<h1>Ryvr Database Table Check</h1>';
    
    // Check if tables exist
    $tables = [
        $wpdb->prefix . 'ryvr_workflows',
        $wpdb->prefix . 'ryvr_tasks', 
        $wpdb->prefix . 'ryvr_runs',
        $wpdb->prefix . 'ryvr_logs',
        $wpdb->prefix . 'ryvr_api_keys',
        $wpdb->prefix . 'ryvr_settings'
    ];
    
    echo '<h2>Table Status:</h2>';
    echo '<table class="widefat">';
    echo '<thead><tr><th>Table</th><th>Status</th></tr></thead>';
    echo '<tbody>';
    
    $missing_tables = [];
    foreach ($tables as $table) {
        $exists = $wpdb->get_var("SHOW TABLES LIKE '$table'");
        echo '<tr>';
        echo '<td>' . $table . '</td>';
        echo '<td>' . ($exists ? '<span style="color: green;">✓ Exists</span>' : '<span style="color: red;">✗ Missing</span>') . '</td>';
        echo '</tr>';
        
        if (!$exists) {
            $missing_tables[] = $table;
        }
    }
    
    echo '</tbody></table>';
    
    if (!empty($missing_tables)) {
        echo '<h2>Create Missing Tables</h2>';
        echo '<p>The following tables are missing and need to be created:</p>';
        echo '<ul>';
        foreach ($missing_tables as $table) {
            echo '<li>' . $table . '</li>';
        }
        echo '</ul>';
        
        if (isset($_POST['create_tables'])) {
            echo '<div class="notice notice-info"><p>Creating tables...</p></div>';
            
            try {
                require_once RYVR_PLUGIN_DIR . 'src/Database/Installer.php';
                $installer = new \Ryvr\Database\Installer();
                $installer->create_tables();
                
                echo '<div class="notice notice-success"><p>✓ Tables created successfully! <a href="">Refresh page</a> to verify.</p></div>';
            } catch (Exception $e) {
                echo '<div class="notice notice-error"><p>✗ Error creating tables: ' . $e->getMessage() . '</p></div>';
            }
        } else {
            echo '<form method="post">';
            echo '<p><input type="submit" name="create_tables" value="Create Missing Tables" class="button button-primary"></p>';
            echo '</form>';
        }
    } else {
        echo '<div class="notice notice-success"><p>✓ All Ryvr tables exist!</p></div>';
    }
    
    echo '</div>';
} 