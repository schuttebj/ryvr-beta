<?php
/**
 * AJAX Debug Script for Ryvr
 * This script helps debug AJAX issues
 */

// Add this temporarily to see what's happening with AJAX
add_action('wp_ajax_ryvr_debug_ajax', function() {
    // Log all received data
    error_log('AJAX Debug - Action: ryvr_debug_ajax');
    error_log('AJAX Debug - POST data: ' . print_r($_POST, true));
    error_log('AJAX Debug - GET data: ' . print_r($_GET, true));
    error_log('AJAX Debug - Headers: ' . print_r(getallheaders(), true));
    
    wp_send_json_success([
        'message' => 'AJAX debug successful',
        'post_data' => $_POST,
        'get_data' => $_GET,
        'user_can_manage' => current_user_can('manage_options'),
        'nonce_verification' => isset($_POST['nonce']) ? wp_verify_nonce($_POST['nonce'], 'ryvr_workflow_builder') : 'No nonce provided'
    ]);
});

// Add debug info to WordPress admin footer
add_action('admin_footer', function() {
    if (strpos(get_current_screen()->id, 'ryvr-builder') !== false) {
        ?>
        <script>
        // Add debug button to test AJAX
        if (typeof ryvrWorkflowBuilder !== 'undefined') {
            console.log('Ryvr Debug - WorkflowBuilder object:', ryvrWorkflowBuilder);
            
            // Test basic AJAX call
            fetch(ryvrWorkflowBuilder.ajax_url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'ryvr_debug_ajax',
                    nonce: ryvrWorkflowBuilder.nonce,
                    test: 'debug_test'
                })
            })
            .then(response => {
                console.log('Debug AJAX Response Status:', response.status);
                return response.json();
            })
            .then(data => {
                console.log('Debug AJAX Response Data:', data);
            })
            .catch(error => {
                console.error('Debug AJAX Error:', error);
            });
        } else {
            console.error('ryvrWorkflowBuilder object not found - localization failed');
        }
        </script>
        <?php
    }
});

?>

Add this to your WordPress functions.php or create this as a temporary plugin to debug the AJAX issues.

The script will:
1. Create a test AJAX endpoint
2. Log all data received by WordPress
3. Test the AJAX call and show response in browser console
4. Verify nonce and permissions

This will help identify if the issue is:
- Nonce verification failing
- AJAX URL wrong  
- Permissions issue
- WordPress not receiving the request properly 