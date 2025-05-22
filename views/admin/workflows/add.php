<?php
// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Check if we're editing an existing workflow
$workflow_id = isset($_GET['id']) ? sanitize_text_field($_GET['id']) : '';
$editing = !empty($workflow_id);

// Get workflow manager
require_once RYVR_PLUGIN_DIR . 'src/Workflows/Manager.php';
$manager = new \Ryvr\Workflows\Manager();

// Get the workflow if editing
$workflow = null;
$workflow_data = '{}';

if ($editing) {
    $workflow = $manager->get_workflow($workflow_id);
    
    if ($workflow) {
        $workflow_data = json_encode($workflow->get_definition(), JSON_PRETTY_PRINT);
    }
}

// Get all available connectors
require_once RYVR_PLUGIN_DIR . 'src/Connectors/Manager.php';
$connector_manager = new \Ryvr\Connectors\Manager();
$connectors = $connector_manager->get_connectors();
?>

<div class="wrap">
    <h1 class="wp-heading-inline">
        <?php echo $editing ? esc_html__('Edit Workflow', 'ryvr') : esc_html__('Add New Workflow', 'ryvr'); ?>
    </h1>
    <a href="<?php echo esc_url(admin_url('admin.php?page=ryvr-workflows')); ?>" class="page-title-action"><?php esc_html_e('Back to Workflows', 'ryvr'); ?></a>
    
    <hr class="wp-header-end">
    
    <div class="ryvr-workflow-editor">
        <div class="ryvr-workflow-editor-header">
            <div class="ryvr-workflow-editor-actions">
                <button type="button" class="button button-primary ryvr-save-workflow">
                    <?php esc_html_e('Save Workflow', 'ryvr'); ?>
                </button>
                
                <button type="button" class="button ryvr-validate-workflow">
                    <?php esc_html_e('Validate', 'ryvr'); ?>
                </button>
                
                <?php if ($editing) : ?>
                    <button type="button" class="button ryvr-run-workflow" data-workflow-id="<?php echo esc_attr($workflow_id); ?>">
                        <?php esc_html_e('Run', 'ryvr'); ?>
                    </button>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="ryvr-workflow-editor-body">
            <div class="ryvr-workflow-editor-main">
                <div class="ryvr-code-editor-container">
                    <h2><?php esc_html_e('Workflow Definition (JSON)', 'ryvr'); ?></h2>
                    <p class="description"><?php esc_html_e('Define your workflow using JSON. The definition must include an id, name, and steps array.', 'ryvr'); ?></p>
                    <textarea id="ryvr-workflow-json" class="ryvr-code-editor"><?php echo esc_textarea($workflow_data); ?></textarea>
                </div>
            </div>
            
            <div class="ryvr-workflow-editor-sidebar">
                <div class="ryvr-panel">
                    <h3><?php esc_html_e('Available Connectors', 'ryvr'); ?></h3>
                    <ul class="ryvr-connector-list">
                        <?php foreach ($connectors as $connector) : ?>
                            <li class="ryvr-connector-item" data-connector-id="<?php echo esc_attr($connector->get_id()); ?>">
                                <span class="ryvr-connector-icon" style="background-image: url('<?php echo esc_url($connector->get_icon_url()); ?>')"></span>
                                <span class="ryvr-connector-name"><?php echo esc_html($connector->get_name()); ?></span>
                                <button type="button" class="button button-small ryvr-view-connector-actions" data-connector-id="<?php echo esc_attr($connector->get_id()); ?>">
                                    <?php esc_html_e('View Actions', 'ryvr'); ?>
                                </button>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                
                <div class="ryvr-panel">
                    <h3><?php esc_html_e('Templates', 'ryvr'); ?></h3>
                    <button type="button" class="button ryvr-load-template" data-template="basic">
                        <?php esc_html_e('Basic Workflow', 'ryvr'); ?>
                    </button>
                    <button type="button" class="button ryvr-load-template" data-template="decision">
                        <?php esc_html_e('Decision Workflow', 'ryvr'); ?>
                    </button>
                </div>
                
                <div class="ryvr-panel">
                    <h3><?php esc_html_e('Documentation', 'ryvr'); ?></h3>
                    <div class="ryvr-doc-section">
                        <h4><?php esc_html_e('Step Types', 'ryvr'); ?></h4>
                        <ul>
                            <li><strong>action</strong>: <?php esc_html_e('Executes an action on a connector', 'ryvr'); ?></li>
                            <li><strong>decision</strong>: <?php esc_html_e('Evaluates a condition', 'ryvr'); ?></li>
                            <li><strong>transformer</strong>: <?php esc_html_e('Processes data using a template', 'ryvr'); ?></li>
                        </ul>
                    </div>
                    <div class="ryvr-doc-section">
                        <h4><?php esc_html_e('Template Variables', 'ryvr'); ?></h4>
                        <p><?php esc_html_e('Use {{step_id.result}} to access the result of a previous step.', 'ryvr'); ?></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- View Connector Actions Modal -->
