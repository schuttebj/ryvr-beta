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
            <!-- Visual Builder Tab Navigation -->
            <div class="ryvr-editor-tabs">
                <button type="button" class="ryvr-tab-button active" data-tab="visual">
                    <?php esc_html_e('Visual Builder', 'ryvr'); ?>
                </button>
                <button type="button" class="ryvr-tab-button" data-tab="json">
                    <?php esc_html_e('JSON Editor', 'ryvr'); ?>
                </button>
            </div>

            <div class="ryvr-workflow-editor-main">
                <!-- Visual Builder -->
                <div id="ryvr-visual-builder" class="ryvr-tab-content active">
                    <div class="ryvr-workflow-canvas-container">
                        <div class="ryvr-workflow-toolbar">
                            <div class="ryvr-workflow-info">
                                <input type="text" id="workflow-name" placeholder="<?php esc_attr_e('Workflow Name', 'ryvr'); ?>" class="ryvr-workflow-name-input">
                                <textarea id="workflow-description" placeholder="<?php esc_attr_e('Workflow Description', 'ryvr'); ?>" class="ryvr-workflow-description-input"></textarea>
                            </div>
                            <div class="ryvr-workflow-controls">
                                <button type="button" class="button ryvr-zoom-in">+</button>
                                <button type="button" class="button ryvr-zoom-out">-</button>
                                <button type="button" class="button ryvr-zoom-fit"><?php esc_html_e('Fit', 'ryvr'); ?></button>
                            </div>
                        </div>
                        
                        <div class="ryvr-workflow-canvas" id="workflow-canvas">
                            <div class="ryvr-canvas-grid"></div>
                            <div class="ryvr-workflow-steps" id="workflow-steps">
                                <!-- Steps will be dynamically added here -->
                            </div>
                            <svg class="ryvr-connections-svg" id="connections-svg">
                                <!-- Connection lines will be drawn here -->
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- JSON Editor -->
                <div id="ryvr-json-editor" class="ryvr-tab-content">
                    <div class="ryvr-code-editor-container">
                        <h2><?php esc_html_e('Workflow Definition (JSON)', 'ryvr'); ?></h2>
                        <p class="description"><?php esc_html_e('Define your workflow using JSON. The definition must include an id, name, and steps array.', 'ryvr'); ?></p>
                        <textarea id="ryvr-workflow-json" class="ryvr-code-editor"><?php echo esc_textarea($workflow_data); ?></textarea>
                    </div>
                </div>
            </div>
        </div>
            
            <div class="ryvr-workflow-editor-sidebar">
                <!-- Component Palette for Visual Builder -->
                <div class="ryvr-panel ryvr-visual-only">
                    <h3><?php esc_html_e('Components', 'ryvr'); ?></h3>
                    
                    <div class="ryvr-component-category">
                        <h4><?php esc_html_e('Flow Control', 'ryvr'); ?></h4>
                        <div class="ryvr-component-list">
                            <div class="ryvr-component-item" data-type="start" draggable="true">
                                <div class="ryvr-component-icon start-icon">▶</div>
                                <span><?php esc_html_e('Start', 'ryvr'); ?></span>
                            </div>
                            <div class="ryvr-component-item" data-type="end" draggable="true">
                                <div class="ryvr-component-icon end-icon">⏹</div>
                                <span><?php esc_html_e('End', 'ryvr'); ?></span>
                            </div>
                            <div class="ryvr-component-item" data-type="decision" draggable="true">
                                <div class="ryvr-component-icon decision-icon">◆</div>
                                <span><?php esc_html_e('Decision', 'ryvr'); ?></span>
                            </div>
                        </div>
                    </div>

                    <div class="ryvr-component-category">
                        <h4><?php esc_html_e('Connectors', 'ryvr'); ?></h4>
                        <div class="ryvr-component-list">
                            <?php foreach ($connectors as $connector) : ?>
                                <div class="ryvr-component-item" data-type="connector" data-connector-id="<?php echo esc_attr($connector->get_id()); ?>" draggable="true">
                                    <div class="ryvr-component-icon connector-icon">🔗</div>
                                    <span><?php echo esc_html($connector->get_name()); ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="ryvr-component-category">
                        <h4><?php esc_html_e('Data Processing', 'ryvr'); ?></h4>
                        <div class="ryvr-component-list">
                            <div class="ryvr-component-item" data-type="transformer" draggable="true">
                                <div class="ryvr-component-icon transformer-icon">⚙</div>
                                <span><?php esc_html_e('Transform', 'ryvr'); ?></span>
                            </div>
                            <div class="ryvr-component-item" data-type="filter" draggable="true">
                                <div class="ryvr-component-icon filter-icon">🔍</div>
                                <span><?php esc_html_e('Filter', 'ryvr'); ?></span>
                            </div>
                            <div class="ryvr-component-item" data-type="mapper" draggable="true">
                                <div class="ryvr-component-icon mapper-icon">📋</div>
                                <span><?php esc_html_e('Map Data', 'ryvr'); ?></span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Traditional Connector List for JSON Editor -->
                <div class="ryvr-panel ryvr-json-only" style="display: none;">
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

