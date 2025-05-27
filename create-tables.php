<?php
/**
 * Manual database table creation script for Ryvr
 * Run this if you're having issues with workflow saving
 */

// Include WordPress
require_once('wp-config.php');

// Include the database installer
require_once('wp-content/plugins/ryvr-beta/src/Database/Installer.php');

echo "Creating Ryvr database tables...\n";

try {
    $installer = new \Ryvr\Database\Installer();
    $installer->create_tables();
    
    echo "✓ Database tables created successfully!\n";
    
    // Check if tables exist
    global $wpdb;
    $tables = [
        $wpdb->prefix . 'ryvr_workflows',
        $wpdb->prefix . 'ryvr_tasks', 
        $wpdb->prefix . 'ryvr_runs',
        $wpdb->prefix . 'ryvr_logs',
        $wpdb->prefix . 'ryvr_api_keys',
        $wpdb->prefix . 'ryvr_settings'
    ];
    
    echo "\nChecking table existence:\n";
    foreach ($tables as $table) {
        $exists = $wpdb->get_var("SHOW TABLES LIKE '$table'");
        echo ($exists ? "✓" : "✗") . " $table\n";
    }
    
} catch (Exception $e) {
    echo "✗ Error creating tables: " . $e->getMessage() . "\n";
}

echo "\nDone!\n"; 