<?php
/**
 * Ryvr Dashboard Admin View
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
    
    <div class="ryvr-dashboard">
        <!-- Welcome Section -->
        <div class="welcome-panel">
            <h2><?php _e('Welcome to Ryvr', 'ryvr'); ?></h2>
            <p><?php _e('Automate your marketing workflows with powerful connectors and intelligent automation.', 'ryvr'); ?></p>
        </div>

        <!-- Quick Stats -->
        <div class="ryvr-stats-grid">
            <div class="ryvr-stat-card">
                <h3><?php _e('Active Workflows', 'ryvr'); ?></h3>
                <div class="stat-number">
                    <?php
                    // TODO: Get actual workflow count
                    echo '0';
                    ?>
                </div>
                <a href="<?php echo admin_url('admin.php?page=ryvr-workflows'); ?>" class="button">
                    <?php _e('View Workflows', 'ryvr'); ?>
                </a>
            </div>

            <div class="ryvr-stat-card">
                <h3><?php _e('Connected Services', 'ryvr'); ?></h3>
                <div class="stat-number">
                    <?php
                    // TODO: Get actual connector count
                    echo '0';
                    ?>
                </div>
                <a href="<?php echo admin_url('admin.php?page=ryvr-connectors'); ?>" class="button">
                    <?php _e('Manage Connectors', 'ryvr'); ?>
                </a>
            </div>

            <div class="ryvr-stat-card">
                <h3><?php _e('Recent Executions', 'ryvr'); ?></h3>
                <div class="stat-number">
                    <?php
                    // TODO: Get actual execution count
                    echo '0';
                    ?>
                </div>
                <a href="<?php echo admin_url('admin.php?page=ryvr-logs'); ?>" class="button">
                    <?php _e('View Logs', 'ryvr'); ?>
                </a>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="ryvr-quick-actions">
            <h2><?php _e('Quick Actions', 'ryvr'); ?></h2>
            <div class="actions-grid">
                <a href="<?php echo admin_url('admin.php?page=ryvr-add-workflow'); ?>" class="action-card">
                    <div class="action-icon">
                        <span class="dashicons dashicons-plus-alt2"></span>
                    </div>
                    <h3><?php _e('Create Workflow', 'ryvr'); ?></h3>
                    <p><?php _e('Build a new automation workflow', 'ryvr'); ?></p>
                </a>

                <a href="<?php echo admin_url('admin.php?page=ryvr-connectors'); ?>" class="action-card">
                    <div class="action-icon">
                        <span class="dashicons dashicons-admin-plugins"></span>
                    </div>
                    <h3><?php _e('Setup Connectors', 'ryvr'); ?></h3>
                    <p><?php _e('Connect to external services', 'ryvr'); ?></p>
                </a>

                <a href="<?php echo admin_url('admin.php?page=ryvr-settings'); ?>" class="action-card">
                    <div class="action-icon">
                        <span class="dashicons dashicons-admin-settings"></span>
                    </div>
                    <h3><?php _e('Configure Settings', 'ryvr'); ?></h3>
                    <p><?php _e('Customize Ryvr preferences', 'ryvr'); ?></p>
                </a>
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="ryvr-recent-activity">
            <h2><?php _e('Recent Activity', 'ryvr'); ?></h2>
            <div class="activity-list">
                <p class="no-activity"><?php _e('No recent activity. Start by creating your first workflow!', 'ryvr'); ?></p>
                <!-- TODO: Display actual recent activity -->
            </div>
        </div>
    </div>
</div>

<style>
.ryvr-dashboard {
    max-width: 1200px;
}

.welcome-panel {
    background: #fff;
    border: 1px solid #c3c4c7;
    box-shadow: 0 1px 1px rgba(0,0,0,.04);
    padding: 23px 10px;
    margin: 20px 0;
    border-radius: 4px;
    text-align: center;
}

.ryvr-stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin: 20px 0;
}

.ryvr-stat-card {
    background: #fff;
    border: 1px solid #c3c4c7;
    border-radius: 4px;
    padding: 20px;
    text-align: center;
}

.ryvr-stat-card h3 {
    margin-top: 0;
    color: #1d2327;
}

.stat-number {
    font-size: 2.5em;
    font-weight: bold;
    color: #2271b1;
    margin: 10px 0;
}

.ryvr-quick-actions {
    margin: 30px 0;
}

.actions-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 20px;
    margin: 20px 0;
}

.action-card {
    background: #fff;
    border: 1px solid #c3c4c7;
    border-radius: 4px;
    padding: 20px;
    text-decoration: none;
    color: inherit;
    transition: box-shadow 0.3s ease;
}

.action-card:hover {
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    text-decoration: none;
    color: inherit;
}

.action-icon {
    text-align: center;
    margin-bottom: 15px;
}

.action-icon .dashicons {
    font-size: 2em;
    color: #2271b1;
}

.action-card h3 {
    margin: 0 0 10px 0;
    color: #1d2327;
}

.action-card p {
    margin: 0;
    color: #646970;
}

.ryvr-recent-activity {
    background: #fff;
    border: 1px solid #c3c4c7;
    border-radius: 4px;
    padding: 20px;
    margin: 20px 0;
}

.activity-list {
    margin-top: 15px;
}

.no-activity {
    color: #646970;
    font-style: italic;
    text-align: center;
    padding: 20px;
}
</style> 