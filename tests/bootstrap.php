<?php
/**
 * PHPUnit bootstrap file
 */

// Define WordPress constants needed for testing
if (!defined('ABSPATH')) {
    define('ABSPATH', __DIR__ . '/stubs/wordpress/');
}

if (!defined('RYVR_PLUGIN_DIR')) {
    define('RYVR_PLUGIN_DIR', dirname(__DIR__) . '/');
}

if (!defined('RYVR_PLUGIN_URL')) {
    define('RYVR_PLUGIN_URL', 'https://example.com/wp-content/plugins/ryvr/');
}

// Create mock global $wpdb object
global $wpdb;
$wpdb = new class {
    public $prefix = 'wp_';
    
    public function prepare($query, ...$args) {
        return $query;
    }
    
    public function get_var($query) {
        return null;
    }
    
    public function get_results($query, $output = OBJECT) {
        return [];
    }
    
    public function insert($table, $data, $format = null) {
        return 1;
    }
    
    public function update($table, $data, $where, $format = null, $where_format = null) {
        return 1;
    }
    
    public function delete($table, $where, $where_format = null) {
        return 1;
    }
};

// Mock WordPress functions
if (!function_exists('__')) {
    function __($text, $domain = 'default') {
        return $text;
    }
}

if (!function_exists('esc_html__')) {
    function esc_html__($text, $domain = 'default') {
        return $text;
    }
}

if (!function_exists('esc_attr')) {
    function esc_attr($text) {
        return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('esc_html')) {
    function esc_html($text) {
        return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('esc_url')) {
    function esc_url($url) {
        return $url;
    }
}

if (!function_exists('wp_hash_password')) {
    function wp_hash_password($password) {
        return password_hash($password, PASSWORD_DEFAULT);
    }
}

if (!function_exists('add_action')) {
    function add_action($hook, $callback, $priority = 10, $accepted_args = 1) {
        return true;
    }
}

if (!function_exists('do_action')) {
    function do_action($hook, ...$args) {
        return true;
    }
}

if (!function_exists('wp_send_json_success')) {
    function wp_send_json_success($data = null) {
        return true;
    }
}

if (!function_exists('wp_send_json_error')) {
    function wp_send_json_error($data = null) {
        return true;
    }
}

if (!function_exists('wp_verify_nonce')) {
    function wp_verify_nonce($nonce, $action = -1) {
        return true;
    }
}

if (!function_exists('sanitize_text_field')) {
    function sanitize_text_field($str) {
        return $str;
    }
}

if (!function_exists('current_user_can')) {
    function current_user_can($capability) {
        return true;
    }
}

if (!function_exists('get_current_user_id')) {
    function get_current_user_id() {
        return 1;
    }
}

if (!function_exists('get_option')) {
    function get_option($option, $default = false) {
        return $default;
    }
}

if (!function_exists('update_option')) {
    function update_option($option, $value, $autoload = null) {
        return true;
    }
}

// Require Composer autoloader
require_once dirname(__DIR__) . '/vendor/autoload.php'; 