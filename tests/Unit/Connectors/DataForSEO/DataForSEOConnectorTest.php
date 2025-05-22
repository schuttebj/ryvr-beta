<?php
declare(strict_types=1);

namespace Ryvr\Tests\Unit\Connectors\DataForSEO;

use PHPUnit\Framework\TestCase;
use Ryvr\Connectors\DataForSEO\DataForSEOConnector;
use Ryvr\Connectors\AbstractConnector;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;

class DataForSEOConnectorTest extends TestCase
{
    /**
     * Test that the connector returns the correct ID.
     */
    public function testGetId(): void
    {
        $connector = new DataForSEOConnector();
        $this->assertEquals('dataforseo', $connector->get_id());
    }
    
    /**
     * Test that the connector returns a non-empty name.
     */
    public function testGetName(): void
    {
        $connector = new DataForSEOConnector();
        $this->assertNotEmpty($connector->get_name());
    }
    
    /**
     * Test that the connector returns a non-empty description.
     */
    public function testGetDescription(): void
    {
        $connector = new DataForSEOConnector();
        $this->assertNotEmpty($connector->get_description());
    }
    
    /**
     * Test that the connector returns the correct icon URL.
     */
    public function testGetIconUrl(): void
    {
        $connector = new DataForSEOConnector();
        $expected = RYVR_PLUGIN_URL . 'assets/images/connectors/dataforseo.svg';
        $this->assertEquals($expected, $connector->get_icon_url());
    }
    
    /**
     * Test that the connector returns authentication fields.
     */
    public function testGetAuthFields(): void
    {
        $connector = new DataForSEOConnector();
        $authFields = $connector->get_auth_fields();
        
        $this->assertIsArray($authFields);
        $this->assertArrayHasKey('login', $authFields);
        $this->assertArrayHasKey('password', $authFields);
        $this->assertArrayHasKey('use_sandbox', $authFields);
    }
    
    /**
     * Test that the connector validates authentication credentials correctly.
     */
    public function testValidateAuth(): void
    {
        // Create a mock Guzzle client with a successful response
        $mock = new MockHandler([
            new Response(200, [], json_encode(['status_code' => 20000])),
        ]);
        
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);
        
        // Create a mock connector that uses our mock client
        $connector = $this->getMockBuilder(DataForSEOConnector::class)
            ->onlyMethods(['getClient'])
            ->getMock();
        
        $connector->method('getClient')->willReturn($client);
        
        // Test with valid credentials
        $credentials = [
            'login' => 'valid-login',
            'password' => 'valid-password',
        ];
        
        $this->assertTrue($connector->validate_auth($credentials));
    }
    
    /**
     * Test that the connector fails validation with invalid credentials.
     */
    public function testValidateAuthFailure(): void
    {
        // Create a mock Guzzle client with an error response
        $mock = new MockHandler([
            new RequestException('Error Communicating with Server', new Request('GET', 'test')),
        ]);
        
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);
        
        // Create a mock connector that uses our mock client
        $connector = $this->getMockBuilder(DataForSEOConnector::class)
            ->onlyMethods(['getClient'])
            ->getMock();
        
        $connector->method('getClient')->willReturn($client);
        
        // Test with invalid credentials
        $credentials = [
            'login' => 'invalid-login',
            'password' => 'invalid-password',
        ];
        
        $this->assertFalse($connector->validate_auth($credentials));
    }
    
    /**
     * Test that the connector validates authentication with sandbox mode.
     */
    public function testValidateAuthWithSandbox(): void
    {
        // Create a mock Guzzle client with a successful response
        $mock = new MockHandler([
            new Response(200, [], json_encode(['status_code' => 20000])),
        ]);
        
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);
        
        // Create a mock connector that uses our mock client
        $connector = $this->getMockBuilder(DataForSEOConnector::class)
            ->onlyMethods(['getClient'])
            ->getMock();
        
        $connector->method('getClient')->willReturn($client);
        
        // Test with valid credentials and sandbox mode enabled
        $credentials = [
            'login' => 'valid-login',
            'password' => 'valid-password',
            'use_sandbox' => true,
        ];
        
        $this->assertTrue($connector->validate_auth($credentials));
    }
    
    /**
     * Test that the connector returns actions.
     */
    public function testGetActions(): void
    {
        $connector = new DataForSEOConnector();
        $actions = $connector->get_actions();
        
        $this->assertIsArray($actions);
        $this->assertArrayHasKey('keyword_research', $actions);
        $this->assertArrayHasKey('serp_analysis', $actions);
        $this->assertArrayHasKey('competitor_research', $actions);
    }
    
    /**
     * Test that the connector returns triggers.
     */
    public function testGetTriggers(): void
    {
        $connector = new DataForSEOConnector();
        $triggers = $connector->get_triggers();
        
        $this->assertIsArray($triggers);
        $this->assertEmpty($triggers); // DataForSEO doesn't have any triggers
    }
    
    /**
     * Test that the connector extends AbstractConnector.
     */
    public function testExtendsAbstractConnector(): void
    {
        $connector = new DataForSEOConnector();
        $this->assertInstanceOf(AbstractConnector::class, $connector);
    }
} 