<?php
/**
 * Test script for field mapping and data processing functionality
 */

require_once __DIR__ . '/ryvr-core.php';

echo "=== Ryvr Field Mapping and Data Processing Test ===\n\n";

try {
    // Test the DataProcessor
    echo "1. Testing DataProcessor...\n";
    
    $data_processor = new \Ryvr\Engine\DataProcessor();
    
    // Test field mapping
    $source_data = [
        'user' => [
            'profile' => [
                'name' => 'John Doe',
                'email' => 'john@example.com'
            ],
            'id' => 123
        ],
        'metadata' => [
            'timestamp' => '2024-01-15 10:30:00',
            'source' => 'api'
        ]
    ];
    
    $field_mapping = [
        ['source' => 'user.profile.name', 'target' => 'full_name'],
        ['source' => 'user.profile.email', 'target' => 'email_address'],
        ['source' => 'user.id', 'target' => 'user_id'],
        ['source' => 'metadata.timestamp', 'target' => 'created_at']
    ];
    
    $mapped_data = $data_processor->apply_field_mapping($source_data, $field_mapping);
    
    echo "Source data:\n";
    print_r($source_data);
    echo "\nMapped data:\n";
    print_r($mapped_data);
    
    // Test data transformations
    echo "\n2. Testing data transformations...\n";
    
    $transformations = [
        [
            'field' => 'full_name',
            'function' => 'uppercase',
            'params' => []
        ],
        [
            'field' => 'email_address',
            'function' => 'lowercase',
            'params' => []
        ],
        [
            'field' => 'created_at',
            'function' => 'format_date',
            'params' => ['format' => 'Y-m-d']
        ]
    ];
    
    $transformed_data = $data_processor->apply_transformations($mapped_data, $transformations);
    
    echo "Transformed data:\n";
    print_r($transformed_data);
    
    // Test data validation
    echo "\n3. Testing data validation...\n";
    
    $validation_rules = [
        'full_name' => [
            ['rule' => 'required'],
            ['rule' => 'string'],
            ['rule' => 'min_length', 'params' => ['length' => 2]]
        ],
        'email_address' => [
            ['rule' => 'required'],
            ['rule' => 'email']
        ],
        'user_id' => [
            ['rule' => 'required'],
            ['rule' => 'integer']
        ]
    ];
    
    $validation_result = $data_processor->validate_data($transformed_data, $validation_rules);
    
    echo "Validation result:\n";
    print_r($validation_result);
    
    // Test complete processing pipeline
    echo "\n4. Testing complete data processing pipeline...\n";
    
    $processed_data = $data_processor->process_step_data(
        $source_data,
        $field_mapping,
        $validation_rules,
        $transformations
    );
    
    echo "Final processed data:\n";
    print_r($processed_data);
    
    echo "\n5. Testing FlowEngine...\n";
    
    // Create a simple workflow for testing
    $workflow_definition = [
        'id' => 'test-field-mapping-workflow',
        'name' => 'Test Field Mapping Workflow',
        'description' => 'A workflow to test field mapping functionality',
        'steps' => [
            [
                'id' => 'step1',
                'type' => 'transformer',
                'template' => 'Processing user: {{input.user.profile.name}}',
                'input' => []
            ],
            [
                'id' => 'step2',
                'type' => 'mapper',
                'mapping' => $field_mapping,
                'input_data' => [] // Will use context
            ],
            [
                'id' => 'step3',
                'type' => 'validator',
                'validation_rules' => $validation_rules,
                'error_handling' => 'continue'
            ]
        ],
        'connections' => [
            [
                'id' => 'conn1',
                'source' => 'step1',
                'target' => 'step2',
                'mapping' => $field_mapping
            ],
            [
                'id' => 'conn2',
                'source' => 'step2',
                'target' => 'step3',
                'mapping' => []
            ]
        ]
    ];
    
    $flow_engine = new \Ryvr\Engine\FlowEngine();
    
    $input_data = [
        'user' => [
            'profile' => [
                'name' => 'Jane Smith',
                'email' => 'jane@test.com'
            ],
            'id' => 456
        ],
        'metadata' => [
            'timestamp' => '2024-01-15 14:45:00',
            'source' => 'webhook'
        ]
    ];
    
    echo "Executing workflow with FlowEngine...\n";
    echo "Input data:\n";
    print_r($input_data);
    
    $workflow_result = $flow_engine->execute_workflow($workflow_definition, $input_data);
    
    echo "\nWorkflow execution result:\n";
    print_r($workflow_result);
    
    echo "\nExecution context:\n";
    print_r($flow_engine->get_context());
    
    echo "\nStep results:\n";
    print_r($flow_engine->get_step_results());
    
    echo "\n=== Field Mapping and Data Processing Test Completed Successfully! ===\n";
    
} catch (\Exception $e) {
    echo "Error during field mapping test: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

// Test available functions and rules
echo "\n6. Available transformation functions:\n";
$data_processor = new \Ryvr\Engine\DataProcessor();
echo "- " . implode("\n- ", $data_processor->get_available_transformation_functions()) . "\n";

echo "\nAvailable validation rules:\n";
echo "- " . implode("\n- ", $data_processor->get_available_validation_rules()) . "\n";

echo "\n=== Test completed ===\n";
?> 