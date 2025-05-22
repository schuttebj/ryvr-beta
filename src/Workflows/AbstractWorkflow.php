<?php
declare(strict_types=1);

namespace Ryvr\Workflows;

/**
 * Abstract workflow implementation.
 *
 * @since 1.0.0
 */
abstract class AbstractWorkflow implements WorkflowInterface
{
    /**
     * Workflow definition.
     *
     * @var array
     */
    protected array $definition = [];
    
    /**
     * Workflow steps.
     *
     * @var array
     */
    protected array $steps = [];
    
    /**
     * Workflow context.
     *
     * @var array
     */
    protected array $context = [];
    
    /**
     * Workflow execution result.
     *
     * @var array
     */
    protected array $result = [];
    
    /**
     * Workflow constructor.
     *
     * @param array $definition Workflow definition.
     *
     * @since 1.0.0
     */
    public function __construct(array $definition = [])
    {
        if (!empty($definition)) {
            $this->definition = $definition;
            $this->steps = $definition['steps'] ?? [];
        }
    }
    
    /**
     * Get the workflow ID.
     *
     * @return string Unique workflow identifier.
     *
     * @since 1.0.0
     */
    public function get_id(): string
    {
        return $this->definition['id'] ?? '';
    }
    
    /**
     * Get the workflow name.
     *
     * @return string Human-readable workflow name.
     *
     * @since 1.0.0
     */
    public function get_name(): string
    {
        return $this->definition['name'] ?? '';
    }
    
    /**
     * Get the workflow description.
     *
     * @return string Human-readable workflow description.
     *
     * @since 1.0.0
     */
    public function get_description(): string
    {
        return $this->definition['description'] ?? '';
    }
    
    /**
     * Get the workflow definition.
     *
     * @return array The workflow definition.
     *
     * @since 1.0.0
     */
    public function get_definition(): array
    {
        return $this->definition;
    }
    
    /**
     * Get the workflow steps.
     *
     * @return array List of step definitions.
     *
     * @since 1.0.0
     */
    public function get_steps(): array
    {
        return $this->steps;
    }
    
    /**
     * Validate the workflow.
     *
     * @return bool|array True if valid, array of errors if invalid.
     *
     * @since 1.0.0
     */
    public function validate()
    {
        $errors = [];
        
        // Check for required fields
        if (empty($this->get_id())) {
            $errors[] = __('Workflow ID is required', 'ryvr');
        }
        
        if (empty($this->get_name())) {
            $errors[] = __('Workflow name is required', 'ryvr');
        }
        
        if (empty($this->steps)) {
            $errors[] = __('Workflow must have at least one step', 'ryvr');
        }
        
        // Check that steps have valid structure
        foreach ($this->steps as $index => $step) {
            if (empty($step['id'])) {
                $errors[] = sprintf(__('Step %d must have an ID', 'ryvr'), $index + 1);
            }
            
            if (empty($step['type'])) {
                $errors[] = sprintf(__('Step %d must have a type', 'ryvr'), $index + 1);
            }
            
            if (!isset($step['connector']) && $step['type'] !== 'decision') {
                $errors[] = sprintf(__('Step %d must specify a connector', 'ryvr'), $index + 1);
            }
        }
        
        return empty($errors) ? true : $errors;
    }
    
    /**
     * Execute the workflow.
     *
     * @param array $input Input data for the workflow.
     *
     * @return array Result of the workflow execution.
     *
     * @throws \Exception If execution fails.
     *
     * @since 1.0.0
     */
    public function execute(array $input = []): array
    {
        // Validate the workflow
        $validation = $this->validate();
        if ($validation !== true) {
            throw new \Exception(
                sprintf(__('Invalid workflow: %s', 'ryvr'), implode(', ', $validation))
            );
        }
        
        // Initialize context with input data
        $this->context = $input;
        $this->result = [];
        
        // Execute steps in order
        foreach ($this->steps as $index => $step) {
            try {
                $step_result = $this->execute_step($step);
                
                // Store step result in context
                $this->context[$step['id']] = $step_result;
                
                // Store in result
                $this->result[$step['id']] = $step_result;
            } catch (\Exception $e) {
                // Handle step execution error based on error handling strategy
                $error_strategy = $step['on_error'] ?? 'abort';
                
                switch ($error_strategy) {
                    case 'continue':
                        // Continue to next step
                        $this->result[$step['id']] = ['error' => $e->getMessage()];
                        break;
                        
                    case 'retry':
                        // Implement retry logic here
                        // For now, just log the error and continue
                        $this->result[$step['id']] = ['error' => $e->getMessage()];
                        break;
                        
                    case 'abort':
                    default:
                        // Abort workflow execution
                        throw new \Exception(
                            sprintf(__('Error in step %s: %s', 'ryvr'), $step['id'], $e->getMessage()),
                            0,
                            $e
                        );
                }
            }
        }
        
        return $this->result;
    }
    
    /**
     * Execute a single workflow step.
     *
     * @param array $step Step definition.
     *
     * @return mixed Result of the step execution.
     *
     * @throws \Exception If execution fails.
     *
     * @since 1.0.0
     */
    protected function execute_step(array $step)
    {
        $step_id = $step['id'];
        $step_type = $step['type'];
        
        // TODO: Implement actual step execution logic
        // This is a placeholder implementation
        
        return [
            'status' => 'success',
            'message' => sprintf(__('Step %s executed successfully', 'ryvr'), $step_id),
        ];
    }
    
    /**
     * Render a template with context.
     *
     * @param string $template Template string.
     * @param array  $context  Context data.
     *
     * @return string Rendered template.
     *
     * @since 1.0.0
     */
    protected function render_template(string $template, array $context = []): string
    {
        // Simple template rendering implementation
        // TODO: Replace with a more robust solution
        
        $rendered = $template;
        
        // Replace variables in the format {{variable}}
        preg_match_all('/\{\{([^}]+)\}\}/', $template, $matches);
        
        if (!empty($matches[1])) {
            foreach ($matches[1] as $index => $var_path) {
                $var_path = trim($var_path);
                $value = $this->get_context_value($var_path, $context);
                
                if (is_array($value) || is_object($value)) {
                    $value = json_encode($value);
                }
                
                $rendered = str_replace($matches[0][$index], (string) $value, $rendered);
            }
        }
        
        return $rendered;
    }
    
    /**
     * Get a value from context using dot notation.
     *
     * @param string $path    Dot notation path.
     * @param array  $context Context data.
     *
     * @return mixed The value or null if not found.
     *
     * @since 1.0.0
     */
    protected function get_context_value(string $path, array $context = [])
    {
        $context = !empty($context) ? $context : $this->context;
        $parts = explode('.', $path);
        $value = $context;
        
        foreach ($parts as $part) {
            // Check if we're accessing an array element
            if (strpos($part, '[') !== false && strpos($part, ']') !== false) {
                $array_parts = explode('[', $part);
                $array_key = $array_parts[0];
                $array_index = trim($array_parts[1], ']');
                
                if (!isset($value[$array_key]) || !is_array($value[$array_key])) {
                    return null;
                }
                
                $value = $value[$array_key][$array_index] ?? null;
            } else {
                $value = $value[$part] ?? null;
            }
            
            if ($value === null) {
                return null;
            }
        }
        
        return $value;
    }
} 