<!-- Step Configuration Modal -->
<div id="ryvr-step-config-modal" class="ryvr-modal" style="display: none;">
    <div class="ryvr-modal-content ryvr-step-modal">
        <div class="ryvr-modal-header">
            <h2 id="ryvr-step-config-title"><?php esc_html_e('Configure Step', 'ryvr'); ?></h2>
            <button type="button" class="ryvr-modal-close">&times;</button>
        </div>
        
        <div class="ryvr-modal-body">
            <div id="ryvr-step-config-form">
                <div class="ryvr-step-basic-info">
                    <div class="ryvr-form-row">
                        <label for="step-name"><?php esc_html_e('Step Name', 'ryvr'); ?></label>
                        <input type="text" id="step-name" class="regular-text" placeholder="<?php esc_attr_e('Enter step name', 'ryvr'); ?>">
                    </div>
                    <div class="ryvr-form-row">
                        <label for="step-description"><?php esc_html_e('Description', 'ryvr'); ?></label>
                        <textarea id="step-description" class="regular-text" placeholder="<?php esc_attr_e('Enter step description', 'ryvr'); ?>"></textarea>
                    </div>
                </div>

                <!-- Connector-specific configuration -->
                <div id="ryvr-connector-config" style="display: none;">
                    <div class="ryvr-form-row">
                        <label for="connector-action"><?php esc_html_e('Action', 'ryvr'); ?></label>
                        <select id="connector-action" class="regular-text">
                            <option value=""><?php esc_html_e('Select an action', 'ryvr'); ?></option>
                        </select>
                    </div>
                    <div id="action-parameters"></div>
                </div>

                <!-- Decision configuration -->
                <div id="ryvr-decision-config" style="display: none;">
                    <div class="ryvr-form-row">
                        <label for="decision-condition"><?php esc_html_e('Condition', 'ryvr'); ?></label>
                        <textarea id="decision-condition" class="regular-text" placeholder="<?php esc_attr_e('Enter condition logic', 'ryvr'); ?>"></textarea>
                        <p class="description"><?php esc_html_e('Use {{variable}} syntax to reference previous step outputs', 'ryvr'); ?></p>
                    </div>
                </div>

                <!-- Transformer configuration -->
                <div id="ryvr-transformer-config" style="display: none;">
                    <div class="ryvr-form-row">
                        <label for="transform-template"><?php esc_html_e('Transform Template', 'ryvr'); ?></label>
                        <textarea id="transform-template" class="regular-text" placeholder="<?php esc_attr_e('Enter transformation template', 'ryvr'); ?>"></textarea>
                        <p class="description"><?php esc_html_e('Use {{variable}} syntax to reference and transform data', 'ryvr'); ?></p>
                    </div>
                </div>

                <!-- Data mapping configuration -->
                <div id="ryvr-data-mapping" style="display: none;">
                    <h4><?php esc_html_e('Data Mapping', 'ryvr'); ?></h4>
                    <div id="mapping-fields"></div>
                    <button type="button" class="button ryvr-add-mapping"><?php esc_html_e('Add Mapping', 'ryvr'); ?></button>
                </div>
            </div>
        </div>
        
        <div class="ryvr-modal-footer">
            <button type="button" class="button button-secondary ryvr-cancel-step"><?php esc_html_e('Cancel', 'ryvr'); ?></button>
            <button type="button" class="button button-primary ryvr-save-step"><?php esc_html_e('Save Step', 'ryvr'); ?></button>
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

<style>
/* Visual Builder Styles - Scoped to workflow editor */
.ryvr-workflow-editor .ryvr-editor-tabs {
    display: flex;
    border-bottom: 1px solid #c3c4c7;
    margin-bottom: 20px;
}

.ryvr-workflow-editor .ryvr-tab-button {
    background: #f1f1f1;
    border: 1px solid #c3c4c7;
    border-bottom: none;
    padding: 10px 20px;
    cursor: pointer;
    margin-right: 5px;
    font-size: 14px;
    text-decoration: none;
}

.ryvr-workflow-editor .ryvr-tab-button.active {
    background: #fff;
    border-bottom: 1px solid #fff;
    margin-bottom: -1px;
}

.ryvr-workflow-editor .ryvr-tab-content {
    display: none;
}

.ryvr-workflow-editor .ryvr-tab-content.active {
    display: block;
}

.ryvr-workflow-canvas-container {
    position: relative;
    height: 600px;
    border: 1px solid #c3c4c7;
    background: #f9f9f9;
    overflow: hidden;
}

.ryvr-workflow-toolbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 10px;
    background: #fff;
    border-bottom: 1px solid #c3c4c7;
}

.ryvr-workflow-name-input {
    font-size: 16px;
    font-weight: bold;
    border: none;
    background: transparent;
    width: 300px;
}

.ryvr-workflow-description-input {
    font-size: 12px;
    border: none;
    background: transparent;
    width: 300px;
    height: 40px;
    resize: none;
}

.ryvr-workflow-controls {
    display: flex;
    gap: 5px;
}

