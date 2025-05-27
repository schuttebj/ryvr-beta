<?php
/**
 * Ryvr Users Admin View
 *
 * @package Ryvr
 * @since 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Check user permissions
if (!current_user_can('manage_ryvr_users')) {
    wp_die(__('You do not have sufficient permissions to access this page.', 'ryvr'));
}
?>

<div class="wrap">
    <h1 class="wp-heading-inline"><?php echo esc_html(get_admin_page_title()); ?></h1>
    <a href="#" class="page-title-action" id="add-user-btn"><?php _e('Add New User', 'ryvr'); ?></a>
    
    <div class="ryvr-users">
        <!-- User Stats -->
        <div class="user-stats">
            <div class="stat-card">
                <h3><?php _e('Total Users', 'ryvr'); ?></h3>
                <div class="stat-number">0</div>
            </div>
            <div class="stat-card">
                <h3><?php _e('Active Users', 'ryvr'); ?></h3>
                <div class="stat-number">0</div>
            </div>
            <div class="stat-card">
                <h3><?php _e('Admin Users', 'ryvr'); ?></h3>
                <div class="stat-number">0</div>
            </div>
        </div>

        <!-- Filter Section -->
        <div class="users-filters">
            <form method="get" action="">
                <input type="hidden" name="page" value="ryvr-users">
                
                <div class="filter-row">
                    <label for="user-role"><?php _e('Role:', 'ryvr'); ?></label>
                    <select name="role" id="user-role">
                        <option value=""><?php _e('All Roles', 'ryvr'); ?></option>
                        <option value="ryvr_admin"><?php _e('Ryvr Admin', 'ryvr'); ?></option>
                        <option value="ryvr_agency"><?php _e('Agency Manager', 'ryvr'); ?></option>
                        <option value="ryvr_user"><?php _e('Ryvr User', 'ryvr'); ?></option>
                    </select>
                    
                    <label for="user-agency"><?php _e('Agency:', 'ryvr'); ?></label>
                    <select name="agency" id="user-agency">
                        <option value=""><?php _e('All Agencies', 'ryvr'); ?></option>
                        <option value="1"><?php _e('Demo Agency', 'ryvr'); ?></option>
                    </select>
                    
                    <label for="user-status"><?php _e('Status:', 'ryvr'); ?></label>
                    <select name="status" id="user-status">
                        <option value=""><?php _e('All Statuses', 'ryvr'); ?></option>
                        <option value="active"><?php _e('Active', 'ryvr'); ?></option>
                        <option value="inactive"><?php _e('Inactive', 'ryvr'); ?></option>
                        <option value="suspended"><?php _e('Suspended', 'ryvr'); ?></option>
                    </select>
                    
                    <input type="submit" class="button" value="<?php _e('Filter', 'ryvr'); ?>">
                </div>
            </form>
        </div>

        <!-- Users Table -->
        <div class="users-table-container">
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th scope="col" class="column-name"><?php _e('Name', 'ryvr'); ?></th>
                        <th scope="col" class="column-email"><?php _e('Email', 'ryvr'); ?></th>
                        <th scope="col" class="column-role"><?php _e('Role', 'ryvr'); ?></th>
                        <th scope="col" class="column-agency"><?php _e('Agency', 'ryvr'); ?></th>
                        <th scope="col" class="column-workflows"><?php _e('Workflows', 'ryvr'); ?></th>
                        <th scope="col" class="column-status"><?php _e('Status', 'ryvr'); ?></th>
                        <th scope="col" class="column-last-login"><?php _e('Last Login', 'ryvr'); ?></th>
                        <th scope="col" class="column-actions"><?php _e('Actions', 'ryvr'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // TODO: Replace with actual user data
                    $current_user = wp_get_current_user();
                    $sample_users = [
                        [
                            'id' => $current_user->ID,
                            'name' => $current_user->display_name ?: $current_user->user_login,
                            'email' => $current_user->user_email,
                            'role' => 'ryvr_admin',
                            'agency' => 'System',
                            'workflow_count' => 0,
                            'status' => 'active',
                            'last_login' => current_time('mysql')
                        ]
                    ];
                    
                    if (empty($sample_users)) {
                        echo '<tr><td colspan="8" class="no-users">' . __('No users found. Click "Add New User" to create one.', 'ryvr') . '</td></tr>';
                    } else {
                        foreach ($sample_users as $user) {
                            $status_class = 'status-' . esc_attr($user['status']);
                            $role_display = str_replace('ryvr_', '', $user['role']);
                            $role_display = ucwords(str_replace('_', ' ', $role_display));
                            ?>
                            <tr>
                                <td class="column-name">
                                    <strong><?php echo esc_html($user['name']); ?></strong>
                                </td>
                                <td class="column-email">
                                    <a href="mailto:<?php echo esc_attr($user['email']); ?>">
                                        <?php echo esc_html($user['email']); ?>
                                    </a>
                                </td>
                                <td class="column-role">
                                    <?php echo esc_html($role_display); ?>
                                </td>
                                <td class="column-agency">
                                    <?php echo esc_html($user['agency']); ?>
                                </td>
                                <td class="column-workflows">
                                    <?php echo esc_html($user['workflow_count']); ?>
                                </td>
                                <td class="column-status">
                                    <span class="status-badge <?php echo $status_class; ?>">
                                        <?php echo esc_html(ucfirst($user['status'])); ?>
                                    </span>
                                </td>
                                <td class="column-last-login">
                                    <?php echo esc_html(mysql2date(get_option('date_format') . ' ' . get_option('time_format'), $user['last_login'])); ?>
                                </td>
                                <td class="column-actions">
                                    <button type="button" class="button button-small edit-user" data-user-id="<?php echo esc_attr($user['id']); ?>">
                                        <?php _e('Edit', 'ryvr'); ?>
                                    </button>
                                    <?php if ($user['id'] != get_current_user_id()): ?>
                                    <button type="button" class="button button-small button-link-delete delete-user" data-user-id="<?php echo esc_attr($user['id']); ?>">
                                        <?php _e('Delete', 'ryvr'); ?>
                                    </button>
                                    <?php endif; ?>
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

<!-- Add/Edit User Modal -->
<div id="user-modal" class="ryvr-modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h2 id="modal-title"><?php _e('Add New User', 'ryvr'); ?></h2>
            <button type="button" class="modal-close">&times;</button>
        </div>
        <div class="modal-body">
            <form id="user-form">
                <input type="hidden" id="user-id" name="user_id" value="">
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="user-name"><?php _e('Full Name', 'ryvr'); ?> <span class="required">*</span></label>
                        </th>
                        <td>
                            <input type="text" id="user-name" name="user_name" class="regular-text" required>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="user-email"><?php _e('Email Address', 'ryvr'); ?> <span class="required">*</span></label>
                        </th>
                        <td>
                            <input type="email" id="user-email" name="user_email" class="regular-text" required>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="user-username"><?php _e('Username', 'ryvr'); ?> <span class="required">*</span></label>
                        </th>
                        <td>
                            <input type="text" id="user-username" name="user_username" class="regular-text" required>
                            <p class="description"><?php _e('Used for login. Cannot be changed after creation.', 'ryvr'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="user-password"><?php _e('Password', 'ryvr'); ?> <span class="required">*</span></label>
                        </th>
                        <td>
                            <input type="password" id="user-password" name="user_password" class="regular-text" required>
                            <p class="description"><?php _e('Minimum 8 characters. Leave blank when editing to keep current password.', 'ryvr'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="user-role-select"><?php _e('Role', 'ryvr'); ?> <span class="required">*</span></label>
                        </th>
                        <td>
                            <select id="user-role-select" name="user_role" required>
                                <option value=""><?php _e('Select Role', 'ryvr'); ?></option>
                                <option value="ryvr_admin"><?php _e('Ryvr Admin', 'ryvr'); ?></option>
                                <option value="ryvr_agency"><?php _e('Agency Manager', 'ryvr'); ?></option>
                                <option value="ryvr_user"><?php _e('Ryvr User', 'ryvr'); ?></option>
                            </select>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="user-agency-select"><?php _e('Agency', 'ryvr'); ?></label>
                        </th>
                        <td>
                            <select id="user-agency-select" name="user_agency">
                                <option value=""><?php _e('No Agency', 'ryvr'); ?></option>
                                <option value="1"><?php _e('Demo Agency', 'ryvr'); ?></option>
                            </select>
                            <p class="description"><?php _e('Only required for Agency Managers and Users.', 'ryvr'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="user-status-select"><?php _e('Status', 'ryvr'); ?></label>
                        </th>
                        <td>
                            <select id="user-status-select" name="user_status">
                                <option value="active"><?php _e('Active', 'ryvr'); ?></option>
                                <option value="inactive"><?php _e('Inactive', 'ryvr'); ?></option>
                                <option value="suspended"><?php _e('Suspended', 'ryvr'); ?></option>
                            </select>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="send-notification"><?php _e('Notification', 'ryvr'); ?></label>
                        </th>
                        <td>
                            <input type="checkbox" id="send-notification" name="send_notification" value="1" checked>
                            <label for="send-notification"><?php _e('Send account details to user via email', 'ryvr'); ?></label>
                        </td>
                    </tr>
                </table>
            </form>
        </div>
        <div class="modal-footer">
            <button type="button" class="button button-secondary" id="cancel-user"><?php _e('Cancel', 'ryvr'); ?></button>
            <button type="submit" class="button button-primary" id="save-user"><?php _e('Save User', 'ryvr'); ?></button>
        </div>
    </div>
</div>

<style>
.ryvr-users {
    max-width: 1200px;
}

.user-stats {
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

.users-filters {
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

.filter-row select {
    min-width: 120px;
}

.users-table-container {
    background: #fff;
    border: 1px solid #c3c4c7;
    border-radius: 4px;
    margin: 20px 0;
}

.column-name { width: 15%; }
.column-email { width: 15%; }
.column-role { width: 12%; }
.column-agency { width: 12%; }
.column-workflows { width: 8%; }
.column-status { width: 8%; }
.column-last-login { width: 12%; }
.column-actions { width: 18%; }

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

.no-users {
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
    margin: 3% auto;
    border: 1px solid #c3c4c7;
    border-radius: 4px;
    width: 600px;
    max-width: 90%;
    max-height: 90%;
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
    const modal = document.getElementById('user-modal');
    const addBtn = document.getElementById('add-user-btn');
    const closeBtn = document.querySelector('.modal-close');
    const cancelBtn = document.getElementById('cancel-user');
    const saveBtn = document.getElementById('save-user');
    const form = document.getElementById('user-form');
    
    // Open modal for new user
    addBtn?.addEventListener('click', function(e) {
        e.preventDefault();
        document.getElementById('modal-title').textContent = '<?php _e('Add New User', 'ryvr'); ?>';
        form.reset();
        document.getElementById('user-id').value = '';
        document.getElementById('user-password').required = true;
        document.getElementById('user-username').disabled = false;
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
    
    // Edit user
    document.querySelectorAll('.edit-user').forEach(function(button) {
        button.addEventListener('click', function() {
            const userId = this.getAttribute('data-user-id');
            document.getElementById('modal-title').textContent = '<?php _e('Edit User', 'ryvr'); ?>';
            document.getElementById('user-id').value = userId;
            document.getElementById('user-password').required = false;
            document.getElementById('user-username').disabled = true;
            
            // TODO: Load user data
            
            modal.style.display = 'block';
        });
    });
    
    // Delete user
    document.querySelectorAll('.delete-user').forEach(function(button) {
        button.addEventListener('click', function() {
            const userId = this.getAttribute('data-user-id');
            if (confirm('<?php _e('Are you sure you want to delete this user? This action cannot be undone.', 'ryvr'); ?>')) {
                // TODO: Implement delete functionality
                alert('<?php _e('Delete functionality will be implemented.', 'ryvr'); ?>');
            }
        });
    });
    
    // Save user
    saveBtn?.addEventListener('click', function() {
        if (form.checkValidity()) {
            // TODO: Implement save functionality
            alert('<?php _e('Save functionality will be implemented.', 'ryvr'); ?>');
            closeModal();
        } else {
            form.reportValidity();
        }
    });
    
    // Role-based agency requirement
    document.getElementById('user-role-select')?.addEventListener('change', function() {
        const agencyField = document.getElementById('user-agency-select');
        const isRequired = this.value === 'ryvr_agency' || this.value === 'ryvr_user';
        
        if (isRequired) {
            agencyField.required = true;
            agencyField.parentNode.querySelector('.description').innerHTML = '<?php _e('Required for Agency Managers and Users.', 'ryvr'); ?>';
        } else {
            agencyField.required = false;
            agencyField.parentNode.querySelector('.description').innerHTML = '<?php _e('Only required for Agency Managers and Users.', 'ryvr'); ?>';
        }
    });
});
</script> 