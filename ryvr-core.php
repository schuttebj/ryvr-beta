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
    
    // First, let's try to download a pre-packaged vendor bundle
    // This is a more reliable approach than downloading individual packages
    $bundle_url = 'https://raw.githubusercontent.com/schuttebj/ryvr-beta/vendor-bundle/vendor-bundle.zip';
    $bundle_file = download_url($bundle_url);
    
    if (!is_wp_error($bundle_file)) {
        // We have a bundle, extract it
        $result = unzip_file($bundle_file, RYVR_PLUGIN_DIR);
        unlink($bundle_file);
        
        if (!is_wp_error($result)) {
            return [
                'success' => true,
                'message' => __('Successfully installed dependencies from the pre-packaged bundle.', 'ryvr')
            ];
        }
    }
    
    // If we get here, the bundle approach failed, try individual packages
    $dependencies = [
        'guzzlehttp/guzzle' => [
            'url' => 'https://github.com/guzzle/guzzle/archive/refs/tags/7.8.1.zip',
            'dir' => 'guzzle-7.8.1',
            'dest' => 'guzzlehttp/guzzle'
        ],
        'guzzlehttp/promises' => [
            'url' => 'https://github.com/guzzle/promises/archive/refs/tags/2.0.2.zip',
            'dir' => 'promises-2.0.2',
            'dest' => 'guzzlehttp/promises'
        ],
        'guzzlehttp/psr7' => [
            'url' => 'https://github.com/guzzle/psr7/archive/refs/tags/2.6.2.zip',
            'dir' => 'psr7-2.6.2',
            'dest' => 'guzzlehttp/psr7'
        ],
        'woocommerce/action-scheduler' => [
            'url' => 'https://github.com/woocommerce/action-scheduler/archive/refs/tags/3.7.1.zip',
            'dir' => 'action-scheduler-3.7.1',
            'dest' => 'woocommerce/action-scheduler'
        ]
    ];
    
    $installed = [];
    $failed = [];
    
    foreach ($dependencies as $package => $info) {
        $result = ryvr_download_package($package, $info, $vendor_dir);
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
 * @param array $info Package info including URL, directory name and destination
 * @param string $vendor_dir Vendor directory path
 *
 * @return bool Success status
 */
function ryvr_download_package(string $package, array $info, string $vendor_dir): bool {
    $url = $info['url'];
    $source_dir = $info['dir'];
    $dest_path = $info['dest'];
    
    // Create a temp directory
    $temp_dir = wp_tempnam('ryvr-dep-');
    unlink($temp_dir);
    wp_mkdir_p($temp_dir);
    
    // Download the package
    $temp_file = download_url($url);
    
    if (is_wp_error($temp_file)) {
        return false;
    }
    
    // Extract to the temp directory
    $result = unzip_file($temp_file, $temp_dir);
    unlink($temp_file);
    
    if (is_wp_error($result)) {
        return false;
    }
    
    // Create destination directory
    $dest_dir = $vendor_dir . '/' . $dest_path;
    wp_mkdir_p(dirname($dest_dir));
    
    // Check if GitHub archive exists
    $github_dir = $temp_dir . '/' . $source_dir;
    
    if (file_exists($github_dir) && is_dir($github_dir)) {
        // Move from the temp location to the vendor directory
        // First remove existing directory if it exists
        if (file_exists($dest_dir)) {
            ryvr_recursive_rmdir($dest_dir);
        }
        
        // Now copy the files
        ryvr_recursive_copy($github_dir, $dest_dir);
        
        // Clean up
        ryvr_recursive_rmdir($temp_dir);
        
        return true;
    }
    
    // If we get here, something went wrong
    ryvr_recursive_rmdir($temp_dir);
    return false;
}

/**
 * Recursively delete a directory
 *
 * @param string $dir Directory path
 * 
 * @return bool Success status
 */
function ryvr_recursive_rmdir(string $dir): bool {
    if (!file_exists($dir)) {
        return true;
    }
    
    if (!is_dir($dir)) {
        return unlink($dir);
    }
    
    foreach (scandir($dir) as $item) {
        if ($item == '.' || $item == '..') {
            continue;
        }
        
        $path = $dir . '/' . $item;
        
        if (is_dir($path)) {
            ryvr_recursive_rmdir($path);
        } else {
            unlink($path);
        }
    }
    
    return rmdir($dir);
}

/**
 * Recursively copy a directory
 *
 * @param string $src Source directory
 * @param string $dst Destination directory
 * 
 * @return bool Success status
 */
function ryvr_recursive_copy(string $src, string $dst): bool {
    $dir = opendir($src);
    wp_mkdir_p($dst);
    
    while (($file = readdir($dir)) !== false) {
        if ($file == '.' || $file == '..') {
            continue;
        }
        
        $src_path = $src . '/' . $file;
        $dst_path = $dst . '/' . $file;
        
        if (is_dir($src_path)) {
            ryvr_recursive_copy($src_path, $dst_path);
        } else {
            copy($src_path, $dst_path);
        }
    }
    
    closedir($dir);
    return true;
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
    
    // Namespaces to handle
    $namespaces = [
        "GuzzleHttp\\\\" => "guzzlehttp/guzzle/src/",
        "GuzzleHttp\\\\Promise\\\\" => "guzzlehttp/promises/src/",
        "GuzzleHttp\\\\Psr7\\\\" => "guzzlehttp/psr7/src/"
    ];
    
    foreach ($namespaces as $namespace => $dir) {
        if (strpos($class, $namespace) === 0) {
            $relative_class = substr($class, strlen($namespace));
            $file = $vendor_dir . "/" . $dir . str_replace("\\\\", "/", $relative_class) . ".php";
            
            if (file_exists($file)) {
                require_once $file;
                return;
            }
        }
    }
    
    // Handle Action Scheduler classes - different structure
    if (strpos($class, "Action_Scheduler") === 0) {
        $file = $vendor_dir . "/woocommerce/action-scheduler/classes/" . str_replace("_", "-", strtolower($class)) . ".php";
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
});

// Load Guzzle function files that aren\'t handled by the autoloader
$guzzle_functions = [
    "/guzzlehttp/guzzle/src/functions_include.php",
    "/guzzlehttp/promises/src/functions_include.php",
    "/guzzlehttp/psr7/src/functions_include.php"
];

foreach ($guzzle_functions as $file) {
    $path = __DIR__ . $file;
    if (file_exists($path)) {
        require_once $path;
    }
}

// Bootstrap Action Scheduler if it exists
$action_scheduler_file = __DIR__ . "/woocommerce/action-scheduler/action-scheduler.php";
if (file_exists($action_scheduler_file)) {
    require_once $action_scheduler_file;
}
';
    
    file_put_contents($vendor_dir . '/autoload.php', $autoload_content);
} 
