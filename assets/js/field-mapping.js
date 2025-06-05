/**
 * Field Mapping JavaScript for Ryvr Workflow Builder
 */

(function($) {
    'use strict';

    /**
     * Field Mapping Manager
     */
    class FieldMappingManager {
        constructor() {
            this.currentConnection = null;
            this.sourceFields = {};
            this.targetFields = {};
            this.mappings = [];
            this.transformations = [];
            this.validations = [];
            
            this.initEventListeners();
        }
        
        /**
         * Initialize event listeners
         */
        initEventListeners() {
            // Open mapping modal for connection
            $(document).on('click', '.ryvr-connection-configure', this.openMappingModal.bind(this));
            $(document).on('dblclick', '.ryvr-connection-line', this.openMappingModal.bind(this));
            
            // Modal controls
            $(document).on('click', '.ryvr-modal-close, .ryvr-cancel-mapping', this.closeMappingModal.bind(this));
            $(document).on('click', '.ryvr-save-mapping', this.saveMappingConfiguration.bind(this));
            
            // Field mapping
            $(document).on('click', '.ryvr-add-field-mapping', this.addFieldMapping.bind(this));
            $(document).on('click', '.ryvr-remove-mapping', this.removeFieldMapping.bind(this));
            
            // Data transformation
            $(document).on('click', '.ryvr-add-transformation', this.addTransformation.bind(this));
            $(document).on('click', '.ryvr-remove-transformation', this.removeTransformation.bind(this));
            
            // Data validation
            $(document).on('click', '.ryvr-add-validation', this.addValidation.bind(this));
            $(document).on('click', '.ryvr-remove-validation', this.removeValidation.bind(this));
            
            // Preview and testing
            $(document).on('click', '.ryvr-test-mapping', this.testMapping.bind(this));
        }
        
        /**
         * Open mapping modal for a connection
         */
        openMappingModal(event) {
            const connectionElement = $(event.currentTarget).closest('.ryvr-connection, .ryvr-connection-line');
            const connectionId = connectionElement.data('connection-id') || 
                                connectionElement.attr('data-connection-id');
            
            if (!connectionId) {
                console.error('No connection ID found');
                return;
            }
            
            this.currentConnection = this.findConnectionById(connectionId);
            if (!this.currentConnection) {
                console.error('Connection not found:', connectionId);
                return;
            }
            
            this.loadConnectionData();
            this.renderMappingModal();
            $('#ryvr-field-mapping-modal').show();
        }
        
        /**
         * Close mapping modal
         */
        closeMappingModal() {
            $('#ryvr-field-mapping-modal').hide();
            this.currentConnection = null;
            this.clearModalData();
        }
        
        /**
         * Find connection by ID
         */
        findConnectionById(connectionId) {
            if (typeof workflowData !== 'undefined' && workflowData.connections) {
                return workflowData.connections.find(conn => conn.id === connectionId);
            }
            return null;
        }
        
        /**
         * Load connection data and field information
         */
        loadConnectionData() {
            if (!this.currentConnection) return;
            
            const sourceStep = this.findStepById(this.currentConnection.source);
            const targetStep = this.findStepById(this.currentConnection.target);
            
            if (sourceStep && targetStep) {
                this.loadSourceFields(sourceStep);
                this.loadTargetFields(targetStep);
                this.loadExistingMappings();
            }
        }
        
        /**
         * Find step by ID
         */
        findStepById(stepId) {
            if (typeof workflowData !== 'undefined' && workflowData.steps) {
                return workflowData.steps.find(step => step.id === stepId);
            }
            return null;
        }
        
        /**
         * Load available source fields from step
         */
        loadSourceFields(sourceStep) {
            this.sourceFields = {};
            
            // Get fields based on step type
            if (sourceStep.type === 'connector' && sourceStep.connectorId) {
                this.loadConnectorOutputFields(sourceStep.connectorId, sourceStep.config.action);
            } else if (sourceStep.type === 'transformer') {
                this.sourceFields = { 'result': 'string' };
            } else if (sourceStep.type === 'decision') {
                this.sourceFields = { 'result': 'boolean', 'condition': 'string' };
            }
            
            this.renderSourceFields();
        }
        
        /**
         * Load required target fields for step
         */
        loadTargetFields(targetStep) {
            this.targetFields = {};
            
            // Get fields based on step type
            if (targetStep.type === 'connector' && targetStep.connectorId) {
                this.loadConnectorInputFields(targetStep.connectorId, targetStep.config.action);
            } else if (targetStep.type === 'transformer') {
                this.targetFields = { 'input': 'any' };
            } else if (targetStep.type === 'decision') {
                this.targetFields = { 'condition': 'string' };
            }
            
            this.renderTargetFields();
        }
        
        /**
         * Load connector output fields
         */
        loadConnectorOutputFields(connectorId, actionId) {
            // This would ideally make an AJAX call to get field schema
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'ryvr_get_connector_output_schema',
                    connector_id: connectorId,
                    action_id: actionId,
                    nonce: ryvrData.nonce
                },
                success: (response) => {
                    if (response.success && response.data) {
                        this.sourceFields = response.data;
                        this.renderSourceFields();
                    }
                },
                error: () => {
                    // Fallback to generic fields
                    this.sourceFields = {
                        'data': 'object',
                        'status': 'string',
                        'message': 'string'
                    };
                    this.renderSourceFields();
                }
            });
        }
        
        /**
         * Load connector input fields
         */
        loadConnectorInputFields(connectorId, actionId) {
            // This would ideally make an AJAX call to get field schema
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'ryvr_get_connector_input_schema',
                    connector_id: connectorId,
                    action_id: actionId,
                    nonce: ryvrData.nonce
                },
                success: (response) => {
                    if (response.success && response.data) {
                        this.targetFields = response.data;
                        this.renderTargetFields();
                    }
                },
                error: () => {
                    // Fallback to generic fields
                    this.targetFields = {
                        'param1': 'string',
                        'param2': 'string'
                    };
                    this.renderTargetFields();
                }
            });
        }
        
        /**
         * Render source fields list
         */
        renderSourceFields() {
            const container = $('#source-fields-list');
            container.empty();
            
            Object.keys(this.sourceFields).forEach(field => {
                const fieldType = this.sourceFields[field];
                const fieldElement = $(`
                    <div class="ryvr-field-item" data-field="${field}" data-type="${fieldType}">
                        <span class="field-name">${field}</span>
                        <span class="field-type">${fieldType}</span>
                        <button type="button" class="button-link ryvr-use-field" data-target="source">
                            <span class="dashicons dashicons-plus"></span>
                        </button>
                    </div>
                `);
                container.append(fieldElement);
            });
        }
        
        /**
         * Render target fields list
         */
        renderTargetFields() {
            const container = $('#target-fields-list');
            container.empty();
            
            Object.keys(this.targetFields).forEach(field => {
                const fieldType = this.targetFields[field];
                const required = this.isFieldRequired(field);
                const fieldElement = $(`
                    <div class="ryvr-field-item" data-field="${field}" data-type="${fieldType}" ${required ? 'data-required="true"' : ''}>
                        <span class="field-name">${field}</span>
                        <span class="field-type">${fieldType}</span>
                        ${required ? '<span class="field-required">*</span>' : ''}
                        <button type="button" class="button-link ryvr-use-field" data-target="target">
                            <span class="dashicons dashicons-plus"></span>
                        </button>
                    </div>
                `);
                container.append(fieldElement);
            });
        }
        
        /**
         * Check if a field is required
         */
        isFieldRequired(field) {
            // This could be enhanced to check actual connector schemas
            return field.includes('required') || field === 'id' || field === 'name';
        }
        
        /**
         * Load existing mappings from connection
         */
        loadExistingMappings() {
            this.mappings = this.currentConnection.mapping || [];
            this.transformations = this.currentConnection.transformations || [];
            this.validations = this.currentConnection.validations || [];
            
            this.renderAllMappings();
            this.renderAllTransformations();
            this.renderAllValidations();
        }
        
        /**
         * Add new field mapping
         */
        addFieldMapping() {
            const newMapping = {
                source: '',
                target: '',
                required: false,
                defaultValue: ''
            };
            
            this.mappings.push(newMapping);
            this.renderFieldMapping(newMapping, this.mappings.length - 1);
        }
        
        /**
         * Remove field mapping
         */
        removeFieldMapping(event) {
            const row = $(event.currentTarget).closest('.ryvr-mapping-row');
            const index = parseInt(row.data('index'));
            
            this.mappings.splice(index, 1);
            row.remove();
            this.updateMappingIndices();
        }
        
        /**
         * Render single field mapping
         */
        renderFieldMapping(mapping, index) {
            const template = $(`
                <div class="ryvr-mapping-row" data-index="${index}">
                    <div class="ryvr-mapping-fields">
                        <div class="ryvr-source-field">
                            <label>Source Field</label>
                            <input type="text" class="mapping-source-field" 
                                   placeholder="e.g., response.data.id" 
                                   value="${mapping.source || ''}">
                        </div>
                        
                        <div class="ryvr-mapping-operator">
                            <span class="dashicons dashicons-arrow-right-alt2"></span>
                        </div>
                        
                        <div class="ryvr-target-field">
                            <label>Target Field</label>
                            <input type="text" class="mapping-target-field" 
                                   placeholder="e.g., user_id" 
                                   value="${mapping.target || ''}">
                        </div>
                        
                        <div class="ryvr-mapping-actions">
                            <button type="button" class="button-link ryvr-remove-mapping">
                                <span class="dashicons dashicons-trash"></span>
                            </button>
                        </div>
                    </div>
                    
                    <div class="ryvr-mapping-options">
                        <label>
                            <input type="checkbox" class="mapping-required" ${mapping.required ? 'checked' : ''}>
                            Required
                        </label>
                        
                        <div class="ryvr-default-value">
                            <label>Default Value</label>
                            <input type="text" class="mapping-default" 
                                   placeholder="Default value if source is empty" 
                                   value="${mapping.defaultValue || ''}">
                        </div>
                    </div>
                </div>
            `);
            
            $('#ryvr-field-mappings').append(template);
        }
        
        /**
         * Render all field mappings
         */
        renderAllMappings() {
            $('#ryvr-field-mappings').empty();
            this.mappings.forEach((mapping, index) => {
                this.renderFieldMapping(mapping, index);
            });
        }
        
        /**
         * Add transformation
         */
        addTransformation() {
            const newTransformation = {
                field: '',
                function: '',
                params: {}
            };
            
            this.transformations.push(newTransformation);
            this.renderTransformation(newTransformation, this.transformations.length - 1);
        }
        
        /**
         * Render transformation
         */
        renderTransformation(transformation, index) {
            const template = $(`
                <div class="ryvr-transformation-row" data-index="${index}">
                    <div class="ryvr-transformation-field">
                        <label>Field</label>
                        <input type="text" class="transformation-field" 
                               placeholder="Field to transform" 
                               value="${transformation.field || ''}">
                    </div>
                    
                    <div class="ryvr-transformation-function">
                        <label>Function</label>
                        <select class="transformation-function">
                            <option value="">Select function</option>
                            <option value="uppercase" ${transformation.function === 'uppercase' ? 'selected' : ''}>Uppercase</option>
                            <option value="lowercase" ${transformation.function === 'lowercase' ? 'selected' : ''}>Lowercase</option>
                            <option value="trim" ${transformation.function === 'trim' ? 'selected' : ''}>Trim whitespace</option>
                            <option value="format_date" ${transformation.function === 'format_date' ? 'selected' : ''}>Format date</option>
                            <option value="replace" ${transformation.function === 'replace' ? 'selected' : ''}>Replace text</option>
                            <option value="number_format" ${transformation.function === 'number_format' ? 'selected' : ''}>Format number</option>
                        </select>
                    </div>
                    
                    <div class="ryvr-transformation-params">
                        <label>Parameters</label>
                        <input type="text" class="transformation-params" 
                               placeholder='{"param": "value"}' 
                               value='${JSON.stringify(transformation.params || {})}'>
                    </div>
                    
                    <div class="ryvr-transformation-actions">
                        <button type="button" class="button-link ryvr-remove-transformation">
                            <span class="dashicons dashicons-trash"></span>
                        </button>
                    </div>
                </div>
            `);
            
            $('#ryvr-transformations').append(template);
        }
        
        /**
         * Render all transformations
         */
        renderAllTransformations() {
            $('#ryvr-transformations').empty();
            this.transformations.forEach((transformation, index) => {
                this.renderTransformation(transformation, index);
            });
        }
        
        /**
         * Add validation
         */
        addValidation() {
            const newValidation = {
                field: '',
                rule: '',
                params: {},
                message: ''
            };
            
            this.validations.push(newValidation);
            this.renderValidation(newValidation, this.validations.length - 1);
        }
        
        /**
         * Render validation
         */
        renderValidation(validation, index) {
            const template = $(`
                <div class="ryvr-validation-row" data-index="${index}">
                    <div class="ryvr-validation-field">
                        <label>Field</label>
                        <input type="text" class="validation-field" 
                               placeholder="Field to validate" 
                               value="${validation.field || ''}">
                    </div>
                    
                    <div class="ryvr-validation-rule">
                        <label>Rule</label>
                        <select class="validation-rule">
                            <option value="">Select rule</option>
                            <option value="required" ${validation.rule === 'required' ? 'selected' : ''}>Required</option>
                            <option value="string" ${validation.rule === 'string' ? 'selected' : ''}>String</option>
                            <option value="integer" ${validation.rule === 'integer' ? 'selected' : ''}>Integer</option>
                            <option value="number" ${validation.rule === 'number' ? 'selected' : ''}>Number</option>
                            <option value="email" ${validation.rule === 'email' ? 'selected' : ''}>Email</option>
                            <option value="url" ${validation.rule === 'url' ? 'selected' : ''}>URL</option>
                            <option value="min_length" ${validation.rule === 'min_length' ? 'selected' : ''}>Minimum length</option>
                            <option value="max_length" ${validation.rule === 'max_length' ? 'selected' : ''}>Maximum length</option>
                        </select>
                    </div>
                    
                    <div class="ryvr-validation-params">
                        <label>Parameters</label>
                        <input type="text" class="validation-params" 
                               placeholder='{"length": 5}' 
                               value='${JSON.stringify(validation.params || {})}'>
                    </div>
                    
                    <div class="ryvr-validation-message">
                        <label>Custom Message</label>
                        <input type="text" class="validation-message" 
                               placeholder="Custom error message" 
                               value="${validation.message || ''}">
                    </div>
                    
                    <div class="ryvr-validation-actions">
                        <button type="button" class="button-link ryvr-remove-validation">
                            <span class="dashicons dashicons-trash"></span>
                        </button>
                    </div>
                </div>
            `);
            
            $('#ryvr-validations').append(template);
        }
        
        /**
         * Render all validations
         */
        renderAllValidations() {
            $('#ryvr-validations').empty();
            this.validations.forEach((validation, index) => {
                this.renderValidation(validation, index);
            });
        }
        
        /**
         * Save mapping configuration
         */
        saveMappingConfiguration() {
            if (!this.currentConnection) return;
            
            // Collect all mapping data
            this.collectMappingData();
            
            // Update connection object
            this.currentConnection.mapping = this.mappings;
            
            // Close modal
            this.closeMappingModal();
            
            // Show success message
            this.showSuccessMessage('Field mapping configuration saved successfully!');
        }
        
        /**
         * Collect mapping data from form
         */
        collectMappingData() {
            this.mappings = [];
            $('.ryvr-mapping-row').each((index, row) => {
                const $row = $(row);
                const mapping = {
                    source: $row.find('.mapping-source-field').val(),
                    target: $row.find('.mapping-target-field').val(),
                    required: $row.find('.mapping-required').is(':checked'),
                    defaultValue: $row.find('.mapping-default').val()
                };
                
                if (mapping.source && mapping.target) {
                    this.mappings.push(mapping);
                }
            });
        }
        
        /**
         * Show success message
         */
        showSuccessMessage(message) {
            const notification = $(`
                <div class="notice notice-success is-dismissible ryvr-notification">
                    <p>${message}</p>
                </div>
            `);
            
            $('.wrap').prepend(notification);
            
            setTimeout(() => {
                notification.fadeOut();
            }, 3000);
        }
        
        /**
         * Test mapping with sample data
         */
        testMapping() {
            const sampleInput = $('#sample-input').val();
            
            try {
                const inputData = JSON.parse(sampleInput || '{}');
                const mappedOutput = this.simulateMapping(inputData);
                
                $('#mapping-preview-output').text(JSON.stringify(mappedOutput, null, 2));
            } catch (error) {
                $('#mapping-preview-output').text('Error: ' + error.message);
            }
        }
        
        /**
         * Simulate mapping transformation
         */
        simulateMapping(inputData) {
            const output = {};
            
            this.mappings.forEach(mapping => {
                const sourceValue = this.getNestedValue(inputData, mapping.source);
                const targetValue = sourceValue !== undefined ? sourceValue : mapping.defaultValue;
                
                if (targetValue !== undefined) {
                    this.setNestedValue(output, mapping.target, targetValue);
                }
            });
            
            return output;
        }
        
        /**
         * Get nested value using dot notation
         */
        getNestedValue(obj, path) {
            return path.split('.').reduce((current, key) => {
                return current && current[key] !== undefined ? current[key] : undefined;
            }, obj);
        }
        
        /**
         * Set nested value using dot notation
         */
        setNestedValue(obj, path, value) {
            const keys = path.split('.');
            const lastKey = keys.pop();
            const target = keys.reduce((current, key) => {
                if (current[key] === undefined) current[key] = {};
                return current[key];
            }, obj);
            
            target[lastKey] = value;
        }
        
        /**
         * Clear modal data
         */
        clearModalData() {
            $('#ryvr-field-mappings').empty();
            $('#ryvr-transformations').empty();
            $('#ryvr-validations').empty();
            $('#source-fields-list').empty();
            $('#target-fields-list').empty();
            $('#sample-input').val('');
            $('#mapping-preview-output').text('');
        }
        
        /**
         * Update mapping indices after removal
         */
        updateMappingIndices() {
            $('.ryvr-mapping-row').each((index, row) => {
                $(row).attr('data-index', index);
            });
        }
        
        /**
         * Collect transformation data
         */
        collectTransformationData() {
            this.transformations = [];
            $('.ryvr-transformation-row').each((index, row) => {
                const $row = $(row);
                try {
                    const params = JSON.parse($row.find('.transformation-params').val() || '{}');
                    const transformation = {
                        field: $row.find('.transformation-field').val(),
                        function: $row.find('.transformation-function').val(),
                        params: params
                    };
                    
                    if (transformation.field && transformation.function) {
                        this.transformations.push(transformation);
                    }
                } catch (e) {
                    console.error('Invalid transformation parameters:', e);
                }
            });
        }
        
        /**
         * Collect validation data
         */
        collectValidationData() {
            this.validations = [];
            $('.ryvr-validation-row').each((index, row) => {
                const $row = $(row);
                try {
                    const params = JSON.parse($row.find('.validation-params').val() || '{}');
                    const validation = {
                        field: $row.find('.validation-field').val(),
                        rule: $row.find('.validation-rule').val(),
                        params: params,
                        message: $row.find('.validation-message').val()
                    };
                    
                    if (validation.field && validation.rule) {
                        this.validations.push(validation);
                    }
                } catch (e) {
                    console.error('Invalid validation parameters:', e);
                }
            });
        }
    }

    // Initialize Field Mapping Manager when document is ready
    $(document).ready(function() {
        if (typeof window.ryvrFieldMapping === 'undefined') {
            window.ryvrFieldMapping = new FieldMappingManager();
        }
    });

})(jQuery); 