<div id="ryvr-connector-actions-modal" class="ryvr-modal" style="display: none;">
    <div class="ryvr-modal-content">
        <div class="ryvr-modal-header">
            <h2 id="ryvr-connector-actions-title"><?php esc_html_e('Connector Actions', 'ryvr'); ?></h2>
            <button type="button" class="ryvr-modal-close">&times;</button>
        </div>
        
        <div class="ryvr-modal-body">
            <div id="ryvr-connector-actions-list">
                <div class="ryvr-loading" style="display: none;">
                    <span class="spinner is-active"></span>
                    <p><?php esc_html_e('Loading...', 'ryvr'); ?></p>
                </div>
                
                <div id="ryvr-actions-content"></div>
            </div>
        </div>
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
                        <button type="button" class="button button-primary ryvr-execute-workflow">
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
    var currentConnectorId = null;
    var currentWorkflowId = '<?php echo esc_js($workflow_id); ?>';
    
    // Initialize the code editor
    var editor = null;
    
    // Use CodeMirror if available (should be included in WordPress 4.9+)
    if (typeof wp !== 'undefined' && wp.codeEditor) {
        var editorSettings = wp.codeEditor.defaultSettings ? _.clone(wp.codeEditor.defaultSettings) : {};
        editorSettings.codemirror = _.extend({}, editorSettings.codemirror, {
            mode: 'application/json',
            lineNumbers: true,
            indentUnit: 4,
            indentWithTabs: false,
            lineWrapping: true,
            autoCloseBrackets: true,
            matchBrackets: true,
            extraKeys: {
                'Ctrl-Space': 'autocomplete'
            }
        });
        
        editor = wp.codeEditor.initialize($('#ryvr-workflow-json'), editorSettings);
    }
    
    // View connector actions
    $('.ryvr-view-connector-actions').on('click', function() {
        var connectorId = $(this).data('connector-id');
        currentConnectorId = connectorId;
        
        // Get connector name
        var connectorName = $(this).closest('.ryvr-connector-item').find('.ryvr-connector-name').text();
        $('#ryvr-connector-actions-title').text(connectorName + ' ' + ryvrData.i18n.actions);
        
        // Show modal and load actions
        $('#ryvr-connector-actions-modal').show();
        loadConnectorActions(connectorId);
    });
    
    // Run workflow
    $('.ryvr-run-workflow').on('click', function() {
        $('#ryvr-workflow-run-modal').show();
        $('#ryvr-run-input').val('{}');
        $('#ryvr-run-results').hide();
    });
    
    // Execute workflow button clicked
    $('.ryvr-execute-workflow').on('click', function() {
        executeWorkflow();
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
    
    // Load template
    $('.ryvr-load-template').on('click', function() {
        var template = $(this).data('template');
        
        if (template === 'basic') {
            var basicTemplate = {
                "id": "basic-workflow",
                "name": "Basic Workflow",
                "description": "A simple workflow example",
                "steps": [
                    {
                        "id": "step1",
                        "type": "action",
                        "connector": "openai",
                        "action": "generate_text",
                        "params": {
                            "model": "gpt-3.5-turbo",
                            "prompt": "Generate a short blog post about WordPress plugins."
                        }
                    }
                ]
            };
            
            setEditorValue(JSON.stringify(basicTemplate, null, 4));
        } else if (template === 'decision') {
            var decisionTemplate = {
                "id": "decision-workflow",
                "name": "Decision Workflow",
                "description": "A workflow with a decision step",
                "steps": [
                    {
                        "id": "step1",
                        "type": "action",
                        "connector": "dataforseo",
                        "action": "keyword_research",
                        "params": {
                            "keyword": "wordpress plugin",
                            "location_code": "2840",
                            "language_code": "en"
                        }
                    },
                    {
                        "id": "decision",
                        "type": "decision",
                        "condition": "step1.search_volume > 1000"
                    },
                    {
                        "id": "step2",
                        "type": "transformer",
                        "template": "Keyword '{{step1.keyword_info.keyword}}' has high search volume: {{step1.search_volume}}"
                    }
                ]
            };
            
            setEditorValue(JSON.stringify(decisionTemplate, null, 4));
        }
    });
    
    // Save workflow
    $('.ryvr-save-workflow').on('click', function() {
        saveWorkflow();
    });
    
    // Validate workflow
    $('.ryvr-validate-workflow').on('click', function() {
        validateWorkflow();
    });
    
    // Function to load connector actions
    function loadConnectorActions(connectorId) {
        // Show loading
        $('.ryvr-loading').show();
        $('#ryvr-actions-content').empty();
        
        // Make AJAX request
        $.ajax({
            url: ryvrData.ajaxUrl,
            type: 'POST',
            data: {
                action: 'ryvr_connector_get_actions',
                connector_id: connectorId,
                nonce: ryvrData.nonce
            },
            success: function(response) {
                $('.ryvr-loading').hide();
                
                if (response.success && response.data.actions) {
                    var actions = response.data.actions;
                    var actionsHtml = '<ul class="ryvr-actions-list">';
                    
                    for (var id in actions) {
                        var action = actions[id];
                        
                        actionsHtml += '<li class="ryvr-action-item">';
                        actionsHtml += '<h4>' + action.label + '</h4>';
                        actionsHtml += '<p>' + action.description + '</p>';
                        
                        // Action parameters
                        if (action.fields && Object.keys(action.fields).length > 0) {
                            actionsHtml += '<h5>' + ryvrData.i18n.parameters + '</h5>';
                            actionsHtml += '<table class="widefat" style="width: 100%;">';
                            actionsHtml += '<thead><tr><th>' + ryvrData.i18n.name + '</th><th>' + ryvrData.i18n.type + '</th><th>' + ryvrData.i18n.description + '</th></tr></thead>';
                            actionsHtml += '<tbody>';
                            
                            for (var paramId in action.fields) {
                                var param = action.fields[paramId];
                                actionsHtml += '<tr>';
                                actionsHtml += '<td>' + paramId + (param.required ? ' *' : '') + '</td>';
                                actionsHtml += '<td>' + param.type + '</td>';
                                actionsHtml += '<td>' + (param.description || '') + '</td>';
                                actionsHtml += '</tr>';
                            }
                            
                            actionsHtml += '</tbody></table>';
                        }
                        
                        // Insert into workflow button
                        actionsHtml += '<p><button type="button" class="button ryvr-insert-action" data-connector="' + connectorId + '" data-action="' + id + '">' + ryvrData.i18n.insertIntoWorkflow + '</button></p>';
                        
                        actionsHtml += '</li>';
                    }
                    
                    actionsHtml += '</ul>';
                    
                    $('#ryvr-actions-content').html(actionsHtml);
                    
                    // Handle insert action buttons
                    $('.ryvr-insert-action').on('click', function() {
                        var connectorId = $(this).data('connector');
                        var actionId = $(this).data('action');
                        insertActionIntoWorkflow(connectorId, actionId, actions[actionId]);
                    });
                } else {
                    $('#ryvr-actions-content').html('<p>' + ryvrData.i18n.noActionsFound + '</p>');
                }
            },
            error: function() {
                $('.ryvr-loading').hide();
                $('#ryvr-actions-content').html('<p>' + ryvrData.i18n.errorLoadingActions + '</p>');
            }
        });
    }
    
    // Function to insert an action into the workflow
    function insertActionIntoWorkflow(connectorId, actionId, actionData) {
        // Get the current workflow JSON
        var workflowJson = getEditorValue();
        
        try {
            var workflow = JSON.parse(workflowJson);
            
            // Create a new step
            var newStepId = 'step' + (workflow.steps ? workflow.steps.length + 1 : 1);
            var newStep = {
                "id": newStepId,
                "type": "action",
                "connector": connectorId,
                "action": actionId,
                "params": {}
            };
            
            // Add default parameters
            if (actionData.fields) {
                for (var paramId in actionData.fields) {
                    var param = actionData.fields[paramId];
                    if (param.default !== undefined) {
                        newStep.params[paramId] = param.default;
                    }
                }
            }
            
            // Add the step to the workflow
            if (!workflow.steps) {
                workflow.steps = [];
            }
            
            workflow.steps.push(newStep);
            
            // Update the editor
            setEditorValue(JSON.stringify(workflow, null, 4));
            
            // Close the modal
            $('#ryvr-connector-actions-modal').hide();
        } catch (e) {
            alert(ryvrData.i18n.invalidWorkflowJson);
        }
    }
    
    // Function to execute the workflow
    function executeWorkflow() {
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
    
    // Function to save the workflow
    function saveWorkflow() {
        var workflowJson = getEditorValue();
        
        try {
            // Validate JSON
            JSON.parse(workflowJson);
        } catch (e) {
            alert(ryvrData.i18n.invalidJson);
            return;
        }
        
        // Show loading indicator
        // (You would add this in a real implementation)
        
        // Make AJAX request
        $.ajax({
            url: ryvrData.ajaxUrl,
            type: 'POST',
            data: {
                action: 'ryvr_workflow_save',
                definition: workflowJson,
                nonce: ryvrData.nonce
            },
            success: function(response) {
                if (response.success) {
                    // Show success message
                    alert(ryvrData.i18n.workflowSaved);
                    
                    // Update currentWorkflowId if this is a new workflow
                    if (!currentWorkflowId && response.data.id) {
                        currentWorkflowId = response.data.id;
                        
                        // Update URL to edit mode
                        var newUrl = window.location.href;
                        if (newUrl.indexOf('?') !== -1) {
                            newUrl = newUrl.substring(0, newUrl.indexOf('?'));
                        }
                        newUrl += '?page=ryvr-add-workflow&id=' + currentWorkflowId;
                        
                        window.history.replaceState({}, document.title, newUrl);
                    }
                } else {
                    alert(response.data.message);
                }
            },
            error: function() {
                alert(ryvrData.i18n.errorSavingWorkflow);
            }
        });
    }
    
    // Function to validate the workflow
    function validateWorkflow() {
        var workflowJson = getEditorValue();
        
        try {
            var workflow = JSON.parse(workflowJson);
            
            // Basic validation
            var errors = [];
            
            if (!workflow.id) {
                errors.push(ryvrData.i18n.missingWorkflowId);
            }
            
            if (!workflow.name) {
                errors.push(ryvrData.i18n.missingWorkflowName);
            }
            
            if (!workflow.steps || !Array.isArray(workflow.steps) || workflow.steps.length === 0) {
                errors.push(ryvrData.i18n.missingWorkflowSteps);
            } else {
                // Validate steps
                workflow.steps.forEach(function(step, index) {
                    if (!step.id) {
                        errors.push(ryvrData.i18n.missingStepId.replace('%d', index + 1));
                    }
                    
                    if (!step.type) {
                        errors.push(ryvrData.i18n.missingStepType.replace('%d', index + 1));
                    } else if (step.type === 'action') {
                        if (!step.connector) {
                            errors.push(ryvrData.i18n.missingStepConnector.replace('%d', index + 1));
                        }
                        
                        if (!step.action) {
                            errors.push(ryvrData.i18n.missingStepAction.replace('%d', index + 1));
                        }
                    } else if (step.type === 'decision' && !step.condition) {
                        errors.push(ryvrData.i18n.missingStepCondition.replace('%d', index + 1));
                    } else if (step.type === 'transformer' && !step.template) {
                        errors.push(ryvrData.i18n.missingStepTemplate.replace('%d', index + 1));
                    }
                });
            }
            
            if (errors.length > 0) {
                alert(ryvrData.i18n.validationErrors + '\n\n' + errors.join('\n'));
            } else {
                alert(ryvrData.i18n.workflowValid);
            }
        } catch (e) {
            alert(ryvrData.i18n.invalidJson);
        }
    }
    
    // Function to get editor value
    function getEditorValue() {
        if (editor && editor.codemirror) {
            return editor.codemirror.getValue();
        } else {
            return $('#ryvr-workflow-json').val();
        }
    }
    
    // Function to set editor value
    function setEditorValue(value) {
        if (editor && editor.codemirror) {
            editor.codemirror.setValue(value);
        } else {
            $('#ryvr-workflow-json').val(value);
        }
    }
});
</script>

<style type="text/css">
.ryvr-workflow-editor {
    display: flex;
    flex-direction: column;
    gap: 20px;
    margin-top: 20px;
}

.ryvr-workflow-editor-header {
    display: flex;
    justify-content: flex-end;
}

.ryvr-workflow-editor-body {
    display: flex;
    gap: 20px;
}

.ryvr-workflow-editor-main {
    flex: 3;
}

.ryvr-workflow-editor-sidebar {
    flex: 1;
    min-width: 250px;
}

.ryvr-panel {
    background: #fff;
    border: 1px solid #ccd0d4;
    box-shadow: 0 1px 1px rgba(0,0,0,0.04);
    margin-bottom: 20px;
    padding: 15px;
}

.ryvr-panel h3 {
    margin-top: 0;
    padding-bottom: 10px;
    border-bottom: 1px solid #eee;
}

.ryvr-code-editor-container {
    background: #fff;
    border: 1px solid #ccd0d4;
    box-shadow: 0 1px 1px rgba(0,0,0,0.04);
    padding: 15px;
}

.ryvr-code-editor {
    width: 100%;
    min-height: 500px;
    font-family: Consolas, Monaco, monospace;
    line-height: 1.5;
}

.CodeMirror {
    height: 500px;
    border: 1px solid #ddd;
}

.ryvr-connector-list {
    margin: 0;
    padding: 0;
}

.ryvr-connector-item {
    display: flex;
    align-items: center;
    padding: 10px 0;
    border-bottom: 1px solid #eee;
}

.ryvr-connector-icon {
    width: 24px;
    height: 24px;
    background-size: contain;
    background-position: center;
    background-repeat: no-repeat;
    margin-right: 10px;
}

.ryvr-connector-name {
    flex: 1;
}

.ryvr-actions-list {
    margin: 0;
    padding: 0;
    list-style: none;
}

.ryvr-action-item {
    margin-bottom: 20px;
    padding-bottom: 20px;
    border-bottom: 1px solid #eee;
}

.ryvr-action-item:last-child {
    border-bottom: none;
    margin-bottom: 0;
    padding-bottom: 0;
}

.ryvr-doc-section {
    margin-bottom: 15px;
}

.ryvr-doc-section h4 {
    margin: 0 0 5px 0;
}

.ryvr-doc-section ul {
    margin: 0 0 10px 20px;
}

.ryvr-code-output {
    background: #f5f5f5;
    padding: 10px;
    overflow: auto;
    max-height: 300px;
    border: 1px solid #ddd;
}
</style> 