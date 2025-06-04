<?php
declare(strict_types=1);

namespace Ryvr\Connectors\OpenAI;

use Ryvr\Connectors\AbstractConnector;
use Ryvr\Admin\Settings;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;

/**
 * OpenAI connector with comprehensive API support.
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
    
    private array $cachedModels = [];
    private int $modelsCacheExpiry = 0;
    
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
     * Get connector metadata for the UI.
     *
     * @return array
     */
    public function get_metadata(): array
    {
        return [
            'id' => $this->get_id(),
            'name' => $this->get_name(),
            'description' => $this->get_description(),
            'category' => 'ai',
            'brand_color' => '#00a67e'
        ];
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
                'description' => __('Custom base API URL (e.g., https://api.openai.com/v1 or your proxy URL). Leave empty to use default OpenAI API.', 'ryvr'),
                'placeholder' => 'https://api.openai.com/v1',
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
        if (defined('WP_DEBUG') && WP_DEBUG) {
            try {
                error_log('Ryvr: OpenAI validate_auth called with credentials: ' . print_r($credentials, true));
            } catch (\Exception $e) {
                error_log('Ryvr: OpenAI debug logging error: ' . $e->getMessage());
            }
        }
        
        if (empty($credentials['api_key'])) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Ryvr: OpenAI validation failed - missing API key');
            }
            return false;
        }
        
        try {
            $client = $this->getClient();
            $api_url = $this->getApiUrl($credentials);
            
            if (defined('WP_DEBUG') && WP_DEBUG) {
                try {
                    error_log('Ryvr: OpenAI making request to: ' . $api_url . '/models');
                    error_log('Ryvr: OpenAI API key length: ' . strlen($credentials['api_key']));
                    error_log('Ryvr: OpenAI API key starts with: ' . substr($credentials['api_key'], 0, 10) . '...');
                } catch (\Exception $e) {
                    error_log('Ryvr: OpenAI debug logging error: ' . $e->getMessage());
                }
            }
            
            $headers = [
                'Authorization' => 'Bearer ' . $credentials['api_key'],
                'Content-Type' => 'application/json',
            ];
            
            if (!empty($credentials['organization_id'])) {
                $headers['OpenAI-Organization'] = $credentials['organization_id'];
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log('Ryvr: OpenAI using organization ID: ' . $credentials['organization_id']);
                }
            }
            
            if (defined('WP_DEBUG') && WP_DEBUG) {
                try {
                    error_log('Ryvr: OpenAI request headers: ' . print_r($headers, true));
                } catch (\Exception $e) {
                    error_log('Ryvr: OpenAI debug logging error: ' . $e->getMessage());
                }
            }
            
            $response = $client->request('GET', $api_url . '/models', [
                'headers' => $headers,
                'timeout' => 30,
            ]);
            
            $status_code = $response->getStatusCode();
            $body = $response->getBody()->getContents();
            
            if (defined('WP_DEBUG') && WP_DEBUG) {
                try {
                    error_log('Ryvr: OpenAI response status: ' . $status_code);
                    error_log('Ryvr: OpenAI response body (first 200 chars): ' . substr($body, 0, 200));
                } catch (\Exception $e) {
                    error_log('Ryvr: OpenAI debug logging error: ' . $e->getMessage());
                }
            }
            
            $success = $status_code === 200;
            
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Ryvr: OpenAI validation result: ' . ($success ? 'SUCCESS' : 'FAILED'));
            }
            
            return $success;
            
        } catch (\GuzzleHttp\Exception\ClientException $e) {
            $status_code = $e->getResponse() ? $e->getResponse()->getStatusCode() : 'unknown';
            $response_body = $e->getResponse() ? $e->getResponse()->getBody()->getContents() : 'no response';
            
            if (defined('WP_DEBUG') && WP_DEBUG) {
                try {
                    error_log('Ryvr: OpenAI ClientException - Status: ' . $status_code);
                    error_log('Ryvr: OpenAI ClientException - Response: ' . $response_body);
                    error_log('Ryvr: OpenAI ClientException - Message: ' . $e->getMessage());
                } catch (\Exception $debug_e) {
                    error_log('Ryvr: OpenAI debug logging error: ' . $debug_e->getMessage());
                }
            }
            
            return false;
            
        } catch (\GuzzleHttp\Exception\ServerException $e) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Ryvr: OpenAI ServerException: ' . $e->getMessage());
            }
            
            return false;
            
        } catch (\GuzzleHttp\Exception\ConnectException $e) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Ryvr: OpenAI ConnectException: ' . $e->getMessage());
            }
            
            return false;
            
        } catch (\Exception $e) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                try {
                    error_log('Ryvr: OpenAI general exception: ' . $e->getMessage());
                    error_log('Ryvr: OpenAI exception trace: ' . $e->getTraceAsString());
                } catch (\Exception $debug_e) {
                    error_log('Ryvr: OpenAI debug logging error: ' . $debug_e->getMessage());
                }
            }
            
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
            'chat_completion' => [
                'name' => 'Chat Completion',
                'description' => 'Generate text using chat models',
                'category' => 'text_generation',
                'parameters' => [
                    'required' => ['messages'],
                    'optional' => [
                        'model', 'max_tokens', 'temperature', 'top_p', 'n',
                        'stream', 'stop', 'presence_penalty', 'frequency_penalty',
                        'logit_bias', 'user', 'response_format', 'seed', 'tools',
                        'tool_choice', 'parallel_tool_calls', 'json_schema'
                    ]
                ],
                'output_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'content' => ['type' => 'string'],
                        'role' => ['type' => 'string'],
                        'finish_reason' => ['type' => 'string'],
                        'usage' => ['type' => 'object']
                    ]
                ]
            ],
            'embeddings' => [
                'name' => 'Create Embeddings',
                'description' => 'Create vector embeddings for text',
                'category' => 'embeddings',
                'parameters' => [
                    'required' => ['input'],
                    'optional' => ['model', 'encoding_format', 'dimensions', 'user']
                ],
                'output_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'data' => [
                            'type' => 'array',
                            'items' => [
                                'type' => 'object',
                                'properties' => [
                                    'embedding' => ['type' => 'array'],
                                    'index' => ['type' => 'integer']
                                ]
                            ]
                        ],
                        'usage' => ['type' => 'object']
                    ]
                ]
            ],
            'image_generation' => [
                'name' => 'Generate Images',
                'description' => 'Create images from text descriptions',
                'category' => 'image_generation',
                'parameters' => [
                    'required' => ['prompt'],
                    'optional' => ['model', 'n', 'quality', 'response_format', 'size', 'style', 'user']
                ],
                'output_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'data' => [
                            'type' => 'array',
                            'items' => [
                                'type' => 'object',
                                'properties' => [
                                    'url' => ['type' => 'string'],
                                    'b64_json' => ['type' => 'string'],
                                    'revised_prompt' => ['type' => 'string']
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            'image_edit' => [
                'name' => 'Edit Images',
                'description' => 'Edit images using text descriptions',
                'category' => 'image_editing',
                'parameters' => [
                    'required' => ['image', 'prompt'],
                    'optional' => ['mask', 'model', 'n', 'size', 'response_format', 'user']
                ]
            ],
            'image_variation' => [
                'name' => 'Create Image Variations',
                'description' => 'Create variations of existing images',
                'category' => 'image_editing',
                'parameters' => [
                    'required' => ['image'],
                    'optional' => ['model', 'n', 'response_format', 'size', 'user']
                ]
            ],
            'audio_transcription' => [
                'name' => 'Transcribe Audio',
                'description' => 'Convert audio to text',
                'category' => 'audio',
                'parameters' => [
                    'required' => ['file'],
                    'optional' => ['model', 'language', 'prompt', 'response_format', 'temperature', 'timestamp_granularities']
                ]
            ],
            'audio_translation' => [
                'name' => 'Translate Audio',
                'description' => 'Translate audio to English text',
                'category' => 'audio',
                'parameters' => [
                    'required' => ['file'],
                    'optional' => ['model', 'prompt', 'response_format', 'temperature']
                ]
            ],
            'text_to_speech' => [
                'name' => 'Text to Speech',
                'description' => 'Convert text to spoken audio',
                'category' => 'audio',
                'parameters' => [
                    'required' => ['input', 'voice'],
                    'optional' => ['model', 'response_format', 'speed']
                ]
            ],
            'moderation' => [
                'name' => 'Content Moderation',
                'description' => 'Check content for policy violations',
                'category' => 'safety',
                'parameters' => [
                    'required' => ['input'],
                    'optional' => ['model']
                ]
            ]
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
        $this->init($auth);
        
        switch ($action_id) {
            case 'chat_completion':
                return $this->chat_completion($params);
            case 'embeddings':
                return $this->create_embeddings($params);
            case 'image_generation':
                return $this->generate_image($params);
            case 'image_edit':
                return $this->edit_image($params);
            case 'image_variation':
                return $this->create_image_variation($params);
            case 'audio_transcription':
                return $this->transcribe_audio($params);
            case 'audio_translation':
                return $this->translate_audio($params);
            case 'text_to_speech':
                return $this->text_to_speech($params);
            case 'moderation':
                return $this->moderate_content($params);
            default:
                throw new \InvalidArgumentException("Unknown action: {$action_id}");
        }
    }
    
    /**
     * Get available models from OpenAI API.
     *
     * @return array
     */
    public function get_available_models(): array
    {
        // Check cache first
        if (!empty($this->cachedModels) && time() < $this->modelsCacheExpiry) {
            return $this->cachedModels;
        }

        try {
            $response = $this->make_request('GET', '/models');
            $models = [];
            
            if (isset($response['data'])) {
                foreach ($response['data'] as $model) {
                    $models[] = [
                        'id' => $model['id'],
                        'name' => $model['id'],
                        'category' => $this->categorize_model($model['id']),
                        'owned_by' => $model['owned_by'] ?? 'openai'
                    ];
                }
                
                // Sort models by category and name
                usort($models, function($a, $b) {
                    if ($a['category'] !== $b['category']) {
                        return strcmp($a['category'], $b['category']);
                    }
                    return strcmp($a['id'], $b['id']);
                });
            }
            
            // Cache for 1 hour
            $this->cachedModels = $models;
            $this->modelsCacheExpiry = time() + 3600;
            
            return $models;
            
        } catch (\Exception $e) {
            // Fallback to default models if API call fails
            return $this->get_default_models();
        }
    }

    /**
     * Get default models as fallback.
     *
     * @return array
     */
    private function get_default_models(): array
    {
        return [
            ['id' => 'gpt-4o', 'name' => 'GPT-4o', 'category' => 'chat'],
            ['id' => 'gpt-4o-mini', 'name' => 'GPT-4o Mini', 'category' => 'chat'],
            ['id' => 'gpt-4-turbo', 'name' => 'GPT-4 Turbo', 'category' => 'chat'],
            ['id' => 'gpt-3.5-turbo', 'name' => 'GPT-3.5 Turbo', 'category' => 'chat'],
            ['id' => 'text-embedding-3-large', 'name' => 'Text Embedding 3 Large', 'category' => 'embeddings'],
            ['id' => 'text-embedding-3-small', 'name' => 'Text Embedding 3 Small', 'category' => 'embeddings'],
            ['id' => 'dall-e-3', 'name' => 'DALL-E 3', 'category' => 'image'],
            ['id' => 'dall-e-2', 'name' => 'DALL-E 2', 'category' => 'image'],
            ['id' => 'whisper-1', 'name' => 'Whisper', 'category' => 'audio'],
            ['id' => 'tts-1', 'name' => 'TTS', 'category' => 'audio'],
            ['id' => 'tts-1-hd', 'name' => 'TTS HD', 'category' => 'audio']
        ];
    }

    /**
     * Categorize model by its ID.
     *
     * @param string $modelId
     * @return string
     */
    private function categorize_model(string $modelId): string
    {
        if (str_contains($modelId, 'gpt')) {
            return 'chat';
        }
        if (str_contains($modelId, 'embedding')) {
            return 'embeddings';
        }
        if (str_contains($modelId, 'dall-e')) {
            return 'image';
        }
        if (str_contains($modelId, 'whisper') || str_contains($modelId, 'tts')) {
            return 'audio';
        }
        if (str_contains($modelId, 'moderation')) {
            return 'safety';
        }
        
        return 'other';
    }

    /**
     * Chat completion with JSON Schema support.
     *
     * @param array $params
     * @return array
     */
    private function chat_completion(array $params): array
    {
        $payload = [
            'model' => $params['model'] ?? 'gpt-4o',
            'messages' => $params['messages']
        ];

        // Add optional parameters
        $optional_params = [
            'max_tokens', 'temperature', 'top_p', 'n', 'stream', 'stop',
            'presence_penalty', 'frequency_penalty', 'logit_bias', 'user',
            'seed', 'tools', 'tool_choice', 'parallel_tool_calls'
        ];

        foreach ($optional_params as $param) {
            if (isset($params[$param])) {
                $payload[$param] = $params[$param];
            }
        }

        // Handle JSON Schema response format
        if (isset($params['json_schema'])) {
            $payload['response_format'] = [
                'type' => 'json_schema',
                'json_schema' => $params['json_schema']
            ];
        } elseif (isset($params['response_format'])) {
            $payload['response_format'] = $params['response_format'];
        }

        return $this->make_request('POST', '/chat/completions', $payload);
    }

    /**
     * Create embeddings.
     *
     * @param array $params
     * @return array
     */
    private function create_embeddings(array $params): array
    {
        $payload = [
            'model' => $params['model'] ?? 'text-embedding-3-small',
            'input' => $params['input']
        ];

        $optional_params = ['encoding_format', 'dimensions', 'user'];
        foreach ($optional_params as $param) {
            if (isset($params[$param])) {
                $payload[$param] = $params[$param];
            }
        }

        return $this->make_request('POST', '/embeddings', $payload);
    }

    /**
     * Generate image.
     *
     * @param array $params
     * @return array
     */
    private function generate_image(array $params): array
    {
        $payload = [
            'model' => $params['model'] ?? 'dall-e-3',
            'prompt' => $params['prompt']
        ];

        $optional_params = ['n', 'quality', 'response_format', 'size', 'style', 'user'];
        foreach ($optional_params as $param) {
            if (isset($params[$param])) {
                $payload[$param] = $params[$param];
            }
        }

        return $this->make_request('POST', '/images/generations', $payload);
    }

    /**
     * Edit image.
     *
     * @param array $params
     * @return array
     */
    private function edit_image(array $params): array
    {
        // This requires multipart/form-data, more complex implementation needed
        throw new \Exception('Image editing not yet implemented - requires file upload handling');
    }

    /**
     * Create image variation.
     *
     * @param array $params
     * @return array
     */
    private function create_image_variation(array $params): array
    {
        // This requires multipart/form-data, more complex implementation needed
        throw new \Exception('Image variations not yet implemented - requires file upload handling');
    }

    /**
     * Transcribe audio.
     *
     * @param array $params
     * @return array
     */
    private function transcribe_audio(array $params): array
    {
        // This requires multipart/form-data, more complex implementation needed
        throw new \Exception('Audio transcription not yet implemented - requires file upload handling');
    }

    /**
     * Translate audio.
     *
     * @param array $params
     * @return array
     */
    private function translate_audio(array $params): array
    {
        // This requires multipart/form-data, more complex implementation needed
        throw new \Exception('Audio translation not yet implemented - requires file upload handling');
    }

    /**
     * Text to speech.
     *
     * @param array $params
     * @return array
     */
    private function text_to_speech(array $params): array
    {
        $payload = [
            'model' => $params['model'] ?? 'tts-1',
            'input' => $params['input'],
            'voice' => $params['voice']
        ];

        $optional_params = ['response_format', 'speed'];
        foreach ($optional_params as $param) {
            if (isset($params[$param])) {
                $payload[$param] = $params[$param];
            }
        }

        return $this->make_request('POST', '/audio/speech', $payload);
    }

    /**
     * Moderate content.
     *
     * @param array $params
     * @return array
     */
    private function moderate_content(array $params): array
    {
        $payload = [
            'input' => $params['input']
        ];

        if (isset($params['model'])) {
            $payload['model'] = $params['model'];
        }

        return $this->make_request('POST', '/moderations', $payload);
    }

    /**
     * Make HTTP request to OpenAI API.
     *
     * @param string $method
     * @param string $endpoint
     * @param array $data
     * @return array
     */
    private function make_request(string $method, string $endpoint, array $data = []): array
    {
        $url = self::API_URL . $endpoint;
        
        $headers = [
            'Authorization' => 'Bearer ' . $this->auth['api_key'],
            'Content-Type' => 'application/json',
            'User-Agent' => 'Ryvr/1.0'
        ];

        if (isset($this->auth['organization_id'])) {
            $headers['OpenAI-Organization'] = $this->auth['organization_id'];
        }

        $request = $this->requestFactory->createRequest($method, $url);
        
        foreach ($headers as $name => $value) {
            $request = $request->withHeader($name, $value);
        }

        if (!empty($data)) {
            $request = $request->withBody(
                $this->streamFactory->createStream(json_encode($data))
            );
        }

        try {
            $response = $this->httpClient->sendRequest($request);
            $body = $response->getBody()->getContents();
            
            if ($response->getStatusCode() >= 400) {
                throw new \Exception("OpenAI API error: {$body}");
            }
            
            return json_decode($body, true) ?: [];
            
        } catch (\Exception $e) {
            throw new \Exception("OpenAI connector error: " . $e->getMessage());
        }
    }
} 