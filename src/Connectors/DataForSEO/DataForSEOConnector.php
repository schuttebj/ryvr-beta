<?php
declare(strict_types=1);

namespace Ryvr\Connectors\DataForSEO;

use Ryvr\Connectors\AbstractConnector;
use Ryvr\Admin\Settings;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

/**
 * DataForSEO connector with comprehensive API support.
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
            'category' => 'seo',
            'brand_color' => '#1e40af'
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
            'login' => [
                'label' => __('API Login', 'ryvr'),
                'type' => 'text',
                'required' => true,
                'description' => __('Your DataForSEO API login (not your email). Get this from your DataForSEO dashboard under API settings.', 'ryvr'),
            ],
            'password' => [
                'label' => __('API Password', 'ryvr'),
                'type' => 'password',
                'required' => true,
                'description' => __('Your DataForSEO API password (generated token). Get this from your DataForSEO dashboard under API settings.', 'ryvr'),
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
        if (defined('WP_DEBUG') && WP_DEBUG) {
            try {
                error_log('');
                error_log('=== DataForSEO Authentication Validation Debug ===');
                error_log('Ryvr: DataForSEO validate_auth called');
                error_log('Ryvr: Raw credentials received: ' . print_r($credentials, true));
                
                // Log each credential field individually
                foreach ($credentials as $key => $value) {
                    if ($key === 'password') {
                        error_log('Ryvr: DataForSEO credential[' . $key . '] = [' . strlen($value) . ' chars] ' . (empty($value) ? 'EMPTY' : 'NOT EMPTY'));
                    } else {
                        error_log('Ryvr: DataForSEO credential[' . $key . '] = ' . $value);
                    }
                }
            } catch (\Exception $e) {
                error_log('Ryvr: DataForSEO debug logging error: ' . $e->getMessage());
            }
        }
        
        if (empty($credentials['login']) || empty($credentials['password'])) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Ryvr: DataForSEO validation failed - missing login or password');
                error_log('Ryvr: Login empty: ' . (empty($credentials['login']) ? 'YES' : 'NO'));
                error_log('Ryvr: Password empty: ' . (empty($credentials['password']) ? 'YES' : 'NO'));
            }
            return false;
        }
        
        try {
            $client = $this->getClient();
            $api_url = $this->getApiUrl($credentials);
            $validation_url = $api_url . '/appendix/user_data';
            
            if (defined('WP_DEBUG') && WP_DEBUG) {
                try {
                    error_log('');
                    error_log('--- Making API Request ---');
                    error_log('Ryvr: DataForSEO making request to: ' . $validation_url);
                    error_log('Ryvr: DataForSEO login: "' . $credentials['login'] . '"');
                    error_log('Ryvr: DataForSEO password: [' . strlen($credentials['password']) . ' chars] ' . substr($credentials['password'], 0, 3) . '...');
                    error_log('Ryvr: DataForSEO use_sandbox: ' . (!empty($credentials['use_sandbox']) ? 'true' : 'false'));
                    
                    // Log the exact Basic Auth string that will be sent
                    $auth_string = $credentials['login'] . ':' . $credentials['password'];
                    $basic_auth = base64_encode($auth_string);
                    error_log('Ryvr: DataForSEO Basic Auth string: ' . $basic_auth);
                } catch (\Exception $e) {
                    error_log('Ryvr: DataForSEO debug logging error: ' . $e->getMessage());
                }
            }
            
            // Use user_data endpoint for validation - free and provides account info
            $response = $client->request('GET', $validation_url, [
                'auth' => [
                    $credentials['login'],
                    $credentials['password'],
                ],
                'headers' => [
                    'Content-Type' => 'application/json',
                    'User-Agent' => 'Ryvr/1.0 WordPress Plugin',
                ],
                'timeout' => 30,
            ]);
            
            $status_code = $response->getStatusCode();
            $body = $response->getBody()->getContents();
            
            if (defined('WP_DEBUG') && WP_DEBUG) {
                try {
                    error_log('');
                    error_log('--- API Response ---');
                    error_log('Ryvr: DataForSEO response status: ' . $status_code);
                    error_log('Ryvr: DataForSEO response headers: ' . print_r($response->getHeaders(), true));
                    error_log('Ryvr: DataForSEO response body (first 500 chars): ' . substr($body, 0, 500));
                    
                    // Try to decode JSON to see structure
                    $decoded = json_decode($body, true);
                    if ($decoded) {
                        error_log('Ryvr: DataForSEO decoded response keys: ' . print_r(array_keys($decoded), true));
                        if (isset($decoded['status_message'])) {
                            error_log('Ryvr: DataForSEO status_message: ' . $decoded['status_message']);
                        }
                        
                        // Log useful account information if validation succeeds
                        if (isset($decoded['tasks'][0]['result'])) {
                            $result = $decoded['tasks'][0]['result'];
                            if (isset($result['login'])) {
                                error_log('Ryvr: DataForSEO API Login: ' . $result['login']);
                            }
                            if (isset($result['timezone'])) {
                                error_log('Ryvr: DataForSEO Timezone: ' . $result['timezone']);
                            }
                            if (isset($result['money']['balance'])) {
                                error_log('Ryvr: DataForSEO Account Balance: $' . $result['money']['balance']);
                            }
                            if (isset($result['backlinks_subscription_expiry_date'])) {
                                $expiry = $result['backlinks_subscription_expiry_date'];
                                error_log('Ryvr: DataForSEO Backlinks Subscription: ' . ($expiry ? $expiry : 'No active subscription'));
                            }
                        }
                    }
                } catch (\Exception $e) {
                    error_log('Ryvr: DataForSEO debug logging error: ' . $e->getMessage());
                }
            }
            
            $success = $status_code === 200;
            
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('');
                error_log('--- Final Result ---');
                error_log('Ryvr: DataForSEO validation result: ' . ($success ? 'SUCCESS' : 'FAILED'));
                error_log('=== End DataForSEO Authentication Debug ===');
                error_log('');
            }
            
            return $success;
            
        } catch (\GuzzleHttp\Exception\ClientException $e) {
            $status_code = $e->getResponse() ? $e->getResponse()->getStatusCode() : 'unknown';
            $response_body = $e->getResponse() ? $e->getResponse()->getBody()->getContents() : 'no response';
            
            if (defined('WP_DEBUG') && WP_DEBUG) {
                try {
                    error_log('');
                    error_log('--- CLIENT EXCEPTION ---');
                    error_log('Ryvr: DataForSEO ClientException - Status: ' . $status_code);
                    error_log('Ryvr: DataForSEO ClientException - Response: ' . $response_body);
                    error_log('Ryvr: DataForSEO ClientException - Message: ' . $e->getMessage());
                    
                    // Try to decode the error response
                    $decoded_error = json_decode($response_body, true);
                    if ($decoded_error) {
                        error_log('Ryvr: DataForSEO decoded error response: ' . print_r($decoded_error, true));
                    }
                    
                    // Specific guidance for 401 errors
                    if ($status_code == 401) {
                        error_log('');
                        error_log('*** DataForSEO 401 UNAUTHORIZED ERROR ***');
                        error_log('This usually means:');
                        error_log('1. You are using account login/password instead of API credentials');
                        error_log('2. Your API credentials are incorrect');
                        error_log('3. Go to https://app.dataforseo.com/api-access to get API credentials');
                        error_log('4. API login is usually NOT your email address');
                        error_log('5. API password is usually a generated token, NOT your account password');
                        error_log('***********************************************');
                        error_log('');
                    }
                    
                    error_log('=== End DataForSEO Authentication Debug ===');
                    error_log('');
                } catch (\Exception $debug_e) {
                    error_log('Ryvr: DataForSEO debug logging error: ' . $debug_e->getMessage());
                }
            }
            
            return false;
            
        } catch (\GuzzleHttp\Exception\ServerException $e) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('');
                error_log('--- SERVER EXCEPTION ---');
                error_log('Ryvr: DataForSEO ServerException: ' . $e->getMessage());
                error_log('=== End DataForSEO Authentication Debug ===');
                error_log('');
            }
            
            return false;
            
        } catch (\GuzzleHttp\Exception\ConnectException $e) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('');
                error_log('--- CONNECTION EXCEPTION ---');
                error_log('Ryvr: DataForSEO ConnectException: ' . $e->getMessage());
                error_log('=== End DataForSEO Authentication Debug ===');
                error_log('');
            }
            
            return false;
            
        } catch (\Exception $e) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                try {
                    error_log('');
                    error_log('--- GENERAL EXCEPTION ---');
                    error_log('Ryvr: DataForSEO general exception: ' . $e->getMessage());
                    error_log('Ryvr: DataForSEO exception trace: ' . $e->getTraceAsString());
                    error_log('=== End DataForSEO Authentication Debug ===');
                    error_log('');
                } catch (\Exception $debug_e) {
                    error_log('Ryvr: DataForSEO debug logging error: ' . $debug_e->getMessage());
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
            // SERP API
            'serp_google_organic' => [
                'name' => 'Google Organic SERP',
                'description' => 'Get Google organic search results',
                'category' => 'serp',
                'async' => true,
                'parameters' => [
                    'required' => ['keyword'],
                    'optional' => [
                        'location_name', 'location_code', 'location_coordinate',
                        'language_name', 'language_code', 'device', 'os',
                        'depth', 'max_crawl_pages', 'search_param', 'date_from',
                        'date_to', 'sort_by', 'tag', 'postback_url', 'pingback_url'
                    ]
                ],
                'output_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'results' => ['type' => 'array'],
                        'total_count' => ['type' => 'integer'],
                        'items_count' => ['type' => 'integer']
                    ]
                ]
            ],
            'serp_google_ads' => [
                'name' => 'Google Ads SERP',
                'description' => 'Get Google Ads search results',
                'category' => 'serp',
                'async' => true,
                'parameters' => [
                    'required' => ['keyword'],
                    'optional' => [
                        'location_name', 'location_code', 'location_coordinate',
                        'language_name', 'language_code', 'device', 'os',
                        'depth', 'max_crawl_pages', 'search_param', 'date_from',
                        'date_to', 'sort_by', 'tag', 'postback_url', 'pingback_url'
                    ]
                ]
            ],
            'serp_google_shopping' => [
                'name' => 'Google Shopping SERP',
                'description' => 'Get Google Shopping search results',
                'category' => 'serp',
                'async' => true,
                'parameters' => [
                    'required' => ['keyword'],
                    'optional' => [
                        'location_name', 'location_code', 'location_coordinate',
                        'language_name', 'language_code', 'device', 'os',
                        'depth', 'max_crawl_pages', 'search_param', 'date_from',
                        'date_to', 'sort_by', 'tag', 'postback_url', 'pingback_url'
                    ]
                ]
            ],
            
            // Keywords API
            'keywords_for_keywords' => [
                'name' => 'Keywords for Keywords',
                'description' => 'Get keyword suggestions based on seed keywords',
                'category' => 'keywords',
                'async' => true,
                'parameters' => [
                    'required' => ['keywords'],
                    'optional' => [
                        'location_name', 'location_code', 'language_name', 'language_code',
                        'search_partners', 'date_from', 'date_to', 'include_serp_info',
                        'include_clickstream_data', 'sort_by', 'filters', 'order_by',
                        'limit', 'offset', 'tag', 'postback_url', 'pingback_url'
                    ]
                ],
                'output_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'keywords' => [
                            'type' => 'array',
                            'items' => [
                                'type' => 'object',
                                'properties' => [
                                    'keyword' => ['type' => 'string'],
                                    'search_volume' => ['type' => 'integer'],
                                    'cpc' => ['type' => 'number'],
                                    'competition' => ['type' => 'number'],
                                    'competition_level' => ['type' => 'string']
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            'keywords_for_site' => [
                'name' => 'Keywords for Site',
                'description' => 'Get keywords that a website ranks for',
                'category' => 'keywords',
                'async' => true,
                'parameters' => [
                    'required' => ['target'],
                    'optional' => [
                        'location_name', 'location_code', 'language_name', 'language_code',
                        'search_partners', 'date_from', 'date_to', 'include_serp_info',
                        'include_clickstream_data', 'sort_by', 'filters', 'order_by',
                        'limit', 'offset', 'tag', 'postback_url', 'pingback_url'
                    ]
                ]
            ],
            'keyword_suggestions' => [
                'name' => 'Keyword Suggestions',
                'description' => 'Get keyword suggestions from autocomplete',
                'category' => 'keywords',
                'async' => true,
                'parameters' => [
                    'required' => ['keyword'],
                    'optional' => [
                        'location_name', 'location_code', 'language_name', 'language_code',
                        'search_partners', 'date_from', 'date_to', 'include_serp_info',
                        'include_clickstream_data', 'sort_by', 'filters', 'order_by',
                        'limit', 'offset', 'tag', 'postback_url', 'pingback_url'
                    ]
                ]
            ],
            
            // Backlinks API
            'backlinks_overview' => [
                'name' => 'Backlinks Overview',
                'description' => 'Get backlinks overview for a domain',
                'category' => 'backlinks',
                'async' => true,
                'parameters' => [
                    'required' => ['target'],
                    'optional' => [
                        'include_subdomains', 'backlinks_status_type', 'internal_list_limit',
                        'limit', 'offset', 'filters', 'order_by', 'tag', 'postback_url'
                    ]
                ]
            ],
            'backlinks_summary' => [
                'name' => 'Backlinks Summary',
                'description' => 'Get backlinks summary metrics',
                'category' => 'backlinks',
                'async' => true,
                'parameters' => [
                    'required' => ['target'],
                    'optional' => [
                        'include_subdomains', 'backlinks_status_type', 'internal_list_limit',
                        'limit', 'offset', 'filters', 'order_by', 'tag', 'postback_url'
                    ]
                ]
            ],
            'referring_domains' => [
                'name' => 'Referring Domains',
                'description' => 'Get referring domains for a target',
                'category' => 'backlinks',
                'async' => true,
                'parameters' => [
                    'required' => ['target'],
                    'optional' => [
                        'include_subdomains', 'backlinks_status_type', 'internal_list_limit',
                        'limit', 'offset', 'filters', 'order_by', 'tag', 'postback_url'
                    ]
                ]
            ],
            
            // Domain Analytics
            'domain_rank_overview' => [
                'name' => 'Domain Rank Overview',
                'description' => 'Get domain ranking metrics overview',
                'category' => 'domain_analytics',
                'async' => true,
                'parameters' => [
                    'required' => ['target'],
                    'optional' => [
                        'location_name', 'location_code', 'language_name', 'language_code',
                        'item_types', 'include_subdomains', 'load_rank_absolute',
                        'historical_serp_mode', 'tag', 'postback_url'
                    ]
                ]
            ],
            'competitors_domain' => [
                'name' => 'Competitors Domain',
                'description' => 'Get competitors for a domain',
                'category' => 'domain_analytics',
                'async' => true,
                'parameters' => [
                    'required' => ['target'],
                    'optional' => [
                        'location_name', 'location_code', 'language_name', 'language_code',
                        'item_types', 'include_subdomains', 'load_rank_absolute',
                        'historical_serp_mode', 'limit', 'offset', 'filters', 'order_by',
                        'tag', 'postback_url'
                    ]
                ]
            ],
            
            // On-Page API
            'page_screenshot' => [
                'name' => 'Page Screenshot',
                'description' => 'Get screenshot of a webpage',
                'category' => 'on_page',
                'async' => true,
                'parameters' => [
                    'required' => ['url'],
                    'optional' => [
                        'browser_preset', 'browser_screen_width', 'browser_screen_height',
                        'browser_screen_scale_factor', 'store_raw_html', 'switch_pool',
                        'tag', 'postback_url'
                    ]
                ]
            ],
            'raw_html' => [
                'name' => 'Raw HTML',
                'description' => 'Get raw HTML content of a webpage',
                'category' => 'on_page',
                'async' => true,
                'parameters' => [
                    'required' => ['url'],
                    'optional' => [
                        'custom_user_agent', 'browser_preset', 'custom_js',
                        'browser_screen_width', 'browser_screen_height',
                        'browser_screen_scale_factor', 'enable_browser_rendering',
                        'load_resources', 'enable_javascript', 'enable_images',
                        'enable_stylesheets', 'enable_flash', 'custom_cookies',
                        'switch_pool', 'return_only_status', 'tag', 'postback_url'
                    ]
                ]
            ],
            'page_insights' => [
                'name' => 'Page Insights',
                'description' => 'Get detailed page analysis and insights',
                'category' => 'on_page',
                'async' => true,
                'parameters' => [
                    'required' => ['url'],
                    'optional' => [
                        'enable_javascript', 'custom_js', 'browser_preset',
                        'browser_screen_width', 'browser_screen_height',
                        'browser_screen_scale_factor', 'enable_browser_rendering',
                        'load_resources', 'enable_images', 'enable_stylesheets',
                        'enable_flash', 'custom_cookies', 'custom_user_agent',
                        'switch_pool', 'tag', 'postback_url'
                    ]
                ]
            ],
            
            // DataForSEO Trends
            'google_trends_explore' => [
                'name' => 'Google Trends Explore',
                'description' => 'Get Google Trends data for keywords',
                'category' => 'trends',
                'async' => true,
                'parameters' => [
                    'required' => ['keywords'],
                    'optional' => [
                        'location_name', 'location_code', 'language_name', 'language_code',
                        'category_code', 'date_from', 'date_to', 'time_range',
                        'item_types', 'tag', 'postback_url'
                    ]
                ]
            ],
            
            // Business Data API
            'business_listings' => [
                'name' => 'Business Listings',
                'description' => 'Get business listings data',
                'category' => 'business_data',
                'async' => true,
                'parameters' => [
                    'required' => ['keyword'],
                    'optional' => [
                        'location_name', 'location_code', 'location_coordinate',
                        'language_name', 'language_code', 'depth', 'sort_by',
                        'tag', 'postback_url'
                    ]
                ]
            ],
            
            // Content Analysis
            'content_analysis_summary' => [
                'name' => 'Content Analysis Summary',
                'description' => 'Get content analysis summary',
                'category' => 'content_analysis',
                'async' => true,
                'parameters' => [
                    'required' => ['keyword'],
                    'optional' => [
                        'location_name', 'location_code', 'language_name', 'language_code',
                        'device', 'os', 'tag', 'postback_url'
                    ]
                ]
            ],
            
            // Task Management
            'task_ready' => [
                'name' => 'Get Task Results',
                'description' => 'Get results for completed async tasks',
                'category' => 'task_management',
                'async' => false,
                'parameters' => [
                    'required' => ['id'],
                    'optional' => []
                ]
            ],
            'tasks_fixed' => [
                'name' => 'Get Fixed Tasks',
                'description' => 'Get list of completed tasks',
                'category' => 'task_management',
                'async' => false,
                'parameters' => [
                    'required' => [],
                    'optional' => ['limit', 'offset']
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
        $this->init($auth);
        
        $actions = $this->get_actions();
        if (!isset($actions[$action_id])) {
            throw new \InvalidArgumentException("Unknown action: {$action_id}");
        }
        
        $action_config = $actions[$action_id];
        
        // Handle async vs sync operations
        if ($action_config['async']) {
            return $this->execute_async_action($action_id, $params);
        } else {
            return $this->execute_sync_action($action_id, $params);
        }
    }
    
    /**
     * Execute async action (post task).
     *
     * @param string $action_id
     * @param array $params
     * @return array
     */
    private function execute_async_action(string $action_id, array $params): array
    {
        $endpoint = $this->get_post_endpoint($action_id);
        $payload = $this->build_task_payload($action_id, $params);
        
        $response = $this->make_request('POST', $endpoint, [$payload]);
        
        // Return task ID for tracking
        return [
            'task_id' => $response['tasks'][0]['id'] ?? null,
            'status' => 'posted',
            'action' => $action_id,
            'message' => 'Task posted successfully, use task_ready to get results'
        ];
    }
    
    /**
     * Execute sync action (get results).
     *
     * @param string $action_id
     * @param array $params
     * @return array
     */
    private function execute_sync_action(string $action_id, array $params): array
    {
        switch ($action_id) {
            case 'task_ready':
                return $this->get_task_results($params['id']);
            case 'tasks_fixed':
                return $this->get_completed_tasks($params);
            default:
                throw new \InvalidArgumentException("Unknown sync action: {$action_id}");
        }
    }
    
    /**
     * Get task results.
     *
     * @param string $task_id
     * @return array
     */
    private function get_task_results(string $task_id): array
    {
        $endpoint = "/task_get/ready/{$task_id}";
        return $this->make_request('GET', $endpoint);
    }
    
    /**
     * Get completed tasks.
     *
     * @param array $params
     * @return array
     */
    private function get_completed_tasks(array $params): array
    {
        $endpoint = '/tasks_fixed';
        $query_params = [];
        
        if (isset($params['limit'])) {
            $query_params['limit'] = $params['limit'];
        }
        if (isset($params['offset'])) {
            $query_params['offset'] = $params['offset'];
        }
        
        if (!empty($query_params)) {
            $endpoint .= '?' . http_build_query($query_params);
        }
        
        return $this->make_request('GET', $endpoint);
    }
    
    /**
     * Get POST endpoint for action.
     *
     * @param string $action_id
     * @return string
     */
    private function get_post_endpoint(string $action_id): string
    {
        $endpoint_map = [
            // SERP endpoints
            'serp_google_organic' => '/serp/google/organic/task_post',
            'serp_google_ads' => '/serp/google/paid/task_post',
            'serp_google_shopping' => '/serp/google/shopping/task_post',
            
            // Keywords endpoints
            'keywords_for_keywords' => '/keywords_data/google_ads/keywords_for_keywords/task_post',
            'keywords_for_site' => '/keywords_data/google_ads/keywords_for_site/task_post',
            'keyword_suggestions' => '/keywords_data/google_ads/suggestions/task_post',
            
            // Backlinks endpoints
            'backlinks_overview' => '/backlinks/overview/task_post',
            'backlinks_summary' => '/backlinks/summary/task_post',
            'referring_domains' => '/backlinks/referring_domains/task_post',
            
            // Domain Analytics endpoints
            'domain_rank_overview' => '/domain_analytics/google/overview/task_post',
            'competitors_domain' => '/domain_analytics/google/competitors/task_post',
            
            // On-Page endpoints
            'page_screenshot' => '/on_page/screenshot/task_post',
            'raw_html' => '/on_page/raw_html/task_post',
            'page_insights' => '/on_page/page_insights/task_post',
            
            // Trends endpoints
            'google_trends_explore' => '/dataforseo_trends/google_trends/explore/task_post',
            
            // Business Data endpoints
            'business_listings' => '/business_data/google/my_business/find/task_post',
            
            // Content Analysis endpoints
            'content_analysis_summary' => '/content_analysis/summary/task_post'
        ];
        
        return $endpoint_map[$action_id] ?? throw new \InvalidArgumentException("No endpoint mapping for action: {$action_id}");
    }
    
    /**
     * Build task payload.
     *
     * @param string $action_id
     * @param array $params
     * @return array
     */
    private function build_task_payload(string $action_id, array $params): array
    {
        $payload = [];
        
        // Add all parameters from the request
        foreach ($params as $key => $value) {
            if ($value !== null && $value !== '') {
                $payload[$key] = $value;
            }
        }
        
        // Action-specific payload adjustments
        switch ($action_id) {
            case 'keywords_for_keywords':
            case 'keyword_suggestions':
                if (isset($params['keywords']) && is_string($params['keywords'])) {
                    $payload['keywords'] = explode(',', $params['keywords']);
                }
                break;
        }
        
        return $payload;
    }
    
    /**
     * Make HTTP request to DataForSEO API.
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
            'Authorization' => 'Basic ' . base64_encode($this->auth['login'] . ':' . $this->auth['password']),
            'Content-Type' => 'application/json',
            'User-Agent' => 'Ryvr/1.0'
        ];

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
                throw new \Exception("DataForSEO API error: {$body}");
            }
            
            return json_decode($body, true) ?: [];
            
        } catch (\Exception $e) {
            throw new \Exception("DataForSEO connector error: " . $e->getMessage());
        }
    }
} 