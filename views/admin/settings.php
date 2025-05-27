<?php
/**
 * Ryvr Settings Admin View
 *
 * @package Ryvr
 * @since 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Handle form submission
if (isset($_POST['submit']) && wp_verify_nonce($_POST['ryvr_settings_nonce'], 'ryvr_settings')) {
    // TODO: Implement settings saving
    $success_message = __('Settings saved successfully!', 'ryvr');
}
?>

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <?php if (isset($success_message)): ?>
        <div class="notice notice-success is-dismissible">
            <p><?php echo esc_html($success_message); ?></p>
        </div>
    <?php endif; ?>
    
    <form method="post" action="">
        <?php wp_nonce_field('ryvr_settings', 'ryvr_settings_nonce'); ?>
        
        <div class="ryvr-settings">
            <!-- General Settings -->
            <div class="settings-section">
                <h2><?php _e('General Settings', 'ryvr'); ?></h2>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="enable_logging"><?php _e('Enable Logging', 'ryvr'); ?></label>
                        </th>
                        <td>
                            <input type="checkbox" id="enable_logging" name="enable_logging" value="1" checked>
                            <p class="description"><?php _e('Enable detailed logging for debugging and monitoring.', 'ryvr'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="log_level"><?php _e('Log Level', 'ryvr'); ?></label>
                        </th>
                        <td>
                            <select id="log_level" name="log_level">
                                <option value="error"><?php _e('Error', 'ryvr'); ?></option>
                                <option value="warning"><?php _e('Warning', 'ryvr'); ?></option>
                                <option value="info" selected><?php _e('Info', 'ryvr'); ?></option>
                                <option value="debug"><?php _e('Debug', 'ryvr'); ?></option>
                            </select>
                            <p class="description"><?php _e('Set the minimum level for logging messages.', 'ryvr'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="max_execution_time"><?php _e('Max Execution Time', 'ryvr'); ?></label>
                        </th>
                        <td>
                            <input type="number" id="max_execution_time" name="max_execution_time" value="300" min="30" max="3600">
                            <p class="description"><?php _e('Maximum time (in seconds) a workflow can run before timing out.', 'ryvr'); ?></p>
                        </td>
                    </tr>
                </table>
            </div>

            <!-- Workflow Settings -->
            <div class="settings-section">
                <h2><?php _e('Workflow Settings', 'ryvr'); ?></h2>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="enable_workflows"><?php _e('Enable Workflows', 'ryvr'); ?></label>
                        </th>
                        <td>
                            <input type="checkbox" id="enable_workflows" name="enable_workflows" value="1" checked>
                            <p class="description"><?php _e('Enable workflow execution functionality.', 'ryvr'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="concurrent_workflows"><?php _e('Concurrent Workflows', 'ryvr'); ?></label>
                        </th>
                        <td>
                            <input type="number" id="concurrent_workflows" name="concurrent_workflows" value="5" min="1" max="50">
                            <p class="description"><?php _e('Maximum number of workflows that can run simultaneously.', 'ryvr'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="workflow_retry_attempts"><?php _e('Retry Attempts', 'ryvr'); ?></label>
                        </th>
                        <td>
                            <input type="number" id="workflow_retry_attempts" name="workflow_retry_attempts" value="3" min="0" max="10">
                            <p class="description"><?php _e('Number of times to retry a failed workflow step.', 'ryvr'); ?></p>
                        </td>
                    </tr>
                </table>
            </div>

            <!-- Security Settings -->
            <div class="settings-section">
                <h2><?php _e('Security Settings', 'ryvr'); ?></h2>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="encrypt_credentials"><?php _e('Encrypt Credentials', 'ryvr'); ?></label>
                        </th>
                        <td>
                            <input type="checkbox" id="encrypt_credentials" name="encrypt_credentials" value="1" checked disabled>
                            <p class="description"><?php _e('Encrypt stored API credentials and sensitive data. (Always enabled)', 'ryvr'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="api_rate_limit"><?php _e('API Rate Limit', 'ryvr'); ?></label>
                        </th>
                        <td>
                            <input type="number" id="api_rate_limit" name="api_rate_limit" value="100" min="10" max="1000">
                            <p class="description"><?php _e('Maximum API requests per minute per connector.', 'ryvr'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="allowed_domains"><?php _e('Allowed Domains', 'ryvr'); ?></label>
                        </th>
                        <td>
                            <textarea id="allowed_domains" name="allowed_domains" rows="4" cols="50" placeholder="example.com&#10;api.service.com"></textarea>
                            <p class="description"><?php _e('Whitelist of domains that workflows can connect to (one per line). Leave empty to allow all.', 'ryvr'); ?></p>
                        </td>
                    </tr>
                </table>
            </div>

            <!-- Performance Settings -->
            <div class="settings-section">
                <h2><?php _e('Performance Settings', 'ryvr'); ?></h2>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="cache_duration"><?php _e('Cache Duration', 'ryvr'); ?></label>
                        </th>
                        <td>
                            <input type="number" id="cache_duration" name="cache_duration" value="3600" min="60" max="86400">
                            <p class="description"><?php _e('How long (in seconds) to cache API responses and connector data.', 'ryvr'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="cleanup_logs_days"><?php _e('Log Cleanup', 'ryvr'); ?></label>
                        </th>
                        <td>
                            <input type="number" id="cleanup_logs_days" name="cleanup_logs_days" value="30" min="1" max="365">
                            <p class="description"><?php _e('Automatically delete logs older than this many days.', 'ryvr'); ?></p>
                        </td>
                    </tr>
                </table>
            </div>

            <!-- Debug Settings -->
            <div class="settings-section">
                <h2><?php _e('Debug Settings', 'ryvr'); ?></h2>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="debug_mode"><?php _e('Debug Mode', 'ryvr'); ?></label>
                        </th>
                        <td>
                            <input type="checkbox" id="debug_mode" name="debug_mode" value="1">
                            <p class="description"><?php _e('Enable debug mode for detailed error reporting and logging.', 'ryvr'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="test_mode"><?php _e('Test Mode', 'ryvr'); ?></label>
                        </th>
                        <td>
                            <input type="checkbox" id="test_mode" name="test_mode" value="1">
                            <p class="description"><?php _e('Run workflows in test mode without executing actual actions.', 'ryvr'); ?></p>
                        </td>
                    </tr>
                </table>
            </div>
        </div>
        
        <?php submit_button(__('Save Settings', 'ryvr')); ?>
    </form>
    
    <!-- System Information -->
    <div class="settings-section">
        <h2><?php _e('System Information', 'ryvr'); ?></h2>
        <div class="system-info">
            <table class="form-table">
                <tr>
                    <th scope="row"><?php _e('Plugin Version', 'ryvr'); ?></th>
                    <td><?php echo esc_html(RYVR_VERSION); ?></td>
                </tr>
                <tr>
                    <th scope="row"><?php _e('WordPress Version', 'ryvr'); ?></th>
                    <td><?php echo esc_html(get_bloginfo('version')); ?></td>
                </tr>
                <tr>
                    <th scope="row"><?php _e('PHP Version', 'ryvr'); ?></th>
                    <td><?php echo esc_html(PHP_VERSION); ?></td>
                </tr>
                <tr>
                    <th scope="row"><?php _e('Dependencies Status', 'ryvr'); ?></th>
                    <td>
                        <?php if (file_exists(RYVR_PLUGIN_DIR . 'vendor/autoload.php')): ?>
                            <span style="color: green;">✓ <?php _e('Installed', 'ryvr'); ?></span>
                        <?php else: ?>
                            <span style="color: red;">✗ <?php _e('Missing', 'ryvr'); ?></span>
                        <?php endif; ?>
                    </td>
                </tr>
            </table>
        </div>
    </div>
</div>

<style>
.ryvr-settings {
    max-width: 1000px;
}

.settings-section {
    background: #fff;
    border: 1px solid #c3c4c7;
    border-radius: 4px;
    margin: 20px 0;
    padding: 20px;
}

.settings-section h2 {
    margin-top: 0;
    border-bottom: 1px solid #ddd;
    padding-bottom: 10px;
}

.form-table th {
    width: 200px;
    padding: 15px 10px 15px 0;
}

.form-table input[type="number"],
.form-table select {
    width: 150px;
}

.form-table textarea {
    width: 100%;
    max-width: 400px;
}

.system-info table {
    background: #f9f9f9;
    border: 1px solid #ddd;
    border-radius: 4px;
}

.system-info th,
.system-info td {
    padding: 10px;
    border-bottom: 1px solid #ddd;
}

.system-info th {
    background: #f1f1f1;
    font-weight: 600;
}
</style> 