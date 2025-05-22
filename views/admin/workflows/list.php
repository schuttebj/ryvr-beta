<?php
// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Get workflow manager
require_once RYVR_PLUGIN_DIR . 'src/Workflows/Manager.php';
$manager = new \Ryvr\Workflows\Manager();
$workflows = $manager->get_workflows();
?>

<div class="wrap">
    <h1 class="wp-heading-inline"><?php esc_html_e('Workflows', 'ryvr'); ?></h1>
    <a href="<?php echo esc_url(admin_url('admin.php?page=ryvr-add-workflow')); ?>" class="page-title-action"><?php esc_html_e('Add New', 'ryvr'); ?></a>
    
    <hr class="wp-header-end">
    
    <div class="ryvr-workflows-list">
        <?php if (empty($workflows)) : ?>
            <div class="ryvr-notice ryvr-notice-info">
                <p><?php esc_html_e('No workflows found. Click "Add New" to create your first workflow.', 'ryvr'); ?></p>
            </div>
        <?php else : ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th scope="col" class="column-primary"><?php esc_html_e('Name', 'ryvr'); ?></th>
                        <th scope="col"><?php esc_html_e('ID', 'ryvr'); ?></th>
                        <th scope="col"><?php esc_html_e('Description', 'ryvr'); ?></th>
                        <th scope="col"><?php esc_html_e('Status', 'ryvr'); ?></th>
                        <th scope="col"><?php esc_html_e('Steps', 'ryvr'); ?></th>
                        <th scope="col"><?php esc_html_e('Actions', 'ryvr'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($workflows as $workflow) : ?>
                        <tr>
                            <td class="column-primary">
                                <strong>
                                    <a href="<?php echo esc_url(admin_url('admin.php?page=ryvr-add-workflow&id=' . $workflow->get_id())); ?>" class="row-title">
                                        <?php echo esc_html($workflow->get_name()); ?>
                                    </a>
                                </strong>
                                <button type="button" class="toggle-row"><span class="screen-reader-text"><?php esc_html_e('Show more details', 'ryvr'); ?></span></button>
                            </td>
                            <td data-colname="<?php esc_attr_e('ID', 'ryvr'); ?>">
                                <?php echo esc_html($workflow->get_id()); ?>
                            </td>
                            <td data-colname="<?php esc_attr_e('Description', 'ryvr'); ?>">
                                <?php echo esc_html($workflow->get_description()); ?>
                            </td>
                            <td data-colname="<?php esc_attr_e('Status', 'ryvr'); ?>">
                                <?php 
                                // In a real implementation, this would show the actual status
                                echo '<span class="ryvr-status-active">Active</span>'; 
                                ?>
                            </td>
                            <td data-colname="<?php esc_attr_e('Steps', 'ryvr'); ?>">
                                <?php echo count($workflow->get_steps()); ?>
                            </td>
                            <td data-colname="<?php esc_attr_e('Actions', 'ryvr'); ?>">
                                <div class="row-actions">
                                    <span class="edit">
                                        <a href="<?php echo esc_url(admin_url('admin.php?page=ryvr-add-workflow&id=' . $workflow->get_id())); ?>" aria-label="<?php esc_attr_e('Edit this workflow', 'ryvr'); ?>">
                                            <?php esc_html_e('Edit', 'ryvr'); ?>
                                        </a> |
                                    </span>
                                    <span class="run">
                                        <a href="#" class="ryvr-workflow-run" data-workflow-id="<?php echo esc_attr($workflow->get_id()); ?>" aria-label="<?php esc_attr_e('Run this workflow', 'ryvr'); ?>">
                                            <?php esc_html_e('Run', 'ryvr'); ?>
                                        </a> |
                                    </span>
                                    <span class="delete">
                                        <a href="#" class="ryvr-workflow-delete" data-workflow-id="<?php echo esc_attr($workflow->get_id()); ?>" aria-label="<?php esc_attr_e('Delete this workflow', 'ryvr'); ?>">
                                            <?php esc_html_e('Delete', 'ryvr'); ?>
                                        </a>
                                    </span>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<!-- Run Workflow Modal -->
<div id="ryvr-workflow-run-modal" class="ryvr-modal" style="display: none;">
    <div class="ryvr-modal-content">
        <div class="ryvr-modal-header">
            <h2 id="ryvr-workflow-run-title"><?php esc_html_e('Run Workflow', 'ryvr'); ?></h2>
            <button type="button" class="ryvr-modal-close">&times;</button>
        </div>
        
        <div class="ryvr-modal-body">
            <div id="ryvr-workflow-run-form">
                <div class="ryvr-loading" style="display: none;">
                    <span class="spinner is-active"></span>
                    <p><?php esc_html_e('Running...', 'ryvr'); ?></p>
                </div>
                
                <form id="ryvr-run-form">
                    <p><?php esc_html_e('Enter any required input data for this workflow in JSON format:', 'ryvr'); ?></p>
                    <textarea id="ryvr-run-input" name="input" class="large-text code" rows="10">{}</textarea>
                    
                    <div class="ryvr-form-actions">
                        <button type="button" class="button button-primary ryvr-run-workflow">
                            <?php esc_html_e('Run Workflow', 'ryvr'); ?>
                        </button>
                    </div>
                </form>
                
                <div id="ryvr-run-results" style="display: none;">
                    <h3><?php esc_html_e('Results', 'ryvr'); ?></h3>
                    <pre id="ryvr-run-output" class="ryvr-code-output"></pre>
                </div>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
jQuery(document).ready(function($) {
    var currentWorkflowId = null;
    
    // Run workflow
    $('.ryvr-workflow-run').on('click', function(e) {
        e.preventDefault();
        var workflowId = $(this).data('workflow-id');
        currentWorkflowId = workflowId;
        
        // Get workflow name
        var workflowName = $(this).closest('tr').find('.row-title').text().trim();
        $('#ryvr-workflow-run-title').text('Run Workflow: ' + workflowName);
        
        // Show run modal
        $('#ryvr-workflow-run-modal').show();
        $('#ryvr-run-input').val('{}');
        $('#ryvr-run-results').hide();
    });
    
    // Delete workflow
    $('.ryvr-workflow-delete').on('click', function(e) {
        e.preventDefault();
        var workflowId = $(this).data('workflow-id');
        
        if (confirm(ryvrData.i18n.confirmDeleteWorkflow)) {
            deleteWorkflow(workflowId);
        }
    });
    
    // Run workflow button clicked
    $('.ryvr-run-workflow').on('click', function() {
        runWorkflow();
    });
    
    // Close modal
    $('.ryvr-modal-close').on('click', function() {
        $('.ryvr-modal').hide();
    });
    
    // Close modal when clicking outside
    $(window).on('click', function(e) {
        if ($(e.target).hasClass('ryvr-modal')) {
            $('.ryvr-modal').hide();
        }
    });
    
    // Function to run workflow
    function runWorkflow() {
        if (!currentWorkflowId) {
            return;
        }
        
        // Get input data
        var inputData = $('#ryvr-run-input').val();
        
        try {
            // Validate JSON
            JSON.parse(inputData);
        } catch (e) {
            alert(ryvrData.i18n.invalidJson);
            return;
        }
        
        // Show loading
        $('.ryvr-loading').show();
        $('#ryvr-run-results').hide();
        
        // Make AJAX request
        $.ajax({
            url: ryvrData.ajaxUrl,
            type: 'POST',
            data: {
                action: 'ryvr_workflow_execute',
                id: currentWorkflowId,
                input: inputData,
                nonce: ryvrData.nonce
            },
            success: function(response) {
                $('.ryvr-loading').hide();
                
                if (response.success) {
                    // Show results
                    $('#ryvr-run-output').text(JSON.stringify(response.data.result, null, 2));
                    $('#ryvr-run-results').show();
                } else {
                    alert(response.data.message);
                }
            },
            error: function() {
                $('.ryvr-loading').hide();
                alert(ryvrData.i18n.errorRunningWorkflow);
            }
        });
    }
    
    // Function to delete workflow
    function deleteWorkflow(workflowId) {
        $.ajax({
            url: ryvrData.ajaxUrl,
            type: 'POST',
            data: {
                action: 'ryvr_workflow_delete',
                id: workflowId,
                nonce: ryvrData.nonce
            },
            success: function(response) {
                if (response.success) {
                    // Reload page
                    location.reload();
                } else {
                    alert(response.data.message);
                }
            },
            error: function() {
                alert(ryvrData.i18n.errorDeletingWorkflow);
            }
        });
    }
});
</script> 