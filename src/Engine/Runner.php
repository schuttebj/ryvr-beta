<?php
declare(strict_types=1);

namespace Ryvr\Engine;

use Ryvr\Workflows\Manager as WorkflowManager;

/**
 * Workflow execution runner.
 * Handles scheduled and triggered workflow execution.
 *
 * @since 1.0.0
 */
class Runner
{
    /**
     * Workflow manager instance.
     *
     * @var \Ryvr\Workflows\Manager
     */
    protected $workflow_manager;
    
    /**
     * Runner constructor.
     *
     * @param \Ryvr\Workflows\Manager|null $workflow_manager Workflow manager instance.
     *
     * @since 1.0.0
     */
    public function __construct(\Ryvr\Workflows\Manager $workflow_manager = null)
    {
        $this->workflow_manager = $workflow_manager ?? new WorkflowManager();
    }
    
    /**
     * Run a workflow.
     *
     * This method is called by the WordPress cron system or directly when 
     * a workflow is triggered manually.
     *
     * @param string $workflow_id  Workflow ID.
     * @param array  $input        Input data for the workflow.
     *
     * @return array Workflow execution result.
     *
     * @throws \Exception If execution fails.
     *
     * @since 1.0.0
     */
    public function run(string $workflow_id, array $input = []): array
    {
        // Log workflow execution start
        $this->log_execution_start($workflow_id, $input);
        
        try {
            // Execute the workflow
            $result = $this->workflow_manager->execute_workflow($workflow_id, $input);
            
            // Log workflow execution success
            $this->log_execution_success($workflow_id, $result);
            
            return $result;
        } catch (\Exception $e) {
            // Log workflow execution failure
            $this->log_execution_failure($workflow_id, $e);
            
            throw $e;
        }
    }
    
    /**
     * Schedule a workflow to run.
     *
     * @param string $workflow_id   Workflow ID.
     * @param array  $input         Input data for the workflow.
     * @param int    $timestamp     Timestamp to run the workflow.
     * @param string $unique_id     Optional unique ID for the event.
     *
     * @return bool Whether the workflow was scheduled successfully.
     *
     * @since 1.0.0
     */
    public function schedule(string $workflow_id, array $input = [], int $timestamp = 0, string $unique_id = ''): bool
    {
        // If no timestamp is provided, run immediately
        if ($timestamp <= 0) {
            $timestamp = time();
        }
        
        // Check if Action Scheduler is available
        if (function_exists('as_schedule_single_action')) {
            // Generate a unique ID if none provided
            if (empty($unique_id)) {
                $unique_id = uniqid('ryvr_workflow_', true);
            }
            
            // Schedule the workflow execution
            return as_schedule_single_action(
                $timestamp,
                'ryvr_run_workflow',
                [
                    'workflow_id' => $workflow_id,
                    'input' => $input,
                ],
                'ryvr',
                $unique_id
            );
        }
        
        // Fallback to WordPress cron if Action Scheduler is not available
        return wp_schedule_single_event(
            $timestamp,
            'ryvr_run_workflow',
            [
                $workflow_id,
                $input,
            ]
        );
    }
    
    /**
     * Log workflow execution start.
     *
     * @param string $workflow_id   Workflow ID.
     * @param array  $input         Input data for the workflow.
     *
     * @return void
     *
     * @since 1.0.0
     */
    protected function log_execution_start(string $workflow_id, array $input): void
    {
        // Simple logging for now
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log(sprintf(
                'Ryvr: Starting workflow execution: %s with input: %s',
                $workflow_id,
                json_encode($input)
            ));
        }
        
        // TODO: Implement proper logging to database
    }
    
    /**
     * Log workflow execution success.
     *
     * @param string $workflow_id   Workflow ID.
     * @param array  $result        Workflow execution result.
     *
     * @return void
     *
     * @since 1.0.0
     */
    protected function log_execution_success(string $workflow_id, array $result): void
    {
        // Simple logging for now
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log(sprintf(
                'Ryvr: Workflow execution completed successfully: %s with result: %s',
                $workflow_id,
                json_encode($result)
            ));
        }
        
        // TODO: Implement proper logging to database
    }
    
    /**
     * Log workflow execution failure.
     *
     * @param string     $workflow_id   Workflow ID.
     * @param \Exception $exception     Exception that occurred.
     *
     * @return void
     *
     * @since 1.0.0
     */
    protected function log_execution_failure(string $workflow_id, \Exception $exception): void
    {
        // Simple logging for now
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log(sprintf(
                'Ryvr: Workflow execution failed: %s with error: %s',
                $workflow_id,
                $exception->getMessage()
            ));
        }
        
        // TODO: Implement proper logging to database
    }
} 