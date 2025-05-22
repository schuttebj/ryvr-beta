<?php
declare(strict_types=1);

namespace Ryvr\Tests\Unit\Connectors;

use PHPUnit\Framework\TestCase;
use Ryvr\Connectors\Manager;
use Ryvr\Connectors\RyvrConnectorInterface;
use Ryvr\Connectors\OpenAI\OpenAIConnector;
use Ryvr\Connectors\DataForSEO\DataForSEOConnector;

class ManagerTest extends TestCase
{
    /**
     * Test that the manager initializes with the expected connectors.
     */
    public function testInitConnectors(): void
    {
        $manager = new Manager();
        $connectors = $manager->get_connectors();
        
        $this->assertIsArray($connectors);
        $this->assertArrayHasKey('openai', $connectors);
        $this->assertArrayHasKey('dataforseo', $connectors);
        
        $this->assertInstanceOf(OpenAIConnector::class, $connectors['openai']);
        $this->assertInstanceOf(DataForSEOConnector::class, $connectors['dataforseo']);
    }
    
    /**
     * Test that the manager can register a connector.
     */
    public function testRegisterConnector(): void
    {
        $manager = new Manager();
        
        // Create a mock connector
        $connector = $this->createMock(RyvrConnectorInterface::class);
        $connector->method('get_id')->willReturn('test-connector');
        
        // Register the connector
        $manager->register_connector($connector);
        
        // Get all connectors
        $connectors = $manager->get_connectors();
        
        // Check that our connector is in the list
        $this->assertArrayHasKey('test-connector', $connectors);
        $this->assertSame($connector, $connectors['test-connector']);
    }
    
    /**
     * Test that the manager can retrieve a specific connector by ID.
     */
    public function testGetConnector(): void
    {
        $manager = new Manager();
        
        // Create a mock connector
        $connector = $this->createMock(RyvrConnectorInterface::class);
        $connector->method('get_id')->willReturn('test-connector');
        
        // Register the connector
        $manager->register_connector($connector);
        
        // Get the connector by ID
        $retrievedConnector = $manager->get_connector('test-connector');
        
        // Check that we got the right connector
        $this->assertSame($connector, $retrievedConnector);
        
        // Check that getting a non-existent connector returns null
        $this->assertNull($manager->get_connector('non-existent-connector'));
    }
    
    /**
     * Test that the manager registers AJAX handlers when register_connectors is called.
     */
    public function testRegisterConnectors(): void
    {
        // Use anonymous class to capture add_action calls
        $manager = new class extends Manager {
            public $addActionCalls = [];
            
            // Override add_action to capture calls
            protected function _add_action($hook, $callback, $priority = 10, $accepted_args = 1) {
                $this->addActionCalls[] = [
                    'hook' => $hook,
                    'callback' => $callback,
                ];
                return true;
            }
        };
        
        // Call register_connectors
        $manager->register_connectors();
        
        // Check that add_action was called for each AJAX handler
        $expectedHooks = [
            'wp_ajax_ryvr_connector_validate_auth',
            'wp_ajax_ryvr_connector_save_auth',
            'wp_ajax_ryvr_connector_delete_auth',
            'wp_ajax_ryvr_connector_get_actions',
            'wp_ajax_ryvr_connector_get_auth_fields',
        ];
        
        $actualHooks = array_column($manager->addActionCalls, 'hook');
        
        foreach ($expectedHooks as $hook) {
            $this->assertContains($hook, $actualHooks);
        }
    }
} 