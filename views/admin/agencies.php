<?php
/**
 * Ryvr Agencies Admin View
 *
 * @package Ryvr
 * @since 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Check user permissions
if (!current_user_can('manage_ryvr_agencies')) {
    wp_die(__('You do not have sufficient permissions to access this page.', 'ryvr'));
}
?>

<div class="wrap">
    <h1 class="wp-heading-inline"><?php echo esc_html(get_admin_page_title()); ?></h1>
    <a href="#" class="page-title-action" id="add-agency-btn"><?php _e('Add New Agency', 'ryvr'); ?></a>
    
    <div class="ryvr-agencies">
        <!-- Agency Stats -->
        <div class="agency-stats">
            <div class="stat-card">
                <h3><?php _e('Total Agencies', 'ryvr'); ?></h3>
                <div class="stat-number">0</div>
            </div>
            <div class="stat-card">
                <h3><?php _e('Active Agencies', 'ryvr'); ?></h3>
                <div class="stat-number">0</div>
            </div>
            <div class="stat-card">
                <h3><?php _e('Total Users', 'ryvr'); ?></h3>
                <div class="stat-number">0</div>
            </div>
        </div>

        <!-- Agencies Table -->
        <div class="agencies-table-container">
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th scope="col" class="column-name"><?php _e('Agency Name', 'ryvr'); ?></th>
                        <th scope="col" class="column-contact"><?php _e('Contact Email', 'ryvr'); ?></th>
                        <th scope="col" class="column-users"><?php _e('Users', 'ryvr'); ?></th>
                        <th scope="col" class="column-workflows"><?php _e('Workflows', 'ryvr'); ?></th>
                        <th scope="col" class="column-status"><?php _e('Status', 'ryvr'); ?></th>
                        <th scope="col" class="column-created"><?php _e('Created', 'ryvr'); ?></th>
                        <th scope="col" class="column-actions"><?php _e('Actions', 'ryvr'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // TODO: Replace with actual agency data
                    $sample_agencies = [
                        [
                            'id' => 1,
                            'name' => 'Demo Agency',
                            'contact_email' => 'contact@demoagency.com',
                            'user_count' => 3,
                            'workflow_count' => 5,
                            'status' => 'active',
                            'created_date' => current_time('mysql')
                        ]
                    ];
                    
                    if (empty($sample_agencies)) {
                        echo '<tr><td colspan="7" class="no-agencies">' . __('No agencies found. Click "Add New Agency" to create one.', 'ryvr') . '</td></tr>';
                    } else {
                        foreach ($sample_agencies as $agency) {
                            $status_class = 'status-' . esc_attr($agency['status']);
                            ?>
                            <tr>
                                <td class="column-name">
                                    <strong><?php echo esc_html($agency['name']); ?></strong>
                                </td>
                                <td class="column-contact">
                                    <a href="mailto:<?php echo esc_attr($agency['contact_email']); ?>">
                                        <?php echo esc_html($agency['contact_email']); ?>
                                    </a>
                                </td>
                                <td class="column-users">
                                    <?php echo esc_html($agency['user_count']); ?>
                                </td>
                                <td class="column-workflows">
                                    <?php echo esc_html($agency['workflow_count']); ?>
                                </td>
                                <td class="column-status">
                                    <span class="status-badge <?php echo $status_class; ?>">
                                        <?php echo esc_html(ucfirst($agency['status'])); ?>
                                    </span>
                                </td>
                                <td class="column-created">
                                    <?php echo esc_html(mysql2date(get_option('date_format'), $agency['created_date'])); ?>
                                </td>
                                <td class="column-actions">
                                    <button type="button" class="button button-small edit-agency" data-agency-id="<?php echo esc_attr($agency['id']); ?>">
                                        <?php _e('Edit', 'ryvr'); ?>
                                    </button>
                                    <button type="button" class="button button-small button-link-delete delete-agency" data-agency-id="<?php echo esc_attr($agency['id']); ?>">
                                        <?php _e('Delete', 'ryvr'); ?>
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
    </div>
</div>

<!-- Add/Edit Agency Modal -->
<div id="agency-modal" class="ryvr-modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h2 id="modal-title"><?php _e('Add New Agency', 'ryvr'); ?></h2>
            <button type="button" class="modal-close">&times;</button>
        </div>
        <div class="modal-body">
            <form id="agency-form">
                <input type="hidden" id="agency-id" name="agency_id" value="">
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="agency-name"><?php _e('Agency Name', 'ryvr'); ?> <span class="required">*</span></label>
                        </th>
                        <td>
                            <input type="text" id="agency-name" name="agency_name" class="regular-text" required>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="contact-email"><?php _e('Contact Email', 'ryvr'); ?> <span class="required">*</span></label>
                        </th>
                        <td>
                            <input type="email" id="contact-email" name="contact_email" class="regular-text" required>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="contact-name"><?php _e('Contact Name', 'ryvr'); ?></label>
                        </th>
                        <td>
                            <input type="text" id="contact-name" name="contact_name" class="regular-text">
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="agency-website"><?php _e('Website', 'ryvr'); ?></label>
                        </th>
                        <td>
                            <input type="url" id="agency-website" name="agency_website" class="regular-text" placeholder="https://">
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="agency-status"><?php _e('Status', 'ryvr'); ?></label>
                        </th>
                        <td>
                            <select id="agency-status" name="agency_status">
                                <option value="active"><?php _e('Active', 'ryvr'); ?></option>
                                <option value="inactive"><?php _e('Inactive', 'ryvr'); ?></option>
                                <option value="suspended"><?php _e('Suspended', 'ryvr'); ?></option>
                            </select>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="agency-description"><?php _e('Description', 'ryvr'); ?></label>
                        </th>
                        <td>
                            <textarea id="agency-description" name="agency_description" rows="4" cols="50"></textarea>
                        </td>
                    </tr>
                </table>
            </form>
        </div>
        <div class="modal-footer">
            <button type="button" class="button button-secondary" id="cancel-agency"><?php _e('Cancel', 'ryvr'); ?></button>
            <button type="submit" class="button button-primary" id="save-agency"><?php _e('Save Agency', 'ryvr'); ?></button>
        </div>
    </div>
</div>

<style>
.ryvr-agencies {
    max-width: 1200px;
}

.agency-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin: 20px 0;
}

.stat-card {
    background: #fff;
    border: 1px solid #c3c4c7;
    border-radius: 4px;
    padding: 20px;
    text-align: center;
}

.stat-card h3 {
    margin: 0 0 10px 0;
    color: #1d2327;
}

.stat-number {
    font-size: 2em;
    font-weight: bold;
    color: #2271b1;
}

.agencies-table-container {
    background: #fff;
    border: 1px solid #c3c4c7;
    border-radius: 4px;
    margin: 20px 0;
}

.column-name { width: 20%; }
.column-contact { width: 20%; }
.column-users { width: 8%; }
.column-workflows { width: 8%; }
.column-status { width: 12%; }
.column-created { width: 12%; }
.column-actions { width: 20%; }

.status-badge {
    padding: 3px 8px;
    border-radius: 3px;
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
}

.status-active {
    background: #00a32a;
    color: #fff;
}

.status-inactive {
    background: #646970;
    color: #fff;
}

.status-suspended {
    background: #d63638;
    color: #fff;
}

.no-agencies {
    text-align: center;
    padding: 30px;
    color: #646970;
    font-style: italic;
}

/* Modal Styles */
.ryvr-modal {
    position: fixed;
    z-index: 100000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,0.5);
}

