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
            $install_url = admin_url('admin.php?page=ryvr-install-dependencies');
            echo '<div class="notice notice-warning is-dismissible"><p>' . 
                 sprintf(
                     __('Ryvr requires additional dependencies to function properly. <a href="%s">Click here to install them automatically</a> or run "composer install --no-dev" in the plugin directory.', 'ryvr'),
                     $install_url
                 ) . 
                 '</p></div>';
        });
        
        // Add a simple admin page for dependency installation
        add_action('admin_menu', function() {
            add_submenu_page(
                null, // No parent menu (hidden)
                __('Install Ryvr Dependencies', 'ryvr'),
                __('Install Dependencies', 'ryvr'),
                'manage_options',
                'ryvr-install-dependencies',
                'ryvr_install_dependencies_page'
            );
        });
        
        // Still initialize basic functionality without dependencies
    }
    
    // Initialize plugin
    require_once RYVR_PLUGIN_DIR . 'src/RyvrServiceProvider.php';
    $ryvr = new \Ryvr\RyvrServiceProvider();
    $ryvr->init();
}
add_action('plugins_loaded', 'ryvr_init');

/**
 * Admin page for installing dependencies
 *
 * @return void
 */
function ryvr_install_dependencies_page(): void {
    if (isset($_POST['install_dependencies']) && wp_verify_nonce($_POST['_wpnonce'], 'ryvr_install_dependencies')) {
        $result = ryvr_download_dependencies();
        if ($result['success']) {
            echo '<div class="notice notice-success"><p>' . $result['message'] . '</p></div>';
        } else {
            echo '<div class="notice notice-error"><p>' . $result['message'] . '</p></div>';
        }
    }
    
    ?>
    <div class="wrap">
        <h1><?php _e('Install Ryvr Dependencies', 'ryvr'); ?></h1>
        <p><?php _e('Ryvr requires some additional libraries to function properly. Click the button below to download and install them automatically.', 'ryvr'); ?></p>
        
        <form method="post" action="">
            <?php wp_nonce_field('ryvr_install_dependencies'); ?>
            <p>
                <input type="submit" name="install_dependencies" class="button button-primary" value="<?php _e('Install Dependencies', 'ryvr'); ?>" />
            </p>
        </form>
        
        <h3><?php _e('Manual Installation', 'ryvr'); ?></h3>
        <p><?php _e('Alternatively, you can install dependencies manually by running the following command in the plugin directory:', 'ryvr'); ?></p>
        <code>composer install --no-dev</code>
    </div>
    <?php
}

/**
 * Download and install dependencies
 *
 * @return array Result array with success status and message
 */
function ryvr_download_dependencies(): array {
    $vendor_dir = RYVR_PLUGIN_DIR . 'vendor';
    
    // Create vendor directory if it doesn't exist
    if (!file_exists($vendor_dir)) {
        wp_mkdir_p($vendor_dir);
    }
    
    // Download the minimal required dependencies directly
    $dependencies = [
        'guzzlehttp/guzzle' => 'https://github.com/guzzle/guzzle/archive/refs/tags/7.8.1.zip',
        'woocommerce/action-scheduler' => 'https://github.com/woocommerce/action-scheduler/archive/refs/tags/3.7.1.zip'
    ];
    
    $installed = [];
    $failed = [];
    
    foreach ($dependencies as $package => $url) {
        $result = ryvr_download_package($package, $url, $vendor_dir);
        if ($result) {
            $installed[] = $package;
        } else {
            $failed[] = $package;
        }
    }
    
    // Create a simple autoload.php
    ryvr_create_simple_autoloader($vendor_dir);
    
    if (empty($failed)) {
        return [
            'success' => true,
            'message' => sprintf(__('Successfully installed %d dependencies: %s', 'ryvr'), count($installed), implode(', ', $installed))
        ];
    } else {
        return [
            'success' => false,
            'message' => sprintf(__('Failed to install some dependencies: %s', 'ryvr'), implode(', ', $failed))
        ];
    }
}

/**
 * Download a single package
 *
 * @param string $package Package name
 * @param string $url Download URL
 * @param string $vendor_dir Vendor directory path
 *
 * @return bool Success status
 */
function ryvr_download_package(string $package, string $url, string $vendor_dir): bool {
    $temp_file = download_url($url);
    
    if (is_wp_error($temp_file)) {
        return false;
    }
    
    $package_dir = $vendor_dir . '/' . str_replace('/', '-', $package);
    
    // Extract the zip file
    $result = unzip_file($temp_file, $package_dir);
    unlink($temp_file);
    
    return !is_wp_error($result);
}

/**
 * Create a simple autoloader for downloaded packages
 *
 * @param string $vendor_dir Vendor directory path
 *
 * @return void
 */
function ryvr_create_simple_autoloader(string $vendor_dir): void {
    $autoload_content = '<?php
// Simple autoloader for Ryvr dependencies
spl_autoload_register(function ($class) {
    $vendor_dir = __DIR__;
    
    // Handle Guzzle classes
    if (strpos($class, "GuzzleHttp\\\\") === 0) {
        $file = $vendor_dir . "/guzzlehttp-guzzle/src/" . str_replace("\\\\", "/", substr($class, 11)) . ".php";
        if (file_exists($file)) {
            require_once $file;
        }
    }
    
    // Handle Action Scheduler classes
    if (strpos($class, "Action_Scheduler") === 0) {
        $file = $vendor_dir . "/woocommerce-action-scheduler/classes/" . str_replace("_", "-", strtolower($class)) . ".php";
        if (file_exists($file)) {
            require_once $file;
        }
    }
});
';
    
    file_put_contents($vendor_dir . '/autoload.php', $autoload_content);
} 
