<?php
declare(strict_types=1);

namespace Ryvr\Connectors\DataForSEO;

use Ryvr\Connectors\AbstractConnector;
use Ryvr\Admin\Settings;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

/**
 * DataForSEO connector.
 *
 * @since 1.0.0
 */
class DataForSEOConnector extends AbstractConnector
{
    /**
     * DataForSEO API base URL.
     *
     * @var string
     */
    private const API_URL = 'https://api.dataforseo.com/v3';
    
    /**
     * DataForSEO Sandbox API base URL.
     *
     * @var string
     */
    private const SANDBOX_API_URL = 'https://sandbox.dataforseo.com/v3';
    
    /**
     * Get the connector ID.
     *
     * @return string Unique connector identifier.
     *
     * @since 1.0.0
     */
    public function get_id(): string
    {
        return 'dataforseo';
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
        return __('DataForSEO', 'ryvr');
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
        return __('Access SEO data including rankings, keywords, and competitive analysis.', 'ryvr');
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
            'login' => [
                'label' => __('Login', 'ryvr'),
                'type' => 'text',
                'required' => true,
                'description' => __('Your DataForSEO API login.', 'ryvr'),
            ],
            'password' => [
                'label' => __('Password', 'ryvr'),
                'type' => 'password',
                'required' => true,
                'description' => __('Your DataForSEO API password.', 'ryvr'),
            ],
            'use_sandbox' => [
                'label' => __('Use Sandbox', 'ryvr'),
                'type' => 'checkbox',
                'required' => false,
                'description' => __('Enable to use DataForSEO sandbox environment for testing.', 'ryvr'),
                'default' => false,
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
     * Get the appropriate API URL based on sandbox setting.
     *
     * @param array $credentials Authentication credentials.
     *
     * @return string The API URL to use.
     */
    private function getApiUrl(array $credentials): string
    {
        return !empty($credentials['use_sandbox']) ? self::SANDBOX_API_URL : self::API_URL;
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
        if (empty($credentials['login']) || empty($credentials['password'])) {
            return false;
        }
        
        try {
            $client = $this->getClient();
            $api_url = $this->getApiUrl($credentials);
            
            $response = $client->request('GET', $api_url . '/merchant/amazon/products', [
                'auth' => [
                    $credentials['login'],
                    $credentials['password'],
                ],
                'headers' => [
                    'Content-Type' => 'application/json',
                ],
            ]);
            
            return $response->getStatusCode() === 200;
        } catch (GuzzleException $e) {
            $this->log(
                'Failed to validate DataForSEO credentials: ' . $e->getMessage(),
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
            'keyword_research' => [
                'label' => __('Keyword Research', 'ryvr'),
                'description' => __('Research keywords and get related search queries.', 'ryvr'),
                'fields' => [
                    'keyword' => [
                        'label' => __('Keyword', 'ryvr'),
                        'type' => 'text',
                        'required' => true,
                        'description' => __('The keyword to research.', 'ryvr'),
                    ],
                    'location_code' => [
                        'label' => __('Location Code', 'ryvr'),
                        'type' => 'text',
                        'required' => false,
                        'description' => __('Location code (e.g., 2840 for United States).', 'ryvr'),
                        'default' => '2840',
                    ],
                    'language_code' => [
                        'label' => __('Language Code', 'ryvr'),
                        'type' => 'text',
                        'required' => false,
                        'description' => __('Language code (e.g., en for English).', 'ryvr'),
                        'default' => 'en',
                    ],
                ],
            ],
            'serp_analysis' => [
                'label' => __('SERP Analysis', 'ryvr'),
                'description' => __('Analyze search engine results for a specific keyword.', 'ryvr'),
                'fields' => [
                    'keyword' => [
                        'label' => __('Keyword', 'ryvr'),
                        'type' => 'text',
                        'required' => true,
                        'description' => __('The keyword to analyze.', 'ryvr'),
                    ],
                    'location_code' => [
                        'label' => __('Location Code', 'ryvr'),
                        'type' => 'text',
                        'required' => false,
                        'description' => __('Location code (e.g., 2840 for United States).', 'ryvr'),
                        'default' => '2840',
                    ],
                    'language_code' => [
                        'label' => __('Language Code', 'ryvr'),
                        'type' => 'text',
                        'required' => false,
                        'description' => __('Language code (e.g., en for English).', 'ryvr'),
                        'default' => 'en',
                    ],
                ],
            ],
            'competitor_research' => [
                'label' => __('Competitor Research', 'ryvr'),
                'description' => __('Analyze competitor domains and their keywords.', 'ryvr'),
                'fields' => [
                    'domain' => [
                        'label' => __('Domain', 'ryvr'),
                        'type' => 'text',
                        'required' => true,
                        'description' => __('The competitor domain to analyze.', 'ryvr'),
                    ],
                    'location_code' => [
                        'label' => __('Location Code', 'ryvr'),
                        'type' => 'text',
                        'required' => false,
                        'description' => __('Location code (e.g., 2840 for United States).', 'ryvr'),
                        'default' => '2840',
                    ],
                    'language_code' => [
                        'label' => __('Language Code', 'ryvr'),
                        'type' => 'text',
                        'required' => false,
                        'description' => __('Language code (e.g., en for English).', 'ryvr'),
                        'default' => 'en',
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
        // DataForSEO doesn't have any triggers
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
            case 'keyword_research':
                return $this->keyword_research($params, $auth);
                
            case 'serp_analysis':
                return $this->serp_analysis($params, $auth);
                
            case 'competitor_research':
                return $this->competitor_research($params, $auth);
                
            default:
                throw new \Exception(
                    sprintf(__('Unsupported action: %s', 'ryvr'), $action_id)
                );
        }
    }
    
    /**
     * Perform keyword research.
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
    private function keyword_research(array $params, array $auth): array
    {
        try {
            $client = new Client();
            $api_url = $this->getApiUrl($auth);
            
            $payload = [
                [
                    'keyword' => $params['keyword'] ?? '',
                    'location_code' => $params['location_code'] ?? '2840',
                    'language_code' => $params['language_code'] ?? 'en',
                ],
            ];
            
            $response = $client->request('POST', $api_url . '/keywords_data/google/search_volume/live', [
                'auth' => [
                    $auth['login'],
                    $auth['password'],
                ],
                'headers' => [
                    'Content-Type' => 'application/json',
                ],
                'json' => $payload,
            ]);
            
            $result = json_decode($response->getBody()->getContents(), true);
            
            if (!$result || !isset($result['tasks']) || !isset($result['tasks'][0]['result'])) {
                throw new \Exception(__('Invalid response from DataForSEO API', 'ryvr'));
            }
            
            return [
                'search_volume' => $result['tasks'][0]['result'][0]['search_volume'] ?? 0,
                'keyword_info' => $result['tasks'][0]['result'][0],
            ];
        } catch (GuzzleException $e) {
            $this->log(
                'Failed to perform keyword research with DataForSEO: ' . $e->getMessage(),
                [
                    'error' => $e->getMessage(),
                    'params' => $params,
                ],
                'error'
            );
            
            throw new \Exception(
                sprintf(__('Failed to perform keyword research: %s', 'ryvr'), $e->getMessage())
            );
        }
    }
    
    /**
     * Perform SERP analysis.
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
    private function serp_analysis(array $params, array $auth): array
    {
        try {
            $client = new Client();
            $api_url = $this->getApiUrl($auth);
            
            $payload = [
                [
                    'keyword' => $params['keyword'] ?? '',
                    'location_code' => $params['location_code'] ?? '2840',
                    'language_code' => $params['language_code'] ?? 'en',
                ],
            ];
            
            $response = $client->request('POST', $api_url . '/serp/google/organic/live/regular', [
                'auth' => [
                    $auth['login'],
                    $auth['password'],
                ],
                'headers' => [
                    'Content-Type' => 'application/json',
                ],
                'json' => $payload,
            ]);
            
            $result = json_decode($response->getBody()->getContents(), true);
            
            if (!$result || !isset($result['tasks']) || !isset($result['tasks'][0]['result'])) {
                throw new \Exception(__('Invalid response from DataForSEO API', 'ryvr'));
            }
            
            $organic_results = [];
            
            if (isset($result['tasks'][0]['result'][0]['items'])) {
                foreach ($result['tasks'][0]['result'][0]['items'] as $item) {
                    if ($item['type'] === 'organic') {
                        $organic_results[] = [
                            'position' => $item['rank_absolute'] ?? 0,
                            'title' => $item['title'] ?? '',
                            'url' => $item['url'] ?? '',
                            'description' => $item['description'] ?? '',
                            'domain' => $item['domain'] ?? '',
                        ];
                    }
                }
            }
            
            return [
                'keyword' => $params['keyword'] ?? '',
                'total_results_count' => $result['tasks'][0]['result'][0]['total_count'] ?? 0,
                'organic_results' => $organic_results,
            ];
        } catch (GuzzleException $e) {
            $this->log(
                'Failed to perform SERP analysis with DataForSEO: ' . $e->getMessage(),
                [
                    'error' => $e->getMessage(),
                    'params' => $params,
                ],
                'error'
            );
            
            throw new \Exception(
                sprintf(__('Failed to perform SERP analysis: %s', 'ryvr'), $e->getMessage())
            );
        }
    }
    
    /**
     * Perform competitor research.
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
    private function competitor_research(array $params, array $auth): array
    {
        try {
            $client = new Client();
            $api_url = $this->getApiUrl($auth);
            
            $payload = [
                [
                    'target' => $params['domain'] ?? '',
                    'location_code' => $params['location_code'] ?? '2840',
                    'language_code' => $params['language_code'] ?? 'en',
                    'limit' => 10,
                ],
            ];
            
            $response = $client->request('POST', $api_url . '/domain_analytics/google/organic_competitors/live', [
                'auth' => [
                    $auth['login'],
                    $auth['password'],
                ],
                'headers' => [
                    'Content-Type' => 'application/json',
                ],
                'json' => $payload,
            ]);
            
            $result = json_decode($response->getBody()->getContents(), true);
            
            if (!$result || !isset($result['tasks']) || !isset($result['tasks'][0]['result'])) {
                throw new \Exception(__('Invalid response from DataForSEO API', 'ryvr'));
            }
            
            $competitors = [];
            
            if (isset($result['tasks'][0]['result'][0]['items'])) {
                foreach ($result['tasks'][0]['result'][0]['items'] as $item) {
                    $competitors[] = [
                        'domain' => $item['domain'] ?? '',
                        'intersections' => $item['intersections'] ?? 0,
                        'common_keywords' => $item['common_keywords'] ?? 0,
                        'se_traffic' => $item['se_traffic'] ?? 0,
                        'se_keywords' => $item['se_keywords'] ?? 0,
                    ];
                }
            }
            
            return [
                'domain' => $params['domain'] ?? '',
                'competitors' => $competitors,
            ];
        } catch (GuzzleException $e) {
            $this->log(
                'Failed to perform competitor research with DataForSEO: ' . $e->getMessage(),
                [
                    'error' => $e->getMessage(),
                    'params' => $params,
                ],
                'error'
            );
            
            throw new \Exception(
                sprintf(__('Failed to perform competitor research: %s', 'ryvr'), $e->getMessage())
            );
        }
    }
} 