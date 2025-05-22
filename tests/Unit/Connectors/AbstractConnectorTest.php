<?php
declare(strict_types=1);

namespace Ryvr\Tests\Unit\Connectors;

use PHPUnit\Framework\TestCase;
use Ryvr\Connectors\AbstractConnector;
use Ryvr\Connectors\RyvrConnectorInterface;

class AbstractConnectorTest extends TestCase
{
    /**
     * Test the get_icon_url method returns the expected URL.
     */
    public function testGetIconUrl(): void
    {
        // Create a mock of AbstractConnector
        $connector = $this->getMockForAbstractClass(AbstractConnector::class);
        
        // Set up the mock to return a specific ID
        $connector->method('get_id')->willReturn('test-connector');
        
        // Test get_icon_url
        $expected = RYVR_PLUGIN_URL . 'assets/images/connectors/test-connector.svg';
        $this->assertEquals($expected, $connector->get_icon_url());
    }
    
    /**
     * Test that the register_trigger method returns false by default.
     */
    public function testRegisterTriggerReturnsFalse(): void
    {
        // Create a mock of AbstractConnector
        $connector = $this->getMockForAbstractClass(AbstractConnector::class);
        
        // Test register_trigger returns false by default
        $callback = function() {};
        $result = $connector->register_trigger('test-trigger', $callback, [], []);
        
        $this->assertFalse($result);
    }
    
    /**
     * Test that AbstractConnector implements RyvrConnectorInterface.
     */
    public function testImplementsInterface(): void
    {
        // Create a mock of AbstractConnector
        $connector = $this->getMockForAbstractClass(AbstractConnector::class);
        
        // Test it implements the interface
        $this->assertInstanceOf(RyvrConnectorInterface::class, $connector);
    }
} 