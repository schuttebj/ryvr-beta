<?php
// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Get connector manager from global or create new instance
global $ryvr_connector_manager;
if (!$ryvr_connector_manager) {
    require_once RYVR_PLUGIN_DIR . 'src/Connectors/Manager.php';
    $ryvr_connector_manager = new \Ryvr\Connectors\Manager();
}

$manager = $ryvr_connector_manager;
$connectors = $manager->get_connectors();
?>

<div class="ryvr-admin-wrap">
    <div class="ryvr-admin-header">
        <h1><?php esc_html_e('Connectors', 'ryvr'); ?></h1>
        <p class="ryvr-subtitle">
            <?php esc_html_e('Configure API connections for your marketing automation workflows.', 'ryvr'); ?>
        </p>
    </div>
    
    <div class="ryvr-connectors-grid">
        <?php if (empty($connectors)) : ?>
            <div class="ryvr-notice ryvr-notice-info">
                <p><?php esc_html_e('No connectors available. Please ensure connectors are properly loaded.', 'ryvr'); ?></p>
            </div>
        <?php else : ?>
            <?php foreach ($connectors as $connector) : ?>
                <div class="ryvr-connector-card" data-connector-id="<?php echo esc_attr($connector->get_id()); ?>">
                    <div class="ryvr-connector-header">
                        <div class="ryvr-connector-icon" style="background: <?php echo esc_attr($connector->get_metadata()['brand_color'] ?? '#666666'); ?>"></div>
                        <h3 class="ryvr-connector-title"><?php echo esc_html($connector->get_name()); ?></h3>
                    </div>
                    
                    <div class="ryvr-connector-content">
                        <p class="ryvr-connector-description"><?php echo esc_html($connector->get_description()); ?></p>
                        
                        <div class="ryvr-connector-actions">
                            <button type="button" class="button button-primary ryvr-connector-configure" data-connector-id="<?php echo esc_attr($connector->get_id()); ?>">
                                <?php esc_html_e('Configure', 'ryvr'); ?>
                            </button>
                            
                            <button type="button" class="button ryvr-connector-test" data-connector-id="<?php echo esc_attr($connector->get_id()); ?>">
                                <?php esc_html_e('Test Connection', 'ryvr'); ?>
                            </button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Configure Connector Modal -->
<div id="ryvr-connector-modal" class="ryvr-modal" style="display: none;">
    <div class="ryvr-modal-content">
        <div class="ryvr-modal-header">
            <h2 id="ryvr-connector-modal-title"></h2>
            <button type="button" class="ryvr-modal-close">&times;</button>
        </div>
        
        <div class="ryvr-modal-body">
            <div id="ryvr-connector-auth-form">
                <div class="ryvr-loading" style="display: none;">
                    <span class="spinner is-active"></span>
                    <p><?php esc_html_e('Loading...', 'ryvr'); ?></p>
                </div>
                
                <form id="ryvr-auth-form">
                    <div id="ryvr-auth-fields"></div>
                    
                    <div class="ryvr-form-actions">
                        <button type="button" class="button button-primary ryvr-save-auth">
                            <?php esc_html_e('Save Credentials', 'ryvr'); ?>
                        </button>
                        
                        <button type="button" class="button ryvr-test-auth">
                            <?php esc_html_e('Test Connection', 'ryvr'); ?>
                        </button>
                        
                        <button type="button" class="button ryvr-delete-auth">
                            <?php esc_html_e('Delete Credentials', 'ryvr'); ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
