<?php
declare(strict_types=1);

namespace Ryvr\Tests\Unit\Connectors\OpenAI;

use PHPUnit\Framework\TestCase;
use Ryvr\Connectors\OpenAI\OpenAIConnector;
use Ryvr\Connectors\AbstractConnector;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;

class OpenAIConnectorTest extends TestCase
{
    /**
     * Test that the connector returns the correct ID.
     */
    public function testGetId(): void
    {
        $connector = new OpenAIConnector();
        $this->assertEquals('openai', $connector->get_id());
    }
    
    /**
     * Test that the connector returns a non-empty name.
     */
    public function testGetName(): void
    {
        $connector = new OpenAIConnector();
        $this->assertNotEmpty($connector->get_name());
    }
    
    /**
     * Test that the connector returns a non-empty description.
     */
    public function testGetDescription(): void
    {
        $connector = new OpenAIConnector();
        $this->assertNotEmpty($connector->get_description());
    }
    
    /**
     * Test that the connector returns the correct icon URL.
     */
    public function testGetIconUrl(): void
    {
        $connector = new OpenAIConnector();
        $expected = RYVR_PLUGIN_URL . 'assets/images/connectors/openai.svg';
        $this->assertEquals($expected, $connector->get_icon_url());
    }
    
    /**
     * Test that the connector returns authentication fields.
     */
    public function testGetAuthFields(): void
    {
        $connector = new OpenAIConnector();
        $authFields = $connector->get_auth_fields();
        
        $this->assertIsArray($authFields);
        $this->assertArrayHasKey('api_key', $authFields);
        $this->assertArrayHasKey('organization_id', $authFields);
        $this->assertArrayHasKey('custom_endpoint', $authFields);
    }
    
    /**
     * Test that the connector validates authentication credentials correctly.
     */
    public function testValidateAuth(): void
    {
        // Create a mock Guzzle client with a successful response
        $mock = new MockHandler([
            new Response(200, [], json_encode(['data' => [['id' => 'gpt-3.5-turbo']]])),
        ]);
        
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);
        
        // Create a mock connector that uses our mock client
        $connector = $this->getMockBuilder(OpenAIConnector::class)
            ->onlyMethods(['getClient'])
            ->getMock();
        
        $connector->method('getClient')->willReturn($client);
        
        // Test with valid credentials
        $credentials = [
            'api_key' => 'valid-api-key',
            'organization_id' => 'valid-org-id',
        ];
        
        $this->assertTrue($connector->validate_auth($credentials));
    }
    
    /**
     * Test that the connector validates authentication with a custom endpoint.
     */
    public function testValidateAuthWithCustomEndpoint(): void
    {
        // Create a mock Guzzle client with a successful response
        $mock = new MockHandler([
            new Response(200, [], json_encode(['data' => [['id' => 'gpt-3.5-turbo']]])),
        ]);
        
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);
        
        // Create a mock connector that uses our mock client
        $connector = $this->getMockBuilder(OpenAIConnector::class)
            ->onlyMethods(['getClient'])
            ->getMock();
        
        $connector->method('getClient')->willReturn($client);
        
        // Test with valid credentials and a custom endpoint
        $credentials = [
            'api_key' => 'valid-api-key',
            'organization_id' => 'valid-org-id',
            'custom_endpoint' => 'https://api.test.com/v1',
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
        $connector = $this->getMockBuilder(OpenAIConnector::class)
            ->onlyMethods(['getClient'])
            ->getMock();
        
        $connector->method('getClient')->willReturn($client);
        
        // Test with invalid credentials
        $credentials = [
            'api_key' => 'invalid-api-key',
        ];
        
        $this->assertFalse($connector->validate_auth($credentials));
    }
    
    /**
     * Test that the connector returns actions.
     */
    public function testGetActions(): void
    {
        $connector = new OpenAIConnector();
        $actions = $connector->get_actions();
        
        $this->assertIsArray($actions);
        $this->assertArrayHasKey('generate_text', $actions);
        $this->assertArrayHasKey('generate_image', $actions);
    }
    
    /**
     * Test that the connector returns triggers.
     */
    public function testGetTriggers(): void
    {
        $connector = new OpenAIConnector();
        $triggers = $connector->get_triggers();
        
        $this->assertIsArray($triggers);
        $this->assertEmpty($triggers); // OpenAI doesn't have any triggers
    }
    
    /**
     * Test that the connector extends AbstractConnector.
     */
    public function testExtendsAbstractConnector(): void
    {
        $connector = new OpenAIConnector();
        $this->assertInstanceOf(AbstractConnector::class, $connector);
    }
} 