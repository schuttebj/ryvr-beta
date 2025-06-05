<?php
declare(strict_types=1);

namespace Ryvr\Engine;

/**
 * Data processor for workflows.
 * Handles data validation, transformation, and field mapping between steps.
 *
 * @since 1.0.0
 */
class DataProcessor
{
    /**
     * Validation rules cache.
     *
     * @var array
     */
    private $validation_rules = [];
    
    /**
     * Transformation functions cache.
     *
     * @var array
     */
    private $transformation_functions = [];
    
    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->initialize_built_in_validators();
        $this->initialize_built_in_transformers();
    }
    
    /**
     * Process data between workflow steps.
     *
     * @param array $data Source data from previous step.
     * @param array $mapping Field mapping configuration.
     * @param array $validation_rules Validation rules to apply.
     * @param array $transformations Data transformations to apply.
     *
     * @return array Processed data ready for next step.
     *
     * @throws \Exception If validation or transformation fails.
     */
    public function process_step_data(
        array $data,
        array $mapping = [],
        array $validation_rules = [],
        array $transformations = []
    ): array {
        // Apply field mapping first
        $mapped_data = $this->apply_field_mapping($data, $mapping);
        
        // Apply transformations
        $transformed_data = $this->apply_transformations($mapped_data, $transformations);
        
        // Validate the final data
        $validation_result = $this->validate_data($transformed_data, $validation_rules);
        
        if (!$validation_result['valid']) {
            throw new \Exception(
                sprintf(
                    __('Data validation failed: %s', 'ryvr'),
                    implode(', ', $validation_result['errors'])
                )
            );
        }
        
        return $transformed_data;
    }
    
    /**
     * Apply field mapping between steps.
     *
     * @param array $source_data Source data.
     * @param array $mapping Field mapping configuration.
     *
     * @return array Mapped data.
     */
    public function apply_field_mapping(array $source_data, array $mapping): array
    {
        if (empty($mapping)) {
            return $source_data;
        }
        
        $mapped_data = [];
        
        foreach ($mapping as $map) {
            $source_field = $map['source'] ?? $map['from'] ?? '';
            $target_field = $map['target'] ?? $map['to'] ?? '';
            
            if (empty($source_field) || empty($target_field)) {
                continue;
            }
            
            // Support nested field access using dot notation
            $source_value = $this->get_nested_value($source_data, $source_field);
            
            if ($source_value !== null) {
                $this->set_nested_value($mapped_data, $target_field, $source_value);
            }
        }
        
        return $mapped_data;
    }
    
    /**
     * Apply data transformations.
     *
     * @param array $data Data to transform.
     * @param array $transformations Transformation rules.
     *
     * @return array Transformed data.
     *
     * @throws \Exception If transformation fails.
     */
    public function apply_transformations(array $data, array $transformations): array
    {
        if (empty($transformations)) {
            return $data;
        }
        
        $transformed_data = $data;
        
        foreach ($transformations as $transformation) {
            $field = $transformation['field'] ?? '';
            $function = $transformation['function'] ?? '';
            $params = $transformation['params'] ?? [];
            
            if (empty($field) || empty($function)) {
                continue;
            }
            
            $current_value = $this->get_nested_value($transformed_data, $field);
            
            if ($current_value !== null) {
                $new_value = $this->apply_transformation_function(
                    $function,
                    $current_value,
                    $params
                );
                
                $this->set_nested_value($transformed_data, $field, $new_value);
            }
        }
        
        return $transformed_data;
    }
    
    /**
     * Validate data against rules.
     *
     * @param array $data Data to validate.
     * @param array $rules Validation rules.
     *
     * @return array Validation result with 'valid' boolean and 'errors' array.
     */
    public function validate_data(array $data, array $rules): array
    {
        $errors = [];
        
        foreach ($rules as $field => $field_rules) {
            $value = $this->get_nested_value($data, $field);
            
            foreach ($field_rules as $rule) {
                $rule_name = $rule['rule'] ?? '';
                $rule_params = $rule['params'] ?? [];
                $custom_message = $rule['message'] ?? '';
                
                if (empty($rule_name)) {
                    continue;
                }
                
                $validation_result = $this->apply_validation_rule(
                    $rule_name,
                    $value,
                    $rule_params
                );
                
                if (!$validation_result['valid']) {
                    $error_message = $custom_message ?: $validation_result['message'];
                    $errors[] = sprintf(
                        __('Field "%s": %s', 'ryvr'),
                        $field,
                        $error_message
                    );
                }
            }
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
    
    /**
     * Initialize built-in validation rules.
     */
    private function initialize_built_in_validators(): void
    {
        $this->validation_rules = [
            'required' => function($value, $params) {
                $valid = $value !== null && $value !== '' && $value !== [];
                return [
                    'valid' => $valid,
                    'message' => __('This field is required', 'ryvr')
                ];
            },
            
            'string' => function($value, $params) {
                $valid = is_string($value);
                return [
                    'valid' => $valid,
                    'message' => __('Must be a string', 'ryvr')
                ];
            },
            
            'integer' => function($value, $params) {
                $valid = is_int($value) || (is_string($value) && ctype_digit($value));
                return [
                    'valid' => $valid,
                    'message' => __('Must be an integer', 'ryvr')
                ];
            },
            
            'number' => function($value, $params) {
                $valid = is_numeric($value);
                return [
                    'valid' => $valid,
                    'message' => __('Must be a number', 'ryvr')
                ];
            },
            
            'email' => function($value, $params) {
                $valid = filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
                return [
                    'valid' => $valid,
                    'message' => __('Must be a valid email address', 'ryvr')
                ];
            },
            
            'url' => function($value, $params) {
                $valid = filter_var($value, FILTER_VALIDATE_URL) !== false;
                return [
                    'valid' => $valid,
                    'message' => __('Must be a valid URL', 'ryvr')
                ];
            },
            
            'min_length' => function($value, $params) {
                $min = $params['length'] ?? 0;
                $valid = strlen((string)$value) >= $min;
                return [
                    'valid' => $valid,
                    'message' => sprintf(__('Must be at least %d characters', 'ryvr'), $min)
                ];
            },
            
            'max_length' => function($value, $params) {
                $max = $params['length'] ?? 0;
                $valid = strlen((string)$value) <= $max;
                return [
                    'valid' => $valid,
                    'message' => sprintf(__('Must be no more than %d characters', 'ryvr'), $max)
                ];
            },
            
            'min_value' => function($value, $params) {
                $min = $params['value'] ?? 0;
                $valid = is_numeric($value) && (float)$value >= $min;
                return [
                    'valid' => $valid,
                    'message' => sprintf(__('Must be at least %s', 'ryvr'), $min)
                ];
            },
            
            'max_value' => function($value, $params) {
                $max = $params['value'] ?? 0;
                $valid = is_numeric($value) && (float)$value <= $max;
                return [
                    'valid' => $valid,
                    'message' => sprintf(__('Must be no more than %s', 'ryvr'), $max)
                ];
            },
            
            'in' => function($value, $params) {
                $allowed = $params['values'] ?? [];
                $valid = in_array($value, $allowed, true);
                return [
                    'valid' => $valid,
                    'message' => sprintf(
                        __('Must be one of: %s', 'ryvr'),
                        implode(', ', $allowed)
                    )
                ];
            },
            
            'not_in' => function($value, $params) {
                $forbidden = $params['values'] ?? [];
                $valid = !in_array($value, $forbidden, true);
                return [
                    'valid' => $valid,
                    'message' => sprintf(
                        __('Must not be one of: %s', 'ryvr'),
                        implode(', ', $forbidden)
                    )
                ];
            },
            
            'regex' => function($value, $params) {
                $pattern = $params['pattern'] ?? '';
                $valid = !empty($pattern) && preg_match($pattern, (string)$value);
                return [
                    'valid' => $valid,
                    'message' => $params['message'] ?? __('Invalid format', 'ryvr')
                ];
            },
            
            'array' => function($value, $params) {
                $valid = is_array($value);
                return [
                    'valid' => $valid,
                    'message' => __('Must be an array', 'ryvr')
                ];
            },
            
            'array_min_length' => function($value, $params) {
                $min = $params['length'] ?? 0;
                $valid = is_array($value) && count($value) >= $min;
                return [
                    'valid' => $valid,
                    'message' => sprintf(__('Array must have at least %d items', 'ryvr'), $min)
                ];
            },
            
            'array_max_length' => function($value, $params) {
                $max = $params['length'] ?? 0;
                $valid = is_array($value) && count($value) <= $max;
                return [
                    'valid' => $valid,
                    'message' => sprintf(__('Array must have no more than %d items', 'ryvr'), $max)
                ];
            }
        ];
    }
    
    /**
     * Initialize built-in transformation functions.
     */
    private function initialize_built_in_transformers(): void
    {
        $this->transformation_functions = [
            'uppercase' => function($value, $params) {
                return strtoupper((string)$value);
            },
            
            'lowercase' => function($value, $params) {
                return strtolower((string)$value);
            },
            
            'trim' => function($value, $params) {
                $chars = $params['chars'] ?? " \t\n\r\0\x0B";
                return trim((string)$value, $chars);
            },
            
            'truncate' => function($value, $params) {
                $length = $params['length'] ?? 100;
                $suffix = $params['suffix'] ?? '...';
                $str = (string)$value;
                
                if (strlen($str) <= $length) {
                    return $str;
                }
                
                return substr($str, 0, $length - strlen($suffix)) . $suffix;
            },
            
            'replace' => function($value, $params) {
                $search = $params['search'] ?? '';
                $replace = $params['replace'] ?? '';
                return str_replace($search, $replace, (string)$value);
            },
            
            'regex_replace' => function($value, $params) {
                $pattern = $params['pattern'] ?? '';
                $replacement = $params['replacement'] ?? '';
                return preg_replace($pattern, $replacement, (string)$value);
            },
            
            'format_date' => function($value, $params) {
                $format = $params['format'] ?? 'Y-m-d H:i:s';
                $input_format = $params['input_format'] ?? null;
                
                if ($input_format) {
                    $date = \DateTime::createFromFormat($input_format, (string)$value);
                } else {
                    $date = new \DateTime((string)$value);
                }
                
                return $date ? $date->format($format) : $value;
            },
            
            'number_format' => function($value, $params) {
                $decimals = $params['decimals'] ?? 2;
                $decimal_separator = $params['decimal_separator'] ?? '.';
                $thousands_separator = $params['thousands_separator'] ?? ',';
                
                return number_format((float)$value, $decimals, $decimal_separator, $thousands_separator);
            },
            
            'json_encode' => function($value, $params) {
                $flags = $params['flags'] ?? JSON_UNESCAPED_UNICODE;
                return json_encode($value, $flags);
            },
            
            'json_decode' => function($value, $params) {
                $associative = $params['associative'] ?? true;
                return json_decode((string)$value, $associative);
            },
            
            'array_slice' => function($value, $params) {
                if (!is_array($value)) {
                    return $value;
                }
                
                $offset = $params['offset'] ?? 0;
                $length = $params['length'] ?? null;
                
                return array_slice($value, $offset, $length);
            },
            
            'array_filter' => function($value, $params) {
                if (!is_array($value)) {
                    return $value;
                }
                
                $callback = $params['callback'] ?? null;
                return $callback ? array_filter($value, $callback) : array_filter($value);
            },
            
            'array_map' => function($value, $params) {
                if (!is_array($value)) {
                    return $value;
                }
                
                $field = $params['field'] ?? '';
                if (empty($field)) {
                    return $value;
                }
                
                return array_map(function($item) use ($field) {
                    return is_array($item) && isset($item[$field]) ? $item[$field] : null;
                }, $value);
            },
            
            'concatenate' => function($value, $params) {
                $strings = $params['strings'] ?? [];
                $separator = $params['separator'] ?? '';
                
                $parts = array_merge([(string)$value], $strings);
                return implode($separator, $parts);
            },
            
            'default_value' => function($value, $params) {
                $default = $params['default'] ?? '';
                return ($value === null || $value === '') ? $default : $value;
            }
        ];
    }
    
    /**
     * Get nested value from array using dot notation.
     */
    private function get_nested_value(array $data, string $path)
    {
        $keys = explode('.', $path);
        $current = $data;
        
        foreach ($keys as $key) {
            if (!is_array($current) || !array_key_exists($key, $current)) {
                return null;
            }
            $current = $current[$key];
        }
        
        return $current;
    }
    
    /**
     * Set nested value in array using dot notation.
     */
    private function set_nested_value(array &$data, string $path, $value): void
    {
        $keys = explode('.', $path);
        $current = &$data;
        
        foreach ($keys as $i => $key) {
            if ($i === count($keys) - 1) {
                $current[$key] = $value;
            } else {
                if (!isset($current[$key]) || !is_array($current[$key])) {
                    $current[$key] = [];
                }
                $current = &$current[$key];
            }
        }
    }
    
    /**
     * Apply a transformation function.
     */
    private function apply_transformation_function(string $function, $value, array $params = [])
    {
        if (!isset($this->transformation_functions[$function])) {
            throw new \Exception(
                sprintf(__('Unknown transformation function: %s', 'ryvr'), $function)
            );
        }
        
        return call_user_func($this->transformation_functions[$function], $value, $params);
    }
    
    /**
     * Apply a validation rule.
     */
    private function apply_validation_rule(string $rule, $value, array $params = []): array
    {
        if (!isset($this->validation_rules[$rule])) {
            return [
                'valid' => false,
                'message' => sprintf(__('Unknown validation rule: %s', 'ryvr'), $rule)
            ];
        }
        
        return call_user_func($this->validation_rules[$rule], $value, $params);
    }
    
    /**
     * Register a custom validation rule.
     *
     * @param string $name Rule name.
     * @param callable $callback Rule callback function.
     */
    public function register_validation_rule(string $name, callable $callback): void
    {
        $this->validation_rules[$name] = $callback;
    }
    
    /**
     * Register a custom transformation function.
     *
     * @param string $name Function name.
     * @param callable $callback Function callback.
     */
    public function register_transformation_function(string $name, callable $callback): void
    {
        $this->transformation_functions[$name] = $callback;
    }
    
    /**
     * Get available validation rules.
     *
     * @return array List of available validation rule names.
     */
    public function get_available_validation_rules(): array
    {
        return array_keys($this->validation_rules);
    }
    
    /**
     * Get available transformation functions.
     *
     * @return array List of available transformation function names.
     */
    public function get_available_transformation_functions(): array
    {
        return array_keys($this->transformation_functions);
    }
} 