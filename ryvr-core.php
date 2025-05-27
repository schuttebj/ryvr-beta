<?php
/**
 * Ryvr Core - Automation layer for small-business marketing
 *
 * @package           Ryvr
 * @author            Ryvr Team
 * @copyright         2024 Ryvr
 * @license           GPL-2.0-or-later
 *
 * @wordpress-plugin
 * Plugin Name:       Ryvr Core
 * Plugin URI:        https://github.com/schuttebj/ryvr-beta
 * Description:       An automation layer for small-business marketing that connects various marketing tools and services.
 * Version:           1.0.0-alpha
 * Requires at least: 6.5
 * Requires PHP:      8.0
 * Author:            Ryvr Team
 * Author URI:        https://github.com/schuttebj
 * Text Domain:       ryvr
 * License:           GPL v2 or later
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 */

declare(strict_types=1);

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Plugin constants
define('RYVR_VERSION', '1.0.0-alpha');
define('RYVR_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('RYVR_PLUGIN_URL', plugin_dir_url(__FILE__));
define('RYVR_PLUGIN_FILE', __FILE__);
define('RYVR_PLUGIN_BASENAME', plugin_basename(__FILE__));

// Composer autoloader
if (file_exists(RYVR_PLUGIN_DIR . 'vendor/autoload.php')) {
    require_once RYVR_PLUGIN_DIR . 'vendor/autoload.php';
}

/**
 * Plugin activation hook
 *
 * @return void
 */
function ryvr_activate(): void {
    // Create database tables
    require_once RYVR_PLUGIN_DIR . 'src/Database/Installer.php';
    $installer = new \Ryvr\Database\Installer();
    $installer->create_tables();
    
    // Set plugin version in database
    update_option('ryvr_version', RYVR_VERSION);
    
    // Create initial roles and capabilities
    require_once RYVR_PLUGIN_DIR . 'src/Admin/Roles.php';
    $roles = new \Ryvr\Admin\Roles();
    $roles->create_roles();
    
    // Flush rewrite rules
    flush_rewrite_rules();
}
register_activation_hook(RYVR_PLUGIN_FILE, 'ryvr_activate');

/**
 * Plugin deactivation hook
 *
 * @return void
 */
function ryvr_deactivate(): void {
    // Flush rewrite rules
    flush_rewrite_rules();
}
register_deactivation_hook(RYVR_PLUGIN_FILE, 'ryvr_deactivate');

/**
 * Plugin uninstall hook (static method to avoid loading full plugin)
 *
 * @return void
 */
function ryvr_uninstall(): void {
    // Delete options
    delete_option('ryvr_version');
    
    // Note: We'll keep database tables by default to prevent data loss
    // Complete uninstall should be a user option
}
register_uninstall_hook(RYVR_PLUGIN_FILE, 'ryvr_uninstall');

/**
 * Initialize the plugin
 *
 * @return void
 */
function ryvr_init(): void {
    // Check if required PHP version is met
    if (version_compare(PHP_VERSION, '8.0', '<')) {
        add_action('admin_notices', function () {
            echo '<div class="error"><p>' . 
                 sprintf(__('Ryvr requires PHP 8.0 or higher. You are running PHP %s.', 'ryvr'), PHP_VERSION) . 
                 '</p></div>';
        });
        return;
    }
    
    // Check if Composer dependencies are installed
    if (!file_exists(RYVR_PLUGIN_DIR . 'vendor/autoload.php')) {
        add_action('admin_notices', function () {
            echo '<div class="error"><p>' . 
                 __('Ryvr requires Composer dependencies to be installed. Please run "composer install" in the plugin directory.', 'ryvr') . 
                 '</p></div>';
        });
        return;
    }
    
    // Initialize plugin
    require_once RYVR_PLUGIN_DIR . 'src/RyvrServiceProvider.php';
    $ryvr = new \Ryvr\RyvrServiceProvider();
    $ryvr->init();
}
add_action('plugins_loaded', 'ryvr_init'); 