jQuery(document).ready(function($) {
    var currentConnector = null;
    
    // Open connector configuration modal
    $('.ryvr-connector-configure').on('click', function() {
        var connectorId = $(this).data('connector-id');
        openConnectorModal(connectorId);
    });
    
    // Test connector connection
    $('.ryvr-connector-test').on('click', function() {
        var connectorId = $(this).data('connector-id');
        testConnectorConnection(connectorId);
    });
    
    // Close modal
    $('.ryvr-modal-close').on('click', function() {
        $('#ryvr-connector-modal').hide();
    });
    
    // Save auth credentials
    $('.ryvr-save-auth').on('click', function() {
        saveAuthCredentials();
    });
    
    // Test auth credentials
    $('.ryvr-test-auth').on('click', function() {
        testAuthCredentials();
    });
    
    // Delete auth credentials
    $('.ryvr-delete-auth').on('click', function() {
        deleteAuthCredentials();
    });
    
    // Close modal when clicking outside
    $(window).on('click', function(e) {
        if ($(e.target).hasClass('ryvr-modal')) {
            $('#ryvr-connector-modal').hide();
        }
    });
    
    // Function to open connector modal
    function openConnectorModal(connectorId) {
        currentConnector = connectorId;
        
        // Debug logging
        console.log('Opening connector modal for:', connectorId);
        console.log('AJAX URL:', ryvrData.ajaxUrl);
        console.log('Nonce:', ryvrData.nonce);
        
        // Get connector info via AJAX
        $('.ryvr-loading').show();
        $('#ryvr-auth-fields').empty();
        
        $.ajax({
            url: ryvrData.ajaxUrl,
            type: 'POST',
            data: {
                action: 'ryvr_connector_get_actions',
                connector_id: connectorId,
                nonce: ryvrData.nonce
            },
            success: function(response) {
                console.log('Get actions response:', response);
                $('.ryvr-loading').hide();
                
                if (response.success) {
                    // Find the connector in the DOM to get its name
                    var connectorName = $('.ryvr-connector-card[data-connector-id="' + connectorId + '"] .ryvr-connector-title').text();
                    $('#ryvr-connector-modal-title').text(connectorName + ' ' + ryvrData.i18n.configuration);
                    
                    // Load auth fields via another AJAX call
                    loadAuthFields(connectorId);
                } else {
                    console.error('Failed to get actions:', response);
                    alert(response.data ? response.data.message : 'Unknown error');
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX error getting actions:', xhr, status, error);
                $('.ryvr-loading').hide();
                alert(ryvrData.i18n.errorLoadingConnector + ' (Status: ' + status + ')');
            }
        });
        
        $('#ryvr-connector-modal').show();
    }
    
    // Function to load auth fields
    function loadAuthFields(connectorId) {
        console.log('Loading auth fields for:', connectorId);
        $('.ryvr-loading').show();
        
        $.ajax({
            url: ryvrData.ajaxUrl,
            type: 'POST',
            data: {
                action: 'ryvr_connector_get_auth_fields',
                connector_id: connectorId,
                nonce: ryvrData.nonce
            },
            success: function(response) {
                console.log('Get auth fields response:', response);
                $('.ryvr-loading').hide();
                
                if (response.success) {
                    var fields = response.data.fields;
                    var credentials = response.data.saved_credentials || {};
                    
                    console.log('Auth fields:', fields);
                    console.log('Saved credentials:', credentials);
                    
                    // Build the form
                    var formHtml = '';
                    
                    for (var key in fields) {
                        var field = fields[key];
                        formHtml += '<div class="ryvr-form-field">';
                        formHtml += '<label for="ryvr-auth-' + key + '">' + field.label + '</label>';
                        
                        if (field.type === 'password') {
                            formHtml += '<input type="password" id="ryvr-auth-' + key + '" name="' + key + '" class="regular-text" placeholder="' + (field.placeholder || '') + '" ' + (field.required ? 'required' : '') + '>';
                        } else if (field.type === 'checkbox') {
                            var checked = credentials[key] ? 'checked' : '';
                            formHtml += '<input type="checkbox" id="ryvr-auth-' + key + '" name="' + key + '" value="1" ' + checked + ' ' + (field.required ? 'required' : '') + '>';
                        } else {
                            formHtml += '<input type="text" id="ryvr-auth-' + key + '" name="' + key + '" class="regular-text" value="' + (credentials[key] || '') + '" placeholder="' + (field.placeholder || '') + '" ' + (field.required ? 'required' : '') + '>';
                        }
                        
                        if (field.description) {
                            formHtml += '<p class="description">' + field.description + '</p>';
                        }
                        
                        formHtml += '</div>';
                    }
                    
                    $('#ryvr-auth-fields').html(formHtml);
                } else {
                    console.error('Failed to get auth fields:', response);
                    alert(response.data ? response.data.message : 'Unknown error loading auth fields');
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX error getting auth fields:', xhr, status, error);
                $('.ryvr-loading').hide();
                alert(ryvrData.i18n.errorLoadingFields + ' (Status: ' + status + ')');
            }
        });
    }
    
    // Function to save auth credentials
    function saveAuthCredentials() {
        var credentials = {};
        
        // Collect form values
        $('#ryvr-auth-form input').each(function() {
            var name = $(this).attr('name');
            if ($(this).attr('type') === 'checkbox') {
                credentials[name] = $(this).is(':checked');
            } else {
                credentials[name] = $(this).val();
            }
        });
        
        console.log('Saving credentials for connector:', currentConnector);
        console.log('Credentials to save:', credentials);
        
        $('.ryvr-loading').show();
        
        $.ajax({
            url: ryvrData.ajaxUrl,
            type: 'POST',
            data: {
                action: 'ryvr_connector_save_auth',
                connector_id: currentConnector,
                credentials: JSON.stringify(credentials),
                nonce: ryvrData.nonce
            },
            success: function(response) {
                console.log('Save auth response:', response);
                $('.ryvr-loading').hide();
                
                if (response.success) {
                    alert(response.data.message);
                } else {
                    console.error('Failed to save credentials:', response);
                    alert(response.data ? response.data.message : 'Unknown error saving credentials');
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX error saving credentials:', xhr, status, error);
                $('.ryvr-loading').hide();
                alert(ryvrData.i18n.errorSavingCredentials + ' (Status: ' + status + ')');
            }
        });
    }
    
    // Function to test auth credentials
    function testAuthCredentials() {
        var credentials = {};
        
        // Collect form values
        $('#ryvr-auth-form input').each(function() {
            var name = $(this).attr('name');
            if ($(this).attr('type') === 'checkbox') {
                credentials[name] = $(this).is(':checked');
            } else {
                credentials[name] = $(this).val();
            }
        });
        
        console.log('Testing credentials for connector:', currentConnector);
        console.log('Credentials to test:', credentials);
        
        $('.ryvr-loading').show();
        
        $.ajax({
            url: ryvrData.ajaxUrl,
            type: 'POST',
            data: {
                action: 'ryvr_connector_validate_auth',
                connector_id: currentConnector,
                credentials: JSON.stringify(credentials),
                nonce: ryvrData.nonce
            },
            success: function(response) {
                console.log('Test auth response:', response);
                $('.ryvr-loading').hide();
                
                if (response.success) {
                    alert(response.data.message);
                } else {
                    console.error('Failed to validate credentials:', response);
                    alert(response.data ? response.data.message : 'Unknown error testing credentials');
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX error testing credentials:', xhr, status, error);
                $('.ryvr-loading').hide();
                alert(ryvrData.i18n.errorTestingCredentials + ' (Status: ' + status + ')');
            }
        });
    }
    
    // Function to delete auth credentials
    function deleteAuthCredentials() {
        if (!confirm(ryvrData.i18n.confirmDeleteCredentials)) {
            return;
        }
        
        $('.ryvr-loading').show();
        
        $.ajax({
            url: ryvrData.ajaxUrl,
            type: 'POST',
            data: {
                action: 'ryvr_connector_delete_auth',
                connector_id: currentConnector,
                nonce: ryvrData.nonce
            },
            success: function(response) {
                $('.ryvr-loading').hide();
                
                if (response.success) {
                    // Clear form
                    $('#ryvr-auth-fields input').val('');
                    alert(response.data.message);
                } else {
                    alert(response.data.message);
                }
            },
            error: function() {
                $('.ryvr-loading').hide();
                alert(ryvrData.i18n.errorDeletingCredentials);
            }
        });
    }
    
    // Function to test connector connection
    function testConnectorConnection(connectorId) {
        openConnectorModal(connectorId);
        setTimeout(function() {
            testAuthCredentials();
        }, 1000);
    }
});
</script> 