.ryvr-workflow-canvas {
    position: relative;
    width: 100%;
    height: calc(100% - 60px);
    overflow: auto;
    background-image: 
        radial-gradient(circle, #ddd 1px, transparent 1px);
    background-size: 20px 20px;
}

.ryvr-workflow-steps {
    position: relative;
    width: 100%;
    height: 100%;
    min-width: 2000px;
    min-height: 1000px;
}

.ryvr-connections-svg {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    pointer-events: none;
    z-index: 1;
}

/* Component Palette */
.ryvr-component-category {
    margin-bottom: 20px;
}

.ryvr-component-category h4 {
    margin: 0 0 10px 0;
    padding: 5px 0;
    border-bottom: 1px solid #ddd;
    font-size: 12px;
    text-transform: uppercase;
    color: #666;
}

.ryvr-component-list {
    display: flex;
    flex-direction: column;
    gap: 5px;
}

.ryvr-component-item {
    display: flex;
    align-items: center;
    padding: 8px;
    border: 1px solid #ddd;
    border-radius: 4px;
    background: #fff;
    cursor: grab;
    transition: all 0.2s;
}

.ryvr-component-item:hover {
    background: #f0f0f0;
    border-color: #2271b1;
}

.ryvr-component-item:active {
    cursor: grabbing;
}

.ryvr-component-icon {
    width: 20px;
    height: 20px;
    margin-right: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
    border-radius: 3px;
}

.start-icon { background: #00a32a; color: white; }
.end-icon { background: #d63638; color: white; }
.decision-icon { background: #dba617; color: white; }
.connector-icon { background: #2271b1; color: white; }
.transformer-icon { background: #8c8f94; color: white; }
.filter-icon { background: #7c3aed; color: white; }
.mapper-icon { background: #059669; color: white; }

/* Workflow Steps */
.ryvr-workflow-step {
    position: absolute;
    width: 150px;
    min-height: 80px;
    background: #fff;
    border: 2px solid #c3c4c7;
    border-radius: 8px;
    padding: 10px;
    cursor: move;
    z-index: 2;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.ryvr-workflow-step.selected {
    border-color: #2271b1;
    box-shadow: 0 0 0 2px rgba(34, 113, 177, 0.3);
}

.ryvr-workflow-step.start { border-color: #00a32a; }
.ryvr-workflow-step.end { border-color: #d63638; }
.ryvr-workflow-step.decision { border-color: #dba617; }
.ryvr-workflow-step.connector { border-color: #2271b1; }
.ryvr-workflow-step.transformer { border-color: #8c8f94; }

.ryvr-step-header {
    display: flex;
    align-items: center;
    margin-bottom: 5px;
}

.ryvr-step-icon {
    width: 16px;
    height: 16px;
    margin-right: 5px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 10px;
    border-radius: 2px;
}

.ryvr-step-title {
    font-weight: bold;
    font-size: 12px;
    flex: 1;
}

.ryvr-step-actions {
    display: flex;
    gap: 2px;
}

.ryvr-step-action {
    background: none;
    border: none;
    cursor: pointer;
    padding: 2px;
    font-size: 10px;
    opacity: 0.7;
}

.ryvr-step-action:hover {
    opacity: 1;
}

.ryvr-step-content {
    font-size: 11px;
    color: #666;
    line-height: 1.3;
}

.ryvr-step-connector {
    position: absolute;
    width: 8px;
    height: 8px;
    background: #2271b1;
    border-radius: 50%;
    cursor: crosshair;
}

.ryvr-step-connector.input {
    top: -4px;
    left: 50%;
    transform: translateX(-50%);
}

.ryvr-step-connector.output {
    bottom: -4px;
    left: 50%;
    transform: translateX(-50%);
}

/* Step Configuration Modal */
.ryvr-step-modal .ryvr-modal-content {
    width: 600px;
    max-width: 90vw;
}

.ryvr-form-row {
    margin-bottom: 15px;
}

.ryvr-form-row label {
    display: block;
    margin-bottom: 5px;
    font-weight: 600;
}

.ryvr-form-row input,
.ryvr-form-row select,
.ryvr-form-row textarea {
    width: 100%;
}

.ryvr-parameter-field {
    margin-bottom: 10px;
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 4px;
    background: #f9f9f9;
}

.ryvr-mapping-row {
    display: flex;
    gap: 10px;
    align-items: center;
    margin-bottom: 10px;
}

.ryvr-mapping-row input {
    flex: 1;
}

.ryvr-remove-mapping {
    background: #d63638;
    color: white;
    border: none;
    padding: 5px 8px;
    border-radius: 3px;
    cursor: pointer;
}

/* Connection Lines */
.ryvr-connection-line {
    stroke: #2271b1;
    stroke-width: 2;
    fill: none;
    marker-end: url(#arrowhead);
}

.ryvr-connection-line.selected {
    stroke: #d63638;
    stroke-width: 3;
}

/* Drop Zone */
.ryvr-drop-zone {
    position: absolute;
    border: 2px dashed #2271b1;
    background: rgba(34, 113, 177, 0.1);
    border-radius: 8px;
    display: none;
}

.ryvr-drop-zone.active {
    display: block;
}

/* Dragging and Connection States */
.ryvr-workflow-step.dragging {
    opacity: 0.8;
    z-index: 1000;
    cursor: grabbing !important;
    transform: scale(1.05);
    box-shadow: 0 8px 16px rgba(0,0,0,0.3);
}

.dragging-step {
    cursor: grabbing !important;
}

.dragging-step * {
    cursor: grabbing !important;
}

.connecting-mode .ryvr-workflow-step {
    cursor: crosshair;
}

.connecting-mode .ryvr-step-connector.input:hover {
    background: #00a32a;
    transform: scale(1.5);
}

.ryvr-step-connector.connecting {
    background: #d63638;
    transform: scale(1.5);
    box-shadow: 0 0 10px rgba(214, 54, 56, 0.5);
}

.ryvr-step-connector:hover {
    transform: scale(1.2);
    transition: transform 0.2s;
}

.ryvr-connection-line {
    cursor: pointer;
    stroke-width: 3;
}

.ryvr-connection-line:hover {
    stroke: #d63638;
    stroke-width: 4;
}

/* Zoom effects */
#workflow-steps,
#connections-svg {
    transform-origin: 0 0;
    transition: transform 0.2s ease;
}

/* Connection mode styles */
.connection-mode {
    cursor: crosshair !important;
}

.ryvr-step-connector.highlight {
    background: #00a32a !important;
    transform: scale(1.3);
    box-shadow: 0 0 8px rgba(0, 163, 42, 0.6);
    animation: pulse 1s infinite;
}

@keyframes pulse {
    0% { opacity: 1; }
    50% { opacity: 0.7; }
    100% { opacity: 1; }
}
</style>

<script type="text/javascript">
jQuery(document).ready(function($) {
    var currentConnectorId = null;
    var currentWorkflowId = '<?php echo esc_js($workflow_id); ?>';
    var currentStep = null;
    var workflowData = {
        id: '',
        name: '',
        description: '',
        steps: [],
        connections: []
    };
    var stepCounter = 0;
    var isDragging = false;
    var isConnecting = false;
    var connectionStart = null;
    
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

    // Tab switching
    $('.ryvr-tab-button').on('click', function() {
        var tab = $(this).data('tab');
        
        $('.ryvr-tab-button').removeClass('active');
        $(this).addClass('active');
        
        $('.ryvr-tab-content').removeClass('active');
        $('#ryvr-' + tab + '-builder, #ryvr-' + tab + '-editor').addClass('active');
        
        // Show/hide sidebar panels
        if (tab === 'visual') {
            $('.ryvr-visual-only').show();
            $('.ryvr-json-only').hide();
        } else {
            $('.ryvr-visual-only').hide();
            $('.ryvr-json-only').show();
        }
        
        // Sync data between visual and JSON
        if (tab === 'json') {
            syncVisualToJson();
        } else {
            syncJsonToVisual();
        }
    });

    // Drag and drop for components
    $('.ryvr-component-item').on('dragstart', function(e) {
        var componentType = $(this).data('type');
        var connectorId = $(this).data('connector-id');
        
        e.originalEvent.dataTransfer.setData('text/plain', JSON.stringify({
            type: componentType,
            connectorId: connectorId
        }));
    });

    // Canvas drop handling
    $('#workflow-canvas').on('dragover', function(e) {
        e.preventDefault();
    });

    $('#workflow-canvas').on('drop', function(e) {
        e.preventDefault();
        
        var data = JSON.parse(e.originalEvent.dataTransfer.getData('text/plain'));
        var canvas = $(this);
        var canvasOffset = canvas.offset();
        
        // Calculate position relative to the canvas, accounting for scroll
        var x = e.clientX - canvasOffset.left + canvas.scrollLeft() - 75; // Center the step (150px width / 2)
        var y = e.clientY - canvasOffset.top + canvas.scrollTop() - 40;   // Center the step (80px height / 2)
        
        // Snap to grid (optional - makes positioning more predictable)
        x = Math.round(x / 20) * 20;
        y = Math.round(y / 20) * 20;
        
        // Ensure minimum bounds
        x = Math.max(20, x);
        y = Math.max(20, y);
        
        createStep(data.type, x, y, data.connectorId);
    });

    // Create a new step
    function createStep(type, x, y, connectorId) {
        stepCounter++;
        var stepId = type + '_' + stepCounter;
        
        var step = {
            id: stepId,
            type: type,
            name: getDefaultStepName(type, connectorId),
            x: x,
            y: y,
            connectorId: connectorId,
            config: {}
        };
        
        workflowData.steps.push(step);
        renderStep(step);
    }

    // Get default step name
    function getDefaultStepName(type, connectorId) {
        switch(type) {
            case 'start': return 'Start';
            case 'end': return 'End';
            case 'decision': return 'Decision';
            case 'transformer': return 'Transform Data';
            case 'filter': return 'Filter Data';
            case 'mapper': return 'Map Data';
            case 'connector': 
                return connectorId ? connectorId.charAt(0).toUpperCase() + connectorId.slice(1) : 'Connector';
            default: return 'Step';
        }
    }

    // Render a step on the canvas
    function renderStep(step) {
        var stepHtml = `
            <div class="ryvr-workflow-step ${step.type}" data-step-id="${step.id}" style="left: ${step.x}px; top: ${step.y}px;">
                <div class="ryvr-step-header">
                    <div class="ryvr-step-icon ${step.type}-icon">${getStepIcon(step.type)}</div>
                    <div class="ryvr-step-title">${step.name}</div>
                    <div class="ryvr-step-actions">
                        <button class="ryvr-step-action ryvr-edit-step" title="Edit">✏️</button>
                        <button class="ryvr-step-action ryvr-delete-step" title="Delete">🗑️</button>
                    </div>
                </div>
                <div class="ryvr-step-content">
                    ${getStepDescription(step)}
                </div>
                <div class="ryvr-step-connector input" title="Input"></div>
                <div class="ryvr-step-connector output" title="Output"></div>
            </div>
        `;
        
        $('#workflow-steps').append(stepHtml);
    }

    // Get step icon
    function getStepIcon(type) {
        switch(type) {
            case 'start': return '▶';
            case 'end': return '⏹';
            case 'decision': return '◆';
            case 'connector': return '🔗';
            case 'transformer': return '⚙';
            case 'filter': return '🔍';
            case 'mapper': return '📋';
            default: return '●';
        }
    }

    // Get step description
    function getStepDescription(step) {
        if (step.connectorId) {
            return step.connectorId + (step.config.action ? ': ' + step.config.action : '');
        }
        return step.type;
    }

    // Step selection
    $(document).on('click', '.ryvr-workflow-step', function(e) {
        if ($(e.target).closest('.ryvr-step-action').length > 0) {
            return; // Don't select when clicking action buttons
        }
        
        $('.ryvr-workflow-step').removeClass('selected');
        $(this).addClass('selected');
    });

    // Step editing
    $(document).on('click', '.ryvr-edit-step', function(e) {
        e.stopPropagation();
        var stepId = $(this).closest('.ryvr-workflow-step').data('step-id');
        var step = workflowData.steps.find(s => s.id === stepId);
        if (step) {
            openStepConfigModal(step);
        }
    });

    // Step deletion
    $(document).on('click', '.ryvr-delete-step', function(e) {
        e.stopPropagation();
        if (confirm('Are you sure you want to delete this step?')) {
            var stepId = $(this).closest('.ryvr-workflow-step').data('step-id');
            deleteStep(stepId);
        }
    });

    // Step movement with grid-based positioning
    var draggedStep = null;
    var dragOffset = { x: 0, y: 0 };
    var isDraggingStep = false;

    $(document).on('mousedown', '.ryvr-workflow-step', function(e) {
        if ($(e.target).closest('.ryvr-step-action, .ryvr-step-connector').length > 0) {
            return; // Don't drag when clicking action buttons or connectors
        }
        
        draggedStep = $(this);
        isDraggingStep = true;
        
        var stepPos = draggedStep.position();
        dragOffset.x = e.clientX - stepPos.left;
        dragOffset.y = e.clientY - stepPos.top;
        
        draggedStep.addClass('dragging');
        $('body').addClass('dragging-step');
        e.preventDefault();
    });

    $(document).on('mousemove', function(e) {
        if (draggedStep && isDraggingStep) {
            var canvas = $('#workflow-canvas');
            var canvasOffset = canvas.offset();
            
            // Calculate new position
            var x = e.clientX - canvasOffset.left - dragOffset.x + canvas.scrollLeft();
            var y = e.clientY - canvasOffset.top - dragOffset.y + canvas.scrollTop();
            
            // Snap to grid for better alignment
            x = Math.round(x / 20) * 20;
            y = Math.round(y / 20) * 20;
            
            // Constrain to canvas bounds with padding
            x = Math.max(20, Math.min(x, 1800));
            y = Math.max(20, Math.min(y, 800));
            
            draggedStep.css({
                left: x + 'px',
                top: y + 'px'
            });
            
            // Update step data
            var stepId = draggedStep.data('step-id');
            var step = workflowData.steps.find(s => s.id === stepId);
            if (step) {
                step.x = x;
                step.y = y;
            }
            
            // Redraw connections
            drawConnections();
        }
    });

    $(document).on('mouseup', function(e) {
        if (draggedStep && isDraggingStep) {
            draggedStep.removeClass('dragging');
            $('body').removeClass('dragging-step');
            draggedStep = null;
            isDraggingStep = false;
        }
    });

    // Connection functionality
    $(document).on('click', '.ryvr-step-connector.output', function(e) {
        e.stopPropagation();
        
        if (!isConnecting) {
            // Start connection
            isConnecting = true;
            connectionStart = $(this).closest('.ryvr-workflow-step').data('step-id');
            $(this).addClass('connecting');
            $('body').addClass('connecting-mode');
            updateConnectionMode();
        }
    });

    $(document).on('click', '.ryvr-step-connector.input', function(e) {
        e.stopPropagation();
        
        if (isConnecting && connectionStart) {
            var targetStepId = $(this).closest('.ryvr-workflow-step').data('step-id');
            
            if (targetStepId !== connectionStart) {
                // Create connection
                var connection = {
                    from: connectionStart,
                    to: targetStepId
                };
                
                // Check if connection already exists
                var exists = workflowData.connections.some(c => 
                    c.from === connection.from && c.to === connection.to
                );
                
                if (!exists) {
                    workflowData.connections.push(connection);
                    drawConnections();
                }
            }
            
            // End connection mode
            endConnectionMode();
        }
    });

    // Cancel connection on canvas click
    $(document).on('click', '#workflow-canvas', function(e) {
        if (isConnecting && $(e.target).is('#workflow-canvas, .ryvr-canvas-grid')) {
            endConnectionMode();
        }
    });

    function endConnectionMode() {
        isConnecting = false;
        connectionStart = null;
        $('.ryvr-step-connector').removeClass('connecting');
        $('body').removeClass('connecting-mode');
        updateConnectionMode();
    }

    // Delete connections
    $(document).on('click', '.ryvr-connection-line', function(e) {
        e.stopPropagation();
        
        if (confirm('Delete this connection?')) {
            var connectionId = $(this).data('connection');
            var parts = connectionId.split('-');
            
            workflowData.connections = workflowData.connections.filter(c => 
                !(c.from === parts[0] && c.to === parts[1])
            );
            
            drawConnections();
        }
    });

    // Zoom controls
    var zoomLevel = 1;
    
    $('.ryvr-zoom-in').on('click', function() {
        zoomLevel = Math.min(zoomLevel + 0.1, 2);
        applyZoom();
    });
    
    $('.ryvr-zoom-out').on('click', function() {
        zoomLevel = Math.max(zoomLevel - 0.1, 0.5);
        applyZoom();
    });
    
    $('.ryvr-zoom-fit').on('click', function() {
        zoomLevel = 1;
        applyZoom();
        
        // Center the canvas
        var canvas = $('#workflow-canvas');
        canvas.scrollLeft(0);
        canvas.scrollTop(0);
    });
    
    function applyZoom() {
        $('#workflow-steps').css('transform', 'scale(' + zoomLevel + ')');
        $('#connections-svg').css('transform', 'scale(' + zoomLevel + ')');
    }

    // Delete step
    function deleteStep(stepId) {
        // Remove from data
        workflowData.steps = workflowData.steps.filter(s => s.id !== stepId);
        workflowData.connections = workflowData.connections.filter(c => 
            c.from !== stepId && c.to !== stepId
        );
        
        // Remove from DOM
        $(`.ryvr-workflow-step[data-step-id="${stepId}"]`).remove();
        
        // Redraw connections
        drawConnections();
    }

    // Open step configuration modal
    function openStepConfigModal(step) {
        currentStep = step;
        
        $('#step-name').val(step.name);
        $('#step-description').val(step.description || '');
        
        // Hide all config sections
        $('#ryvr-connector-config, #ryvr-decision-config, #ryvr-transformer-config, #ryvr-data-mapping').hide();
        
        // Show relevant config section
        if (step.type === 'connector' && step.connectorId) {
            $('#ryvr-connector-config').show();
            loadConnectorActions(step.connectorId);
        } else if (step.type === 'decision') {
            $('#ryvr-decision-config').show();
            $('#decision-condition').val(step.config.condition || '');
        } else if (step.type === 'transformer') {
            $('#ryvr-transformer-config').show();
            $('#transform-template').val(step.config.template || '');
        }
        
        // Show data mapping for all types except start/end
        if (step.type !== 'start' && step.type !== 'end') {
            $('#ryvr-data-mapping').show();
            loadDataMapping(step);
        }
        
        $('#ryvr-step-config-modal').show();
    }

    // Load connector actions
    function loadConnectorActions(connectorId) {
        // This would typically make an AJAX call to get connector actions
        // For now, we'll use some sample data
        var actions = getConnectorActions(connectorId);
        
        var actionSelect = $('#connector-action');
        actionSelect.empty().append('<option value="">Select an action</option>');
        
        actions.forEach(function(action) {
            actionSelect.append(`<option value="${action.id}">${action.label}</option>`);
        });
        
        if (currentStep.config.action) {
            actionSelect.val(currentStep.config.action);
            loadActionParameters(connectorId, currentStep.config.action);
        }
    }

    // Get connector actions (sample data)
    function getConnectorActions(connectorId) {
        switch(connectorId) {
            case 'openai':
                return [
                    { id: 'generate_text', label: 'Generate Text' },
                    { id: 'generate_image', label: 'Generate Image' },
                    { id: 'chat_completion', label: 'Chat Completion' }
                ];
            case 'dataforseo':
                return [
                    { id: 'keyword_research', label: 'Keyword Research' },
                    { id: 'serp_analysis', label: 'SERP Analysis' },
                    { id: 'backlink_analysis', label: 'Backlink Analysis' }
                ];
            default:
                return [
                    { id: 'test_action', label: 'Test Action' }
                ];
        }
    }

    // Load action parameters
    $('#connector-action').on('change', function() {
        var action = $(this).val();
        if (action && currentStep.connectorId) {
            loadActionParameters(currentStep.connectorId, action);
        }
    });

    function loadActionParameters(connectorId, actionId) {
        var parameters = getActionParameters(connectorId, actionId);
        var container = $('#action-parameters');
        container.empty();
        
        parameters.forEach(function(param) {
            var fieldHtml = `
                <div class="ryvr-parameter-field">
                    <label>${param.label}</label>
                    ${createParameterField(param)}
                    ${param.description ? `<p class="description">${param.description}</p>` : ''}
                </div>
            `;
            container.append(fieldHtml);
        });
    }

    // Create parameter field
    function createParameterField(param) {
        var value = currentStep.config.parameters && currentStep.config.parameters[param.id] || param.default || '';
        
        switch(param.type) {
            case 'select':
                var options = param.options.map(opt => 
                    `<option value="${opt.value}" ${opt.value === value ? 'selected' : ''}>${opt.label}</option>`
                ).join('');
                return `<select name="param_${param.id}">${options}</select>`;
            case 'textarea':
                return `<textarea name="param_${param.id}" rows="3">${value}</textarea>`;
            case 'number':
                return `<input type="number" name="param_${param.id}" value="${value}">`;
            default:
                return `<input type="text" name="param_${param.id}" value="${value}">`;
        }
    }

    // Get action parameters (sample data)
    function getActionParameters(connectorId, actionId) {
        if (connectorId === 'openai') {
            if (actionId === 'generate_text') {
                return [
                    {
                        id: 'model',
                        label: 'Model',
                        type: 'select',
                        options: [
                            { value: 'gpt-3.5-turbo', label: 'GPT-3.5 Turbo' },
                            { value: 'gpt-4', label: 'GPT-4' },
                            { value: 'gpt-4-turbo', label: 'GPT-4 Turbo' }
                        ],
                        default: 'gpt-3.5-turbo'
                    },
                    {
                        id: 'prompt',
                        label: 'Prompt',
                        type: 'textarea',
                        description: 'The prompt to generate text from'
                    },
                    {
                        id: 'max_tokens',
                        label: 'Max Tokens',
                        type: 'number',
                        default: 1024
                    },
                    {
                        id: 'temperature',
                        label: 'Temperature',
                        type: 'number',
                        default: 0.7,
                        description: 'Controls randomness (0-2)'
                    }
                ];
            } else if (actionId === 'generate_image') {
                return [
                    {
                        id: 'prompt',
                        label: 'Image Prompt',
                        type: 'textarea',
                        description: 'Describe the image you want to generate'
                    },
                    {
                        id: 'size',
                        label: 'Image Size',
                        type: 'select',
                        options: [
                            { value: '256x256', label: '256x256' },
                            { value: '512x512', label: '512x512' },
                            { value: '1024x1024', label: '1024x1024' }
                        ],
                        default: '512x512'
                    }
                ];
            }
        } else if (connectorId === 'dataforseo') {
            if (actionId === 'keyword_research') {
                return [
                    {
                        id: 'keyword',
                        label: 'Keyword',
                        type: 'text',
                        description: 'The keyword to research'
                    },
                    {
                        id: 'location_code',
                        label: 'Location Code',
                        type: 'text',
                        default: '2840',
                        description: 'Location code for the search'
                    },
                    {
                        id: 'language_code',
                        label: 'Language Code',
                        type: 'text',
                        default: 'en',
                        description: 'Language code for the search'
                    }
                ];
            }
        }
        return [];
    }

    // Load data mapping
    function loadDataMapping(step) {
        var container = $('#mapping-fields');
        container.empty();
        
        if (step.config.mapping) {
            step.config.mapping.forEach(function(mapping, index) {
                addMappingRow(mapping.from, mapping.to, index);
            });
        }
    }

    // Add mapping row
    $('.ryvr-add-mapping').on('click', function() {
        addMappingRow('', '', Date.now());
    });

    function addMappingRow(from, to, index) {
        var rowHtml = `
            <div class="ryvr-mapping-row" data-index="${index}">
                <input type="text" placeholder="Source field" value="${from}" class="mapping-from">
                <span>→</span>
                <input type="text" placeholder="Target field" value="${to}" class="mapping-to">
                <button type="button" class="ryvr-remove-mapping">×</button>
            </div>
        `;
        $('#mapping-fields').append(rowHtml);
    }

    // Remove mapping row
    $(document).on('click', '.ryvr-remove-mapping', function() {
        $(this).closest('.ryvr-mapping-row').remove();
    });

    // Save step configuration
    $('.ryvr-save-step').on('click', function() {
        if (!currentStep) return;
        
        // Update basic info
        currentStep.name = $('#step-name').val();
        currentStep.description = $('#step-description').val();
        
        // Update type-specific config
        if (currentStep.type === 'connector') {
            currentStep.config.action = $('#connector-action').val();
            
            // Save parameters
            currentStep.config.parameters = {};
            $('#action-parameters input, #action-parameters select, #action-parameters textarea').each(function() {
                var name = $(this).attr('name');
                if (name && name.startsWith('param_')) {
                    var paramId = name.substring(6);
                    currentStep.config.parameters[paramId] = $(this).val();
                }
            });
        } else if (currentStep.type === 'decision') {
            currentStep.config.condition = $('#decision-condition').val();
        } else if (currentStep.type === 'transformer') {
            currentStep.config.template = $('#transform-template').val();
        }
        
        // Save data mapping
        var mapping = [];
        $('.ryvr-mapping-row').each(function() {
            var from = $(this).find('.mapping-from').val();
            var to = $(this).find('.mapping-to').val();
            if (from && to) {
                mapping.push({ from: from, to: to });
            }
        });
        currentStep.config.mapping = mapping;
        
        // Update step display
        var stepElement = $(`.ryvr-workflow-step[data-step-id="${currentStep.id}"]`);
        stepElement.find('.ryvr-step-title').text(currentStep.name);
        stepElement.find('.ryvr-step-content').text(getStepDescription(currentStep));
        
        $('#ryvr-step-config-modal').hide();
        currentStep = null;
    });

    // Cancel step configuration
    $('.ryvr-cancel-step').on('click', function() {
        $('#ryvr-step-config-modal').hide();
        currentStep = null;
    });

    // Modal close
    $('.ryvr-modal-close').on('click', function() {
        $(this).closest('.ryvr-modal').hide();
        currentStep = null;
    });

    // Sync visual to JSON
    function syncVisualToJson() {
        workflowData.name = $('#workflow-name').val();
        workflowData.description = $('#workflow-description').val();
        
        var jsonData = {
            id: workflowData.id || currentWorkflowId || 'workflow_' + Date.now(),
            name: workflowData.name,
            description: workflowData.description,
            steps: workflowData.steps.map(function(step) {
                var stepData = {
                    id: step.id,
                    type: step.type,
                    name: step.name
                };
                
                if (step.description) {
                    stepData.description = step.description;
                }
                
                if (step.connectorId) {
                    stepData.connector = step.connectorId;
                }
                
                if (step.config && Object.keys(step.config).length > 0) {
                    // Map config based on step type
                    if (step.type === 'connector' || step.type === 'action') {
                        if (step.config.action) {
                            stepData.action = step.config.action;
                        }
                        if (step.config.parameters) {
                            stepData.params = step.config.parameters;
                        }
                    } else if (step.type === 'decision') {
                        if (step.config.condition) {
                            stepData.condition = step.config.condition;
                        }
                    } else if (step.type === 'transformer') {
                        if (step.config.template) {
                            stepData.template = step.config.template;
                        }
                    }
                    
                    if (step.config.mapping && step.config.mapping.length > 0) {
                        stepData.mapping = step.config.mapping;
                    }
                }
                
                return stepData;
            }),
            connections: workflowData.connections
        };
        
        if (editor && editor.codemirror) {
            editor.codemirror.setValue(JSON.stringify(jsonData, null, 2));
        } else {
            $('#ryvr-workflow-json').val(JSON.stringify(jsonData, null, 2));
        }
    }
    
    // Auto-sync when workflow name/description changes
    $('#workflow-name, #workflow-description').on('input', function() {
        workflowData.name = $('#workflow-name').val();
        workflowData.description = $('#workflow-description').val();
    });
    
    // Initialize field mapping modal
    $('.ryvr-workflow-editor').append('<?php 
        $field_mapping_modal = new \Ryvr\Admin\FieldMappingModal();
        echo str_replace(array("\r", "\n"), '', addslashes($field_mapping_modal->render_modal()));
    ?>');

    // Keyboard shortcuts
    $(document).on('keydown', function(e) {
        // Delete selected step with Delete key
        if (e.key === 'Delete' && $('.ryvr-workflow-step.selected').length > 0) {
            var stepId = $('.ryvr-workflow-step.selected').data('step-id');
            if (confirm('Are you sure you want to delete this step?')) {
                deleteStep(stepId);
            }
        }
        
        // Save workflow with Ctrl+S
        if (e.ctrlKey && e.key === 's') {
            e.preventDefault();
            saveWorkflow();
        }
        
        // Escape to cancel connection mode
        if (e.key === 'Escape' && isConnecting) {
            endConnectionMode();
        }
    });

    // Sync JSON to visual
    function syncJsonToVisual() {
        try {
            var jsonText = editor && editor.codemirror ? 
                editor.codemirror.getValue() : 
                $('#ryvr-workflow-json').val();
            
            var jsonData = JSON.parse(jsonText);
            
            workflowData = {
                id: jsonData.id || '',
                name: jsonData.name || '',
                description: jsonData.description || '',
                steps: jsonData.steps || [],
                connections: jsonData.connections || []
            };
            
            $('#workflow-name').val(workflowData.name);
            $('#workflow-description').val(workflowData.description);
            
            // Clear and re-render steps
            $('#workflow-steps').empty();
            workflowData.steps.forEach(function(step) {
                renderStep(step);
            });
            
            drawConnections();
        } catch (e) {
            console.error('Invalid JSON:', e);
        }
    }

    // Draw connections
    function drawConnections() {
        var svg = $('#connections-svg');
        svg.empty();
        
        // Add arrow marker
        svg.append(`
            <defs>
                <marker id="arrowhead" markerWidth="10" markerHeight="7" 
                        refX="9" refY="3.5" orient="auto">
                    <polygon points="0 0, 10 3.5, 0 7" fill="#2271b1" />
                </marker>
            </defs>
        `);
        
        workflowData.connections.forEach(function(connection) {
            drawConnection(connection);
        });
    }

    // Draw a single connection
    function drawConnection(connection) {
        var fromStep = $(`.ryvr-workflow-step[data-step-id="${connection.from}"]`);
        var toStep = $(`.ryvr-workflow-step[data-step-id="${connection.to}"]`);
        
        if (fromStep.length && toStep.length) {
            var fromPos = fromStep.position();
            var toPos = toStep.position();
            
            var x1 = fromPos.left + fromStep.width() / 2;
            var y1 = fromPos.top + fromStep.height();
            var x2 = toPos.left + toStep.width() / 2;
            var y2 = toPos.top;
            
            // Create a curved path for better visual appeal
            var midY = y1 + (y2 - y1) / 2;
            var path = `M ${x1} ${y1} C ${x1} ${midY} ${x2} ${midY} ${x2} ${y2}`;
            
            $('#connections-svg').append(`
                <path d="${path}" class="ryvr-connection-line" data-connection="${connection.from}-${connection.to}"/>
            `);
        }
    }

    // Add visual feedback for connection mode
    function updateConnectionMode() {
        if (isConnecting) {
            $('#workflow-canvas').addClass('connection-mode');
            $('.ryvr-step-connector.input').addClass('highlight');
        } else {
            $('#workflow-canvas').removeClass('connection-mode');
            $('.ryvr-step-connector').removeClass('highlight');
        }
    }

    // Initialize workflow data from existing workflow if editing
    if (currentWorkflowId && '<?php echo esc_js($workflow_data); ?>' !== '{}') {
        try {
            var existingData = JSON.parse('<?php echo addslashes($workflow_data); ?>');
            if (existingData.steps) {
                workflowData = {
                    id: existingData.id || currentWorkflowId,
                    name: existingData.name || '',
                    description: existingData.description || '',
                    steps: existingData.steps.map(function(step, index) {
                        return {
                            id: step.id,
                            type: step.type,
                            name: step.name || getDefaultStepName(step.type, step.connector),
                            description: step.description || '',
                            x: (index * 200) + 100, // Position steps horizontally
                            y: 150,
                            connectorId: step.connector,
                            config: step.config || {}
                        };
                    }),
                    connections: existingData.connections || []
                };
                
                $('#workflow-name').val(workflowData.name);
                $('#workflow-description').val(workflowData.description);
                
                // Render existing steps
                workflowData.steps.forEach(function(step) {
                    renderStep(step);
                });
                
                stepCounter = workflowData.steps.length;
            }
        } catch (e) {
            console.error('Error parsing existing workflow:', e);
        }
    }
    
    // Initialize with a start step if empty
    if (workflowData.steps.length === 0) {
        createStep('start', 100, 100);
    }
    
    // View connector actions
    $('.ryvr-view-connector-actions').on('click', function() {
        var connectorId = $(this).data('connector-id');
        currentConnectorId = connectorId;
        
        // Get connector name
        var connectorName = $(this).closest('.ryvr-connector-item').find('.ryvr-connector-name').text();
        $('#ryvr-connector-actions-title').text(connectorName + ' Actions');
        
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
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'ryvr_connector_get_actions',
                connector_id: connectorId,
                nonce: '<?php echo wp_create_nonce("ryvr-admin-nonce"); ?>'
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
                            actionsHtml += '<h5>Parameters</h5>';
                            actionsHtml += '<table class="widefat" style="width: 100%;">';
                            actionsHtml += '<thead><tr><th>Name</th><th>Type</th><th>Description</th></tr></thead>';
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
                        actionsHtml += '<p><button type="button" class="button ryvr-insert-action" data-connector="' + connectorId + '" data-action="' + id + '">Insert into Workflow</button></p>';
                        
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
                    $('#ryvr-actions-content').html('<p>No actions found for this connector.</p>');
                }
            },
            error: function() {
                $('.ryvr-loading').hide();
                $('#ryvr-actions-content').html('<p>Error loading actions. Please try again.</p>');
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
            alert('Invalid workflow JSON. Please check the format.');
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
            alert('Invalid JSON format. Please check your input data.');
            return;
        }
        
        // Show loading
        $('.ryvr-loading').show();
        $('#ryvr-run-results').hide();
        
        // Make AJAX request
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'ryvr_workflow_execute',
                id: currentWorkflowId,
                input: inputData,
                nonce: '<?php echo wp_create_nonce("ryvr_workflow_execute"); ?>'
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
                alert('Error running workflow. Please try again.');
            }
        });
    }
    
    // Function to save the workflow
    function saveWorkflow() {
        // Sync visual to JSON first if on visual tab
        if ($('.ryvr-tab-button.active').data('tab') === 'visual') {
            syncVisualToJson();
        }
        
        var workflowJson = getEditorValue();
        
        try {
            // Validate JSON
            JSON.parse(workflowJson);
        } catch (e) {
            alert('Invalid JSON format. Please check your workflow definition.');
            return;
        }
        
        // Show loading indicator
        $('.ryvr-save-workflow').prop('disabled', true).text('Saving...');
        
        // Make AJAX request
        $.ajax({
            url: '<?php echo admin_url("admin-ajax.php"); ?>',
            type: 'POST',
            data: {
                action: 'ryvr_workflow_save',
                definition: workflowJson,
                nonce: '<?php echo wp_create_nonce("ryvr_workflow_save"); ?>'
            },
            success: function(response) {
                $('.ryvr-save-workflow').prop('disabled', false).text('Save Workflow');
                
                if (response.success) {
                    // Show success message
                    alert('Workflow saved successfully!');
                    
                    // Update currentWorkflowId if this is a new workflow
                    if (!currentWorkflowId && response.data.id) {
                        currentWorkflowId = response.data.id;
                        workflowData.id = currentWorkflowId;
                        
                        // Update URL to edit mode
                        var newUrl = window.location.href;
                        if (newUrl.indexOf('?') !== -1) {
                            newUrl = newUrl.substring(0, newUrl.indexOf('?'));
                        }
                        newUrl += '?page=ryvr-add-workflow&id=' + currentWorkflowId;
                        
                        window.history.replaceState({}, document.title, newUrl);
                        
                        // Show run button
                        if ($('.ryvr-run-workflow').length === 0) {
                            $('.ryvr-validate-workflow').after(
                                '<button type="button" class="button ryvr-run-workflow" data-workflow-id="' + currentWorkflowId + '">Run</button>'
                            );
                        }
                    }
                } else {
                    alert(response.data ? response.data.message : 'Error saving workflow');
                }
            },
            error: function() {
                $('.ryvr-save-workflow').prop('disabled', false).text('Save Workflow');
                alert('Error saving workflow. Please try again.');
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
                errors.push('Workflow ID is required');
            }
            
            if (!workflow.name) {
                errors.push('Workflow name is required');
            }
            
            if (!workflow.steps || !Array.isArray(workflow.steps) || workflow.steps.length === 0) {
                errors.push('Workflow must have at least one step');
            } else {
                // Validate steps
                workflow.steps.forEach(function(step, index) {
                    if (!step.id) {
                        errors.push('Step ' + (index + 1) + ' is missing an ID');
                    }
                    
                    if (!step.type) {
                        errors.push('Step ' + (index + 1) + ' is missing a type');
                    } else if (step.type === 'action') {
                        if (!step.connector) {
                            errors.push('Step ' + (index + 1) + ' is missing a connector');
                        }
                        
                        if (!step.action) {
                            errors.push('Step ' + (index + 1) + ' is missing an action');
                        }
                    } else if (step.type === 'decision' && !step.condition) {
                        errors.push('Step ' + (index + 1) + ' is missing a condition');
                    } else if (step.type === 'transformer' && !step.template) {
                        errors.push('Step ' + (index + 1) + ' is missing a template');
                    }
                });
            }
            
            if (errors.length > 0) {
                alert('Validation Errors:\n\n' + errors.join('\n'));
            } else {
                alert('Workflow is valid!');
            }
        } catch (e) {
            alert('Invalid JSON format. Please check your workflow definition.');
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
    margin-top: 20px;
}

.ryvr-workflow-editor-header {
    display: flex;
    justify-content: flex-end;
    margin-bottom: 20px;
}

.ryvr-workflow-editor-body {
    display: flex;
    gap: 20px;
    flex-direction: column;
}

.ryvr-workflow-editor-main {
    flex: 1;
    min-width: 0; /* Allow flex item to shrink */
}

.ryvr-workflow-editor-sidebar {
    width: 100%;
    flex-shrink: 0;
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