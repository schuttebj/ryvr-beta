<?php
declare(strict_types=1);

namespace Ryvr\Connectors\OpenAI;

use Ryvr\Connectors\AbstractConnector;
use Ryvr\Admin\Settings;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

/**
 * OpenAI connector.
 *
 * @since 1.0.0
 */
class OpenAIConnector extends AbstractConnector
{
    /**
     * OpenAI API base URL.
     *
     * @var string
     */
    private const API_URL = 'https://api.openai.com/v1';
    
    /**
     * Get the connector ID.
     *
     * @return string Unique connector identifier.
     *
     * @since 1.0.0
     */
    public function get_id(): string
    {
        return 'openai';
    }
    
    /**
     * Get the connector name.
     *
     * @return string Human-readable connector name.
     *
     * @since 1.0.0
     */
    public function get_name(): string
    {
        return __('OpenAI', 'ryvr');
    }
    
    /**
     * Get the connector description.
     *
     * @return string Human-readable connector description.
     *
     * @since 1.0.0
     */
    public function get_description(): string
    {
        return __('Integrate with OpenAI\'s API for content generation, text completion, and more.', 'ryvr');
    }
    
    /**
     * Get the authentication fields required by this connector.
     *
     * @return array List of authentication field definitions.
     *
     * @since 1.0.0
     */
    public function get_auth_fields(): array
    {
        return [
            'api_key' => [
                'label' => __('API Key', 'ryvr'),
                'type' => 'password',
                'required' => true,
                'description' => __('Your OpenAI API key.', 'ryvr'),
                'placeholder' => 'sk-...',
            ],
            'organization_id' => [
                'label' => __('Organization ID', 'ryvr'),
                'type' => 'text',
                'required' => false,
                'description' => __('Your OpenAI organization ID (optional).', 'ryvr'),
                'placeholder' => 'org-...',
            ],
            'custom_endpoint' => [
                'label' => __('Custom API Endpoint', 'ryvr'),
                'type' => 'text',
                'required' => false,
                'description' => __('Custom API endpoint URL (for testing or specific deployments).', 'ryvr'),
                'placeholder' => 'https://api.example.com/v1',
            ],
        ];
    }
    
    /**
     * Get a Guzzle HTTP client.
     *
     * @return \GuzzleHttp\Client
     *
     * @since 1.0.0
     */
    protected function getClient(): \GuzzleHttp\Client
    {
        return new Client();
    }
    
    /**
     * Get the API URL to use, considering custom endpoint if provided.
     *
     * @param array $credentials Authentication credentials.
     *
     * @return string The API URL to use.
     */
    private function getApiUrl(array $credentials): string
    {
        return !empty($credentials['custom_endpoint']) ? rtrim($credentials['custom_endpoint'], '/') : self::API_URL;
    }
    
    /**
     * Validate authentication credentials.
     *
     * @param array $credentials Authentication credentials.
     *
     * @return bool Whether the credentials are valid.
     *
     * @since 1.0.0
     */
    public function validate_auth(array $credentials): bool
    {
        if (empty($credentials['api_key'])) {
            return false;
        }
        
        try {
            $client = $this->getClient();
            $api_url = $this->getApiUrl($credentials);
            
            $headers = [
                'Authorization' => 'Bearer ' . $credentials['api_key'],
                'Content-Type' => 'application/json',
            ];
            
            if (!empty($credentials['organization_id'])) {
                $headers['OpenAI-Organization'] = $credentials['organization_id'];
            }
            
            $response = $client->request('GET', $api_url . '/models', [
                'headers' => $headers,
            ]);
            
            return $response->getStatusCode() === 200;
        } catch (GuzzleException $e) {
            $this->log(
                'Failed to validate OpenAI credentials: ' . $e->getMessage(),
                ['error' => $e->getMessage()],
                'error'
            );
            
            return false;
        }
    }
    
    /**
     * Get the available actions for this connector.
     *
     * @return array List of available action definitions.
     *
     * @since 1.0.0
     */
    public function get_actions(): array
    {
        return [
            'generate_text' => [
                'label' => __('Generate Text', 'ryvr'),
                'description' => __('Generate text using OpenAI\'s GPT models.', 'ryvr'),
                'fields' => [
                    'model' => [
                        'label' => __('Model', 'ryvr'),
                        'type' => 'select',
                        'required' => true,
                        'options' => [
                            'gpt-4o' => 'GPT-4o',
                            'gpt-4-turbo' => 'GPT-4 Turbo',
                            'gpt-4' => 'GPT-4',
                            'gpt-3.5-turbo' => 'GPT-3.5 Turbo',
                        ],
                        'default' => 'gpt-3.5-turbo',
                    ],
                    'prompt' => [
                        'label' => __('Prompt', 'ryvr'),
                        'type' => 'textarea',
                        'required' => true,
                        'description' => __('The prompt to generate text from.', 'ryvr'),
                    ],
                    'max_tokens' => [
                        'label' => __('Max Tokens', 'ryvr'),
                        'type' => 'number',
                        'required' => false,
                        'description' => __('Maximum number of tokens to generate.', 'ryvr'),
                        'default' => 1024,
                    ],
                    'temperature' => [
                        'label' => __('Temperature', 'ryvr'),
                        'type' => 'number',
                        'required' => false,
                        'description' => __('Controls randomness. Lower values are more focused, higher values are more creative.', 'ryvr'),
                        'default' => 0.7,
                    ],
                ],
            ],
            'generate_image' => [
                'label' => __('Generate Image', 'ryvr'),
                'description' => __('Generate an image using DALL-E models.', 'ryvr'),
                'fields' => [
                    'model' => [
                        'label' => __('Model', 'ryvr'),
                        'type' => 'select',
                        'required' => true,
                        'options' => [
                            'dall-e-3' => 'DALL-E 3',
                            'dall-e-2' => 'DALL-E 2',
                        ],
                        'default' => 'dall-e-3',
                    ],
                    'prompt' => [
                        'label' => __('Prompt', 'ryvr'),
                        'type' => 'textarea',
                        'required' => true,
                        'description' => __('The prompt to generate an image from.', 'ryvr'),
                    ],
                    'size' => [
                        'label' => __('Size', 'ryvr'),
                        'type' => 'select',
                        'required' => false,
                        'options' => [
                            '1024x1024' => '1024x1024',
                            '1024x1792' => '1024x1792',
                            '1792x1024' => '1792x1024',
                        ],
                        'default' => '1024x1024',
                    ],
                ],
            ],
        ];
    }
    