.modal-content {
    background-color: #fff;
    margin: 5% auto;
    border: 1px solid #c3c4c7;
    border-radius: 4px;
    width: 600px;
    max-width: 90%;
    max-height: 80%;
    overflow-y: auto;
}

.modal-header {
    padding: 20px;
    border-bottom: 1px solid #c3c4c7;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.modal-header h2 {
    margin: 0;
}

.modal-close {
    background: none;
    border: none;
    font-size: 24px;
    cursor: pointer;
    color: #646970;
}

.modal-close:hover {
    color: #d63638;
}

.modal-body {
    padding: 20px;
}

.modal-footer {
    padding: 20px;
    border-top: 1px solid #c3c4c7;
    text-align: right;
}

.modal-footer .button {
    margin-left: 10px;
}

.required {
    color: #d63638;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('agency-modal');
    const addBtn = document.getElementById('add-agency-btn');
    const closeBtn = document.querySelector('.modal-close');
    const cancelBtn = document.getElementById('cancel-agency');
    const saveBtn = document.getElementById('save-agency');
    const form = document.getElementById('agency-form');
    
    // Open modal for new agency
    addBtn?.addEventListener('click', function(e) {
        e.preventDefault();
        document.getElementById('modal-title').textContent = '<?php _e('Add New Agency', 'ryvr'); ?>';
        form.reset();
        document.getElementById('agency-id').value = '';
        modal.style.display = 'block';
    });
    
    // Close modal
    function closeModal() {
        modal.style.display = 'none';
    }
    
    closeBtn?.addEventListener('click', closeModal);
    cancelBtn?.addEventListener('click', closeModal);
    
    // Close modal when clicking outside
    window.addEventListener('click', function(e) {
        if (e.target === modal) {
            closeModal();
        }
    });
    
    // Edit agency
    document.querySelectorAll('.edit-agency').forEach(function(button) {
        button.addEventListener('click', function() {
            const agencyId = this.getAttribute('data-agency-id');
            document.getElementById('modal-title').textContent = '<?php _e('Edit Agency', 'ryvr'); ?>';
            document.getElementById('agency-id').value = agencyId;
            
            // TODO: Load agency data
            
            modal.style.display = 'block';
        });
    });
    
    // Delete agency
    document.querySelectorAll('.delete-agency').forEach(function(button) {
        button.addEventListener('click', function() {
            const agencyId = this.getAttribute('data-agency-id');
            if (confirm('<?php _e('Are you sure you want to delete this agency? This action cannot be undone.', 'ryvr'); ?>')) {
                // TODO: Implement delete functionality
                alert('<?php _e('Delete functionality will be implemented.', 'ryvr'); ?>');
            }
        });
    });
    
    // Save agency
    saveBtn?.addEventListener('click', function() {
        if (form.checkValidity()) {
            // TODO: Implement save functionality
            alert('<?php _e('Save functionality will be implemented.', 'ryvr'); ?>');
            closeModal();
        } else {
            form.reportValidity();
        }
    });
});
</script> 