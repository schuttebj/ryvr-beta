<?php
/**
 * Integration test for Ryvr workflows
 * This script tests the basic workflow functionality
 */

// Define WordPress constants for testing
if (!defined('ABSPATH')) {
    define('ABSPATH', __DIR__ . '/');
}

if (!defined('RYVR_PLUGIN_DIR')) {
    define('RYVR_PLUGIN_DIR', __DIR__ . '/');
}

if (!defined('RYVR_PLUGIN_URL')) {
    define('RYVR_PLUGIN_URL', 'https://example.com/wp-content/plugins/ryvr/');
}

// Create autoloader
spl_autoload_register(function ($class) {
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

// Mock WordPress functions
if (!function_exists('__')) {
    function __($text, $domain = 'default') {
        return $text;
    }
}

if (!function_exists('current_time')) {
    function current_time($type) {
        return date('Y-m-d H:i:s');
    }
}

echo "\n=== Ryvr Integration Test ===\n";

// Test 1: Load sample workflows
echo "\n1. Testing sample workflow loading...\n";
try {
    $sample_workflows_file = __DIR__ . '/examples/sample-workflows.json';
    
    if (!file_exists($sample_workflows_file)) {
        throw new Exception('Sample workflows file not found');
    }
    
    $content = file_get_contents($sample_workflows_file);
    $workflows = json_decode($content, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Invalid JSON: ' . json_last_error_msg());
    }
    
    echo "✓ Sample workflows loaded successfully\n";
    echo "✓ Found " . count($workflows) . " sample workflows:\n";
    
    foreach ($workflows as $key => $workflow) {
        echo "  - {$workflow['name']}: {$workflow['description']}\n";
    }
    
} catch (Exception $e) {
    echo "✗ Failed to load sample workflows: " . $e->getMessage() . "\n";
}

// Test 2: Test connector manager
echo "\n2. Testing connector manager...\n";
try {
    require_once __DIR__ . '/src/Connectors/Manager.php';
    
    $connector_manager = new \Ryvr\Connectors\Manager();
    $connectors = $connector_manager->get_connectors();
    
    echo "✓ Connector manager initialized\n";
    echo "✓ Found " . count($connectors) . " connectors:\n";
    
    foreach ($connectors as $id => $connector) {
        $metadata = $connector->get_metadata();
        $actions = $connector->get_actions();
        echo "  - {$metadata['name']} ({$id}): " . count($actions) . " actions\n";
    }
    
} catch (Exception $e) {
    echo "✗ Failed to initialize connector manager: " . $e->getMessage() . "\n";
}

// Test 3: Test workflow creation and validation
echo "\n3. Testing workflow creation and validation...\n";
try {
    require_once __DIR__ . '/src/Workflows/WorkflowInterface.php';
    require_once __DIR__ . '/src/Workflows/AbstractWorkflow.php';
    require_once __DIR__ . '/src/Workflows/JSONWorkflow.php';
    require_once __DIR__ . '/src/Workflows/Manager.php';
    
    // Get the basic SEO workflow from samples
    $basic_workflow_def = $workflows['basic_seo_workflow'];
    
    $workflow = new \Ryvr\Workflows\JSONWorkflow($basic_workflow_def);
    
    echo "✓ Created workflow: " . $workflow->get_name() . "\n";
    echo "✓ Workflow ID: " . $workflow->get_id() . "\n";
    echo "✓ Steps count: " . count($workflow->get_steps()) . "\n";
    
    // Validate the workflow
    $validation = $workflow->validate();
    if ($validation === true) {
        echo "✓ Workflow validation: PASSED\n";
    } else {
        echo "✗ Workflow validation: FAILED\n";
        foreach ($validation as $error) {
            echo "  - {$error}\n";
        }
    }
    
} catch (Exception $e) {
    echo "✗ Failed to create/validate workflow: " . $e->getMessage() . "\n";
}

// Test 4: Test workflow manager
echo "\n4. Testing workflow manager...\n";
try {
    $workflow_manager = new \Ryvr\Workflows\Manager($connector_manager);
    
    echo "✓ Workflow manager initialized\n";
    
    // Register the basic workflow
    $workflow_manager->register_workflow($workflow);
    
    $registered_workflows = $workflow_manager->get_workflows();
    echo "✓ Registered workflows: " . count($registered_workflows) . "\n";
    
    $retrieved_workflow = $workflow_manager->get_workflow($basic_workflow_def['id']);
    if ($retrieved_workflow) {
        echo "✓ Successfully retrieved workflow by ID\n";
    } else {
        echo "✗ Failed to retrieve workflow by ID\n";
    }
    
} catch (Exception $e) {
    echo "✗ Failed to test workflow manager: " . $e->getMessage() . "\n";
}

// Test 5: Test workflow builder functionality
echo "\n5. Testing workflow builder simulation...\n";
try {
    echo "✓ Testing JSON export/import...\n";
    
    // Export workflow to JSON
    $exported = json_encode($basic_workflow_def, JSON_PRETTY_PRINT);
    echo "✓ Exported workflow JSON (" . strlen($exported) . " bytes)\n";
    
    // Import workflow from JSON
    $imported = json_decode($exported, true);
    if (json_last_error() === JSON_ERROR_NONE) {
        echo "✓ Successfully imported workflow from JSON\n";
        
        // Create workflow from imported data
        $imported_workflow = new \Ryvr\Workflows\JSONWorkflow($imported);
        echo "✓ Created workflow from imported data: " . $imported_workflow->get_name() . "\n";
    } else {
        echo "✗ Failed to import workflow from JSON\n";
    }
    
} catch (Exception $e) {
    echo "✗ Failed to test workflow builder: " . $e->getMessage() . "\n";
}

echo "\n=== Integration Test Complete ===\n";
echo "Run this script to validate your Ryvr installation:\n";
echo "php test-integration.php\n\n";

echo "Next steps:\n";
echo "1. Go to WordPress Admin > Ryvr > Builder\n";
echo "2. Click 'Load Template' to see sample workflows\n";
echo "3. Try dragging tasks from the sidebar to the canvas\n";
echo "4. Configure task parameters in the inspector panel\n";
echo "5. Save and test your workflows\n\n"; 