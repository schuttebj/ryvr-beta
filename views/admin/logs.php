<?php
/**
 * Ryvr Logs Admin View
 *
 * @package Ryvr
 * @since 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <div class="ryvr-logs">
        <!-- Filter Section -->
        <div class="logs-filters">
            <form method="get" action="">
                <input type="hidden" name="page" value="ryvr-logs">
                
                <div class="filter-row">
                    <label for="log-level"><?php _e('Log Level:', 'ryvr'); ?></label>
                    <select name="level" id="log-level">
                        <option value=""><?php _e('All Levels', 'ryvr'); ?></option>
                        <option value="error"><?php _e('Error', 'ryvr'); ?></option>
                        <option value="warning"><?php _e('Warning', 'ryvr'); ?></option>
                        <option value="info"><?php _e('Info', 'ryvr'); ?></option>
                        <option value="debug"><?php _e('Debug', 'ryvr'); ?></option>
                    </select>
                    
                    <label for="log-connector"><?php _e('Connector:', 'ryvr'); ?></label>
                    <select name="connector" id="log-connector">
                        <option value=""><?php _e('All Connectors', 'ryvr'); ?></option>
                        <option value="openai"><?php _e('OpenAI', 'ryvr'); ?></option>
                        <option value="dataforseo"><?php _e('DataForSEO', 'ryvr'); ?></option>
                    </select>
                    
                    <label for="log-date"><?php _e('Date:', 'ryvr'); ?></label>
                    <input type="date" name="date" id="log-date" value="<?php echo esc_attr($_GET['date'] ?? ''); ?>">
                    
                    <input type="submit" class="button" value="<?php _e('Filter', 'ryvr'); ?>">
                </div>
            </form>
        </div>

        <!-- Logs Table -->
        <div class="logs-table-container">
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th scope="col" class="column-timestamp"><?php _e('Timestamp', 'ryvr'); ?></th>
                        <th scope="col" class="column-level"><?php _e('Level', 'ryvr'); ?></th>
                        <th scope="col" class="column-source"><?php _e('Source', 'ryvr'); ?></th>
                        <th scope="col" class="column-message"><?php _e('Message', 'ryvr'); ?></th>
                        <th scope="col" class="column-actions"><?php _e('Actions', 'ryvr'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // TODO: Replace with actual log data
                    $sample_logs = [
                        [
                            'timestamp' => current_time('mysql'),
                            'level' => 'info',
                            'source' => 'System',
                            'message' => 'Ryvr plugin initialized successfully'
                        ],
                        [
                            'timestamp' => current_time('mysql'),
                            'level' => 'info',
                            'source' => 'Dependencies',
                            'message' => 'Minimal vendor structure created successfully'
                        ]
                    ];
                    
                    if (empty($sample_logs)) {
                        echo '<tr><td colspan="5" class="no-logs">' . __('No logs found.', 'ryvr') . '</td></tr>';
                    } else {
                        foreach ($sample_logs as $log) {
                            $level_class = 'log-level-' . esc_attr($log['level']);
                            ?>
                            <tr class="<?php echo $level_class; ?>">
                                <td class="column-timestamp">
                                    <?php echo esc_html(mysql2date(get_option('date_format') . ' ' . get_option('time_format'), $log['timestamp'])); ?>
                                </td>
                                <td class="column-level">
                                    <span class="log-level-badge level-<?php echo esc_attr($log['level']); ?>">
                                        <?php echo esc_html(ucfirst($log['level'])); ?>
                                    </span>
                                </td>
                                <td class="column-source">
                                    <?php echo esc_html($log['source']); ?>
                                </td>
                                <td class="column-message">
                                    <?php echo esc_html($log['message']); ?>
                                </td>
                                <td class="column-actions">
                                    <button type="button" class="button button-small view-details" data-log-id="1">
                                        <?php _e('Details', 'ryvr'); ?>
                                    </button>
                                </td>
                            </tr>
                            <?php
                        }
                    }
                    ?>
                </tbody>
            </table>
        </div>

        <!-- Log Actions -->
        <div class="logs-actions">
            <button type="button" class="button button-secondary" id="clear-logs">
                <?php _e('Clear All Logs', 'ryvr'); ?>
            </button>
            <button type="button" class="button button-secondary" id="export-logs">
                <?php _e('Export Logs', 'ryvr'); ?>
            </button>
        </div>
    </div>
</div>

<style>
.ryvr-logs {
    max-width: 1200px;
}

.logs-filters {
    background: #fff;
    border: 1px solid #c3c4c7;
    border-radius: 4px;
    padding: 15px;
    margin: 20px 0;
}

.filter-row {
    display: flex;
    align-items: center;
    gap: 15px;
    flex-wrap: wrap;
}

.filter-row label {
    font-weight: 600;
    white-space: nowrap;
}

.filter-row select,
.filter-row input[type="date"] {
    min-width: 120px;
}

.logs-table-container {
    background: #fff;
    border: 1px solid #c3c4c7;
    border-radius: 4px;
    margin: 20px 0;
}

.column-timestamp {
    width: 15%;
}

.column-level {
    width: 10%;
}

.column-source {
    width: 15%;
}

.column-message {
    width: 50%;
}

.column-actions {
    width: 10%;
}

.log-level-badge {
    padding: 3px 8px;
    border-radius: 3px;
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
}

.level-error {
    background: #d63638;
    color: #fff;
}

.level-warning {
    background: #dba617;
    color: #fff;
}

.level-info {
    background: #2271b1;
    color: #fff;
}

.level-debug {
    background: #646970;
    color: #fff;
}

.no-logs {
    text-align: center;
    padding: 30px;
    color: #646970;
    font-style: italic;
}

.logs-actions {
    margin: 20px 0;
}

.logs-actions .button {
    margin-right: 10px;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Clear logs functionality
    document.getElementById('clear-logs')?.addEventListener('click', function() {
        if (confirm('<?php _e('Are you sure you want to clear all logs? This action cannot be undone.', 'ryvr'); ?>')) {
            // TODO: Implement clear logs functionality
            alert('<?php _e('Clear logs functionality will be implemented.', 'ryvr'); ?>');
        }
    });
    
    // Export logs functionality
    document.getElementById('export-logs')?.addEventListener('click', function() {
        // TODO: Implement export logs functionality
        alert('<?php _e('Export logs functionality will be implemented.', 'ryvr'); ?>');
    });
    
    // View details functionality
    document.querySelectorAll('.view-details').forEach(function(button) {
        button.addEventListener('click', function() {
            // TODO: Implement view details functionality
            alert('<?php _e('View details functionality will be implemented.', 'ryvr'); ?>');
        });
    });
});
</script> 