    /**
     * Get the available triggers for this connector.
     *
     * @return array List of available trigger definitions.
     *
     * @since 1.0.0
     */
    public function get_triggers(): array
    {
        // OpenAI doesn't have any triggers
        return [];
    }
    
    /**
     * Execute an action.
     *
     * @param string $action_id Action identifier.
     * @param array  $params    Action parameters.
     * @param array  $auth      Authentication credentials.
     *
     * @return array Result of the action execution.
     *
     * @throws \Exception If the action execution fails.
     *
     * @since 1.0.0
     */
    public function execute_action(string $action_id, array $params, array $auth): array
    {
        switch ($action_id) {
            case 'generate_text':
                return $this->generate_text($params, $auth);
                
            case 'generate_image':
                return $this->generate_image($params, $auth);
                
            default:
                throw new \Exception(
                    sprintf(__('Unsupported action: %s', 'ryvr'), $action_id)
                );
        }
    }
    
    /**
     * Generate text using OpenAI's API.
     *
     * @param array $params Action parameters.
     * @param array $auth   Authentication credentials.
     *
     * @return array Result of the action execution.
     *
     * @throws \Exception If the API request fails.
     *
     * @since 1.0.0
     */
    private function generate_text(array $params, array $auth): array
    {
        try {
            $client = new Client();
            $api_url = $this->getApiUrl($auth);
            
            $headers = [
                'Authorization' => 'Bearer ' . $auth['api_key'],
                'Content-Type' => 'application/json',
            ];
            
            if (!empty($auth['organization_id'])) {
                $headers['OpenAI-Organization'] = $auth['organization_id'];
            }
            
            $data = [
                'model' => $params['model'] ?? 'gpt-3.5-turbo',
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => $params['prompt'] ?? '',
                    ],
                ],
                'max_tokens' => (int) ($params['max_tokens'] ?? 1024),
                'temperature' => (float) ($params['temperature'] ?? 0.7),
            ];
            
            $response = $client->request('POST', $api_url . '/chat/completions', [
                'headers' => $headers,
                'json' => $data,
            ]);
            
            $result = json_decode($response->getBody()->getContents(), true);
            
            if (!$result || !isset($result['choices'][0]['message']['content'])) {
                throw new \Exception(__('Invalid response from OpenAI API', 'ryvr'));
            }
            
            return [
                'text' => $result['choices'][0]['message']['content'],
                'usage' => $result['usage'] ?? [],
                'model' => $result['model'] ?? $data['model'],
            ];
        } catch (GuzzleException $e) {
            $this->log(
                'Failed to generate text with OpenAI: ' . $e->getMessage(),
                [
                    'error' => $e->getMessage(),
                    'params' => $params,
                ],
                'error'
            );
            
            throw new \Exception(
                sprintf(__('Failed to generate text: %s', 'ryvr'), $e->getMessage())
            );
        }
    }
    
    /**
     * Generate an image using OpenAI's API.
     *
     * @param array $params Action parameters.
     * @param array $auth   Authentication credentials.
     *
     * @return array Result of the action execution.
     *
     * @throws \Exception If the API request fails.
     *
     * @since 1.0.0
     */
    private function generate_image(array $params, array $auth): array
    {
        try {
            $client = new Client();
            $api_url = $this->getApiUrl($auth);
            
            $headers = [
                'Authorization' => 'Bearer ' . $auth['api_key'],
                'Content-Type' => 'application/json',
            ];
            
            if (!empty($auth['organization_id'])) {
                $headers['OpenAI-Organization'] = $auth['organization_id'];
            }
            
            $data = [
                'model' => $params['model'] ?? 'dall-e-3',
                'prompt' => $params['prompt'] ?? '',
                'n' => 1,
                'size' => $params['size'] ?? '1024x1024',
                'response_format' => 'url',
            ];
            
            $response = $client->request('POST', $api_url . '/images/generations', [
                'headers' => $headers,
                'json' => $data,
            ]);
            
            $result = json_decode($response->getBody()->getContents(), true);
            
            if (!$result || !isset($result['data'][0]['url'])) {
                throw new \Exception(__('Invalid response from OpenAI API', 'ryvr'));
            }
            
            return [
                'image_url' => $result['data'][0]['url'],
                'model' => $data['model'],
            ];
        } catch (GuzzleException $e) {
            $this->log(
                'Failed to generate image with OpenAI: ' . $e->getMessage(),
                [
                    'error' => $e->getMessage(),
                    'params' => $params,
                ],
                'error'
            );
            
            throw new \Exception(
                sprintf(__('Failed to generate image: %s', 'ryvr'), $e->getMessage())
            );
        }
    }
} 