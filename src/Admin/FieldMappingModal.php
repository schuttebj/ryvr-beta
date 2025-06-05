<?php
declare(strict_types=1);

namespace Ryvr\Admin;

/**
 * Field mapping modal for workflow builder.
 * Handles data field mapping between workflow steps and API calls.
 *
 * @since 1.0.0
 */
class FieldMappingModal
{
    /**
     * Render the field mapping modal HTML.
     *
     * @return string Modal HTML.
     */
    public function render_modal(): string
    {
        ob_start();
        ?>
        <!-- Field Mapping Modal -->
        <div id="ryvr-field-mapping-modal" class="ryvr-modal" style="display: none;">
            <div class="ryvr-modal-content ryvr-field-mapping-content">
                <div class="ryvr-modal-header">
                    <h3><?php esc_html_e('Field Mapping Configuration', 'ryvr'); ?></h3>
                    <button type="button" class="ryvr-modal-close">&times;</button>
                </div>
                
                <div class="ryvr-modal-body">
                    <div class="ryvr-mapping-info">
                        <p class="description">
                            <?php esc_html_e('Map fields from the source step to the target step. Use dot notation for nested fields (e.g., user.profile.name).', 'ryvr'); ?>
                        </p>
                    </div>
                    
                    <!-- Source and Target Step Information -->
                    <div class="ryvr-step-info">
                        <div class="ryvr-source-step">
                            <h4><?php esc_html_e('Source Step', 'ryvr'); ?></h4>
                            <div class="ryvr-step-details">
                                <span class="step-name" id="source-step-name"></span>
                                <span class="step-type" id="source-step-type"></span>
                            </div>
                            <div class="ryvr-available-fields">
                                <h5><?php esc_html_e('Available Fields', 'ryvr'); ?></h5>
                                <div id="source-fields-list" class="ryvr-fields-list"></div>
                            </div>
                        </div>
                        
                        <div class="ryvr-mapping-arrow">
                            <span class="dashicons dashicons-arrow-right-alt2"></span>
                        </div>
                        
                        <div class="ryvr-target-step">
                            <h4><?php esc_html_e('Target Step', 'ryvr'); ?></h4>
                            <div class="ryvr-step-details">
                                <span class="step-name" id="target-step-name"></span>
                                <span class="step-type" id="target-step-type"></span>
                            </div>
                            <div class="ryvr-required-fields">
                                <h5><?php esc_html_e('Required Fields', 'ryvr'); ?></h5>
                                <div id="target-fields-list" class="ryvr-fields-list"></div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Field Mapping Configuration -->
                    <div class="ryvr-mapping-configuration">
                        <div class="ryvr-mapping-header">
                            <h4><?php esc_html_e('Field Mappings', 'ryvr'); ?></h4>
                            <button type="button" class="button ryvr-add-field-mapping">
                                <?php esc_html_e('Add Mapping', 'ryvr'); ?>
                            </button>
                        </div>
                        
                        <div id="ryvr-field-mappings" class="ryvr-mapping-list">
                            <!-- Dynamic mapping rows will be added here -->
                        </div>
                    </div>
                    
                    <!-- Data Transformation -->
                    <div class="ryvr-transformation-section">
                        <h4><?php esc_html_e('Data Transformations', 'ryvr'); ?></h4>
                        <p class="description">
                            <?php esc_html_e('Apply transformations to data during mapping.', 'ryvr'); ?>
                        </p>
                        
                        <div class="ryvr-transformation-header">
                            <button type="button" class="button ryvr-add-transformation">
                                <?php esc_html_e('Add Transformation', 'ryvr'); ?>
                            </button>
                        </div>
                        
                        <div id="ryvr-transformations" class="ryvr-transformation-list">
                            <!-- Dynamic transformation rows will be added here -->
                        </div>
                    </div>
                    
                    <!-- Data Validation -->
                    <div class="ryvr-validation-section">
                        <h4><?php esc_html_e('Data Validation', 'ryvr'); ?></h4>
                        <p class="description">
                            <?php esc_html_e('Add validation rules to ensure data integrity.', 'ryvr'); ?>
                        </p>
                        
                        <div class="ryvr-validation-header">
                            <button type="button" class="button ryvr-add-validation">
                                <?php esc_html_e('Add Validation Rule', 'ryvr'); ?>
                            </button>
                        </div>
                        
                        <div id="ryvr-validations" class="ryvr-validation-list">
                            <!-- Dynamic validation rows will be added here -->
                        </div>
                    </div>
                    
                    <!-- Preview Section -->
                    <div class="ryvr-mapping-preview">
                        <h4><?php esc_html_e('Mapping Preview', 'ryvr'); ?></h4>
                        <div class="ryvr-preview-content">
                            <div class="ryvr-sample-data">
                                <h5><?php esc_html_e('Sample Input Data', 'ryvr'); ?></h5>
                                <textarea id="sample-input" class="ryvr-code-editor" placeholder='{"example": "data"}'></textarea>
                            </div>
                            
                            <div class="ryvr-preview-result">
                                <h5><?php esc_html_e('Mapped Output', 'ryvr'); ?></h5>
                                <pre id="mapping-preview-output" class="ryvr-preview-output"></pre>
                            </div>
                            
                            <button type="button" class="button ryvr-test-mapping">
                                <?php esc_html_e('Test Mapping', 'ryvr'); ?>
                            </button>
                        </div>
                    </div>
                </div>
                
                <div class="ryvr-modal-footer">
                    <button type="button" class="button button-secondary ryvr-cancel-mapping">
                        <?php esc_html_e('Cancel', 'ryvr'); ?>
                    </button>
                    <button type="button" class="button button-primary ryvr-save-mapping">
                        <?php esc_html_e('Save Mapping', 'ryvr'); ?>
                    </button>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Render field mapping row template.
     *
     * @return string Row template HTML.
     */
    public function render_mapping_row_template(): string
    {
        ob_start();
        ?>
        <script type="text/template" id="ryvr-mapping-row-template">
            <div class="ryvr-mapping-row" data-index="{{index}}">
                <div class="ryvr-mapping-fields">
                    <div class="ryvr-source-field">
                        <label><?php esc_html_e('Source Field', 'ryvr'); ?></label>
                        <div class="field-input-container">
                            <input type="text" class="mapping-source-field" 
                                   placeholder="<?php esc_attr_e('e.g., response.data.id', 'ryvr'); ?>" 
                                   value="{{sourceField}}">
                            <button type="button" class="button-link ryvr-browse-source-fields">
                                <span class="dashicons dashicons-search"></span>
                            </button>
                        </div>
                    </div>
                    
                    <div class="ryvr-mapping-operator">
                        <span class="dashicons dashicons-arrow-right-alt2"></span>
                    </div>
                    
                    <div class="ryvr-target-field">
                        <label><?php esc_html_e('Target Field', 'ryvr'); ?></label>
                        <div class="field-input-container">
                            <input type="text" class="mapping-target-field" 
                                   placeholder="<?php esc_attr_e('e.g., user_id', 'ryvr'); ?>" 
                                   value="{{targetField}}">
                            <button type="button" class="button-link ryvr-browse-target-fields">
                                <span class="dashicons dashicons-search"></span>
                            </button>
                        </div>
                    </div>
                    
                    <div class="ryvr-mapping-actions">
                        <button type="button" class="button-link ryvr-remove-mapping" title="<?php esc_attr_e('Remove mapping', 'ryvr'); ?>">
                            <span class="dashicons dashicons-trash"></span>
                        </button>
                    </div>
                </div>
                
                <div class="ryvr-mapping-options">
                    <label>
                        <input type="checkbox" class="mapping-required" {{#if required}}checked{{/if}}>
                        <?php esc_html_e('Required', 'ryvr'); ?>
                    </label>
                    
                    <label>
                        <input type="checkbox" class="mapping-transform" {{#if transform}}checked{{/if}}>
                        <?php esc_html_e('Apply transformation', 'ryvr'); ?>
                    </label>
                    
                    <div class="ryvr-default-value" style="{{#unless showDefault}}display: none;{{/unless}}">
                        <label><?php esc_html_e('Default Value', 'ryvr'); ?></label>
                        <input type="text" class="mapping-default" placeholder="<?php esc_attr_e('Default value if source is empty', 'ryvr'); ?>" value="{{defaultValue}}">
                    </div>
                </div>
            </div>
        </script>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Render transformation row template.
     *
     * @return string Transformation template HTML.
     */
    public function render_transformation_row_template(): string
    {
        ob_start();
        ?>
        <script type="text/template" id="ryvr-transformation-row-template">
            <div class="ryvr-transformation-row" data-index="{{index}}">
                <div class="ryvr-transformation-field">
                    <label><?php esc_html_e('Field', 'ryvr'); ?></label>
                    <input type="text" class="transformation-field" placeholder="<?php esc_attr_e('Field to transform', 'ryvr'); ?>" value="{{field}}">
                </div>
                
                <div class="ryvr-transformation-function">
                    <label><?php esc_html_e('Function', 'ryvr'); ?></label>
                    <select class="transformation-function">
                        <option value=""><?php esc_html_e('Select function', 'ryvr'); ?></option>
                        <option value="uppercase" {{#if (eq function 'uppercase')}}selected{{/if}}><?php esc_html_e('Uppercase', 'ryvr'); ?></option>
                        <option value="lowercase" {{#if (eq function 'lowercase')}}selected{{/if}}><?php esc_html_e('Lowercase', 'ryvr'); ?></option>
                        <option value="trim" {{#if (eq function 'trim')}}selected{{/if}}><?php esc_html_e('Trim whitespace', 'ryvr'); ?></option>
                        <option value="format_date" {{#if (eq function 'format_date')}}selected{{/if}}><?php esc_html_e('Format date', 'ryvr'); ?></option>
                        <option value="replace" {{#if (eq function 'replace')}}selected{{/if}}><?php esc_html_e('Replace text', 'ryvr'); ?></option>
                        <option value="number_format" {{#if (eq function 'number_format')}}selected{{/if}}><?php esc_html_e('Format number', 'ryvr'); ?></option>
                    </select>
                </div>
                
                <div class="ryvr-transformation-params">
                    <label><?php esc_html_e('Parameters', 'ryvr'); ?></label>
                    <input type="text" class="transformation-params" placeholder='<?php esc_attr_e('{"param": "value"}', 'ryvr'); ?>' value="{{params}}">
                </div>
                
                <div class="ryvr-transformation-actions">
                    <button type="button" class="button-link ryvr-remove-transformation" title="<?php esc_attr_e('Remove transformation', 'ryvr'); ?>">
                        <span class="dashicons dashicons-trash"></span>
                    </button>
                </div>
            </div>
        </script>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Render validation row template.
     *
     * @return string Validation template HTML.
     */
    public function render_validation_row_template(): string
    {
        ob_start();
        ?>
        <script type="text/template" id="ryvr-validation-row-template">
            <div class="ryvr-validation-row" data-index="{{index}}">
                <div class="ryvr-validation-field">
                    <label><?php esc_html_e('Field', 'ryvr'); ?></label>
                    <input type="text" class="validation-field" placeholder="<?php esc_attr_e('Field to validate', 'ryvr'); ?>" value="{{field}}">
                </div>
                
                <div class="ryvr-validation-rule">
                    <label><?php esc_html_e('Rule', 'ryvr'); ?></label>
                    <select class="validation-rule">
                        <option value=""><?php esc_html_e('Select rule', 'ryvr'); ?></option>
                        <option value="required" {{#if (eq rule 'required')}}selected{{/if}}><?php esc_html_e('Required', 'ryvr'); ?></option>
                        <option value="string" {{#if (eq rule 'string')}}selected{{/if}}><?php esc_html_e('String', 'ryvr'); ?></option>
                        <option value="integer" {{#if (eq rule 'integer')}}selected{{/if}}><?php esc_html_e('Integer', 'ryvr'); ?></option>
                        <option value="number" {{#if (eq rule 'number')}}selected{{/if}}><?php esc_html_e('Number', 'ryvr'); ?></option>
                        <option value="email" {{#if (eq rule 'email')}}selected{{/if}}><?php esc_html_e('Email', 'ryvr'); ?></option>
                        <option value="url" {{#if (eq rule 'url')}}selected{{/if}}><?php esc_html_e('URL', 'ryvr'); ?></option>
                        <option value="min_length" {{#if (eq rule 'min_length')}}selected{{/if}}><?php esc_html_e('Minimum length', 'ryvr'); ?></option>
                        <option value="max_length" {{#if (eq rule 'max_length')}}selected{{/if}}><?php esc_html_e('Maximum length', 'ryvr'); ?></option>
                        <option value="array" {{#if (eq rule 'array')}}selected{{/if}}><?php esc_html_e('Array', 'ryvr'); ?></option>
                    </select>
                </div>
                
                <div class="ryvr-validation-params">
                    <label><?php esc_html_e('Parameters', 'ryvr'); ?></label>
                    <input type="text" class="validation-params" placeholder='<?php esc_attr_e('{"length": 5}', 'ryvr'); ?>' value="{{params}}">
                </div>
                
                <div class="ryvr-validation-message">
                    <label><?php esc_html_e('Custom Message', 'ryvr'); ?></label>
                    <input type="text" class="validation-message" placeholder="<?php esc_attr_e('Custom error message', 'ryvr'); ?>" value="{{message}}">
                </div>
                
                <div class="ryvr-validation-actions">
                    <button type="button" class="button-link ryvr-remove-validation" title="<?php esc_attr_e('Remove validation', 'ryvr'); ?>">
                        <span class="dashicons dashicons-trash"></span>
                    </button>
                </div>
            </div>
        </script>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Get available transformation functions.
     *
     * @return array List of transformation functions.
     */
    public function get_transformation_functions(): array
    {
        return [
            'uppercase' => __('Convert to uppercase', 'ryvr'),
            'lowercase' => __('Convert to lowercase', 'ryvr'),
            'trim' => __('Trim whitespace', 'ryvr'),
            'truncate' => __('Truncate text', 'ryvr'),
            'replace' => __('Replace text', 'ryvr'),
            'regex_replace' => __('Regex replace', 'ryvr'),
            'format_date' => __('Format date', 'ryvr'),
            'number_format' => __('Format number', 'ryvr'),
            'json_encode' => __('Encode as JSON', 'ryvr'),
            'json_decode' => __('Decode JSON', 'ryvr'),
            'array_slice' => __('Slice array', 'ryvr'),
            'array_filter' => __('Filter array', 'ryvr'),
            'array_map' => __('Map array field', 'ryvr'),
            'concatenate' => __('Concatenate strings', 'ryvr'),
            'default_value' => __('Set default value', 'ryvr')
        ];
    }
    
    /**
     * Get available validation rules.
     *
     * @return array List of validation rules.
     */
    public function get_validation_rules(): array
    {
        return [
            'required' => __('Required field', 'ryvr'),
            'string' => __('Must be a string', 'ryvr'),
            'integer' => __('Must be an integer', 'ryvr'),
            'number' => __('Must be a number', 'ryvr'),
            'email' => __('Must be a valid email', 'ryvr'),
            'url' => __('Must be a valid URL', 'ryvr'),
            'min_length' => __('Minimum length', 'ryvr'),
            'max_length' => __('Maximum length', 'ryvr'),
            'min_value' => __('Minimum value', 'ryvr'),
            'max_value' => __('Maximum value', 'ryvr'),
            'in' => __('Must be one of specified values', 'ryvr'),
            'not_in' => __('Must not be one of specified values', 'ryvr'),
            'regex' => __('Must match regex pattern', 'ryvr'),
            'array' => __('Must be an array', 'ryvr'),
            'array_min_length' => __('Array minimum length', 'ryvr'),
            'array_max_length' => __('Array maximum length', 'ryvr')
        ];
    }
} 