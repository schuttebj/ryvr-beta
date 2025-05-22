<?php
/**
 * Manual test script for Ryvr connector components.
 * 
 * This script can be used to test connector components outside of WordPress.
 */

// Define WordPress constants needed for testing
if (!defined('ABSPATH')) {
    define('ABSPATH', __DIR__ . '/');
}

if (!defined('RYVR_PLUGIN_DIR')) {
    define('RYVR_PLUGIN_DIR', __DIR__ . '/');
}

if (!defined('RYVR_PLUGIN_URL')) {
    define('RYVR_PLUGIN_URL', 'https://example.com/wp-content/plugins/ryvr/');
}

// Create a minimal autoloader for our classes
spl_autoload_register(function ($class) {
    // Convert namespace to path
    $prefix = 'Ryvr\\';
    $len = strlen($prefix);
    
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }
    
    $relative_class = substr($class, $len);
    $file = __DIR__ . '/src/' . str_replace('\\', '/', $relative_class) . '.php';
    
    if (file_exists($file)) {
        require $file;
    }
});

// Create mock global $wpdb object
global $wpdb;
$wpdb = new class {
    public $prefix = 'wp_';
    
    public function prepare($query, ...$args) {
        echo "DB Query: $query\n";
        return $query;
    }
    
    public function get_var($query) {
        echo "DB Get Var: $query\n";
        return null;
    }
    
    public function get_results($query, $output = OBJECT) {
        echo "DB Get Results: $query\n";
        return [];
    }
    
    public function insert($table, $data, $format = null) {
        echo "DB Insert: $table\n";
        return 1;
    }
    
    public function update($table, $data, $where, $format = null, $where_format = null) {
        echo "DB Update: $table\n";
        return 1;
    }
    
    public function delete($table, $where, $where_format = null) {
        echo "DB Delete: $table\n";
        return 1;
    }
};

// Mock WordPress functions
if (!function_exists('__')) {
    function __($text, $domain = 'default') {
        return $text;
    }
}

if (!function_exists('wp_hash_password')) {
    function wp_hash_password($password) {
        return password_hash($password, PASSWORD_DEFAULT);
    }
}

if (!function_exists('add_action')) {
    function add_action($hook, $callback, $priority = 10, $accepted_args = 1) {
        echo "Action added: $hook\n";
        return true;
    }
}

if (!function_exists('do_action')) {
    function do_action($hook, ...$args) {
        echo "Action called: $hook\n";
        return true;
    }
}

if (!function_exists('get_option')) {
    function get_option($option, $default = false) {
        return $default;
    }
}

if (!function_exists('update_option')) {
    function update_option($option, $value, $autoload = null) {
        echo "Option updated: $option\n";
        return true;
    }
}

// Test the Manager class
echo "Testing Manager class...\n";
require_once __DIR__ . '/src/Connectors/RyvrConnectorInterface.php';
require_once __DIR__ . '/src/Connectors/AbstractConnector.php';
require_once __DIR__ . '/src/Connectors/DataForSEO/DataForSEOConnector.php';
require_once __DIR__ . '/src/Connectors/OpenAI/OpenAIConnector.php';
require_once __DIR__ . '/src/Connectors/Manager.php';

try {
    $manager = new \Ryvr\Connectors\Manager();
    $connectors = $manager->get_connectors();
    
    echo "Found " . count($connectors) . " connectors:\n";
    foreach ($connectors as $id => $connector) {
        echo "- $id: " . $connector->get_name() . " (" . $connector->get_description() . ")\n";
    }
    
    // Register AJAX handlers
    $manager->register_connectors();
    
    echo "All tests passed!\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

// Test DataForSEO connector sandbox mode
echo "\nTesting DataForSEO connector with sandbox mode...\n";
try {
    $connector = new \Ryvr\Connectors\DataForSEO\DataForSEOConnector();
    
    // Check auth fields to verify sandbox mode is included
    $authFields = $connector->get_auth_fields();
    if (isset($authFields['use_sandbox'])) {
        echo "Sandbox mode option is available in auth fields.\n";
    } else {
        echo "Error: Sandbox mode option is missing from auth fields.\n";
    }
    
    // If you have actual DataForSEO sandbox credentials, you can test them here
    // For example:
    // $credentials = [
    //     'login' => 'your-sandbox-login',
    //     'password' => 'your-sandbox-password',
    //     'use_sandbox' => true
    // ];
    // 
    // $isValid = $connector->validate_auth($credentials);
    // echo "Sandbox credentials validation: " . ($isValid ? "Valid" : "Invalid") . "\n";
    
    echo "DataForSEO connector sandbox mode test completed.\n";
} catch (Exception $e) {
    echo "Error testing DataForSEO sandbox mode: " . $e->getMessage() . "\n";
}

// Test OpenAI connector with custom endpoint
echo "\nTesting OpenAI connector with custom endpoint...\n";
try {
    $connector = new \Ryvr\Connectors\OpenAI\OpenAIConnector();
    
    // Check auth fields to verify custom endpoint is included
    $authFields = $connector->get_auth_fields();
    if (isset($authFields['custom_endpoint'])) {
        echo "Custom endpoint option is available in auth fields.\n";
    } else {
        echo "Error: Custom endpoint option is missing from auth fields.\n";
    }
    
    // If you have actual OpenAI credentials and want to test a custom endpoint, you can do it here
    // For example:
    // $credentials = [
    //     'api_key' => 'your-api-key',
    //     'custom_endpoint' => 'https://your-custom-endpoint.com/v1'
    // ];
    // 
    // $isValid = $connector->validate_auth($credentials);
    // echo "Custom endpoint credentials validation: " . ($isValid ? "Valid" : "Invalid") . "\n";
    
    echo "OpenAI connector custom endpoint test completed.\n";
} catch (Exception $e) {
    echo "Error testing OpenAI custom endpoint: " . $e->getMessage() . "\n";
}

// Test the Workflow Engine
echo "\nTesting Workflow Engine...\n";
try {
    // Create a workflow definition
    $workflow_definition = [
        'id' => 'test-workflow',
        'name' => 'Test Workflow',
        'description' => 'A test workflow',
        'steps' => [
            [
                'id' => 'step1',
                'type' => 'transformer',
                'template' => 'Hello, {{input.name}}!',
                'input' => [
                    'name' => 'input.name'
                ]
            ]
        ]
    ];
    
    // Create a workflow from the definition
    require_once __DIR__ . '/src/Workflows/WorkflowInterface.php';
    require_once __DIR__ . '/src/Workflows/AbstractWorkflow.php';
    require_once __DIR__ . '/src/Workflows/JSONWorkflow.php';
    require_once __DIR__ . '/src/Workflows/Manager.php';
    
    $workflow = new \Ryvr\Workflows\JSONWorkflow($workflow_definition);
    
    // Validate the workflow
    $validation = $workflow->validate();
    if ($validation === true) {
        echo "Workflow validation: Valid\n";
    } else {
        echo "Workflow validation: Invalid\n";
        print_r($validation);
    }
    
    // Execute the workflow
    $input = ['name' => 'World'];
    $result = $workflow->execute($input);
    
    echo "Workflow execution result:\n";
    print_r($result);
    
    echo "Workflow engine test completed.\n";
} catch (Exception $e) {
    echo "Error testing workflow engine: " . $e->getMessage() . "\n";
}

// Test the Encryption class
echo "\nTesting Encryption class...\n";
require_once __DIR__ . '/src/Security/Encryption.php';

try {
    if (function_exists('sodium_crypto_secretbox')) {
        // Define a test encryption key for testing
        define('RYVR_ENCRYPTION_KEY', base64_encode(random_bytes(SODIUM_CRYPTO_SECRETBOX_KEYBYTES)));
        
        $data = "This is a test string to encrypt and decrypt";
        
        $encrypted = \Ryvr\Security\Encryption::encrypt($data);
        echo "Encrypted: " . $encrypted . "\n";
        
        $decrypted = \Ryvr\Security\Encryption::decrypt($encrypted);
        echo "Decrypted: " . $decrypted . "\n";
        
        if ($data === $decrypted) {
            echo "Encryption test passed!\n";
        } else {
            echo "Encryption test failed: Decrypted data does not match original.\n";
        }
    } else {
        echo "Encryption test skipped: sodium_crypto_secretbox not available.\n";
    }
} catch (Exception $e) {
    echo "Encryption Error: " . $e->getMessage() . "\n";
} 