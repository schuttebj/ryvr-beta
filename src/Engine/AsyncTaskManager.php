<?php
declare(strict_types=1);

namespace Ryvr\Engine;

/**
 * Async Task Manager for handling long-running operations like DataForSEO.
 *
 * @since 1.0.0
 */
class AsyncTaskManager
{
    /**
     * Schedule an async task for checking.
     *
     * @param string $task_id External task ID (e.g., DataForSEO task ID)
     * @param string $connector_id Connector that owns this task
     * @param string $action_id Action that was executed
     * @param int $workflow_run_id Associated workflow run
     * @param int $check_interval Seconds between checks
     * @return bool Success
     */
    public function schedule_task_check(
        string $task_id,
        string $connector_id,
        string $action_id,
        int $workflow_run_id,
        int $check_interval = 30
    ): bool {
        // Store task info in database
        global $wpdb;
        
        $result = $wpdb->insert(
            $wpdb->prefix . 'ryvr_async_tasks',
            [
                'external_task_id' => $task_id,
                'connector_id' => $connector_id,
                'action_id' => $action_id,
                'workflow_run_id' => $workflow_run_id,
                'status' => 'pending',
                'check_interval' => $check_interval,
                'next_check' => current_time('mysql', true),
                'created_at' => current_time('mysql', true)
            ],
            ['%s', '%s', '%s', '%d', '%s', '%d', '%s', '%s']
        );
        
        if ($result === false) {
            return false;
        }
        
        // Schedule the first check
        $this->schedule_next_check($task_id, $check_interval);
        
        return true;
    }
    
    /**
     * Schedule the next task check using Action Scheduler.
     *
     * @param string $task_id
     * @param int $delay_seconds
     * @return void
     */
    private function schedule_next_check(string $task_id, int $delay_seconds): void
    {
        if (function_exists('as_schedule_single_action')) {
            as_schedule_single_action(
                time() + $delay_seconds,
                'ryvr_check_async_task',
                [$task_id],
                'ryvr-async-tasks'
            );
        } else {
            // Fallback to wp_cron if Action Scheduler not available
            wp_schedule_single_event(
                time() + $delay_seconds,
                'ryvr_check_async_task',
                [$task_id]
            );
        }
    }
    
    /**
     * Check if an async task is complete.
     *
     * @param string $task_id
     * @return void
     */
    public function check_task_status(string $task_id): void
    {
        global $wpdb;
        
        // Get task info
        $task = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}ryvr_async_tasks WHERE external_task_id = %s AND status = 'pending'",
                $task_id
            )
        );
        
        if (!$task) {
            return; // Task not found or already completed
        }
        
        try {
            // Load the appropriate connector
            $connector = $this->get_connector($task->connector_id);
            if (!$connector) {
                $this->mark_task_failed($task_id, 'Connector not available');
                return;
            }
            
            // Check if task is ready
            $result = $connector->execute_action('task_ready', ['id' => $task_id], []);
            
            if ($this->is_task_complete($result)) {
                // Task is complete
                $this->mark_task_complete($task_id, $result);
                $this->continue_workflow($task->workflow_run_id, $result);
            } else {
                // Task still pending, schedule next check
                $this->schedule_next_check($task_id, $task->check_interval);
                $this->update_next_check_time($task_id, $task->check_interval);
            }
            
        } catch (\Exception $e) {
            $this->mark_task_failed($task_id, $e->getMessage());
        }
    }
    
    /**
     * Get connector instance by ID.
     *
     * @param string $connector_id
     * @return mixed|null
     */
    private function get_connector(string $connector_id)
    {
        switch ($connector_id) {
            case 'dataforseo':
                if (class_exists('\Ryvr\Connectors\DataForSEO\DataForSEOConnector')) {
                    return new \Ryvr\Connectors\DataForSEO\DataForSEOConnector();
                }
                break;
            case 'openai':
                if (class_exists('\Ryvr\Connectors\OpenAI\OpenAIConnector')) {
                    return new \Ryvr\Connectors\OpenAI\OpenAIConnector();
                }
                break;
        }
        
        return null;
    }
    
    /**
     * Check if a task result indicates completion.
     *
     * @param array $result
     * @return bool
     */
    private function is_task_complete(array $result): bool
    {
        // DataForSEO typically returns tasks in result.tasks array
        if (isset($result['tasks']) && is_array($result['tasks'])) {
            foreach ($result['tasks'] as $task) {
                if (isset($task['status_code']) && $task['status_code'] === 20000) {
                    return true; // Task completed successfully
                }
            }
        }
        
        return false;
    }
    
    /**
     * Mark task as complete.
     *
     * @param string $task_id
     * @param array $result
     * @return void
     */
    private function mark_task_complete(string $task_id, array $result): void
    {
        global $wpdb;
        
        $wpdb->update(
            $wpdb->prefix . 'ryvr_async_tasks',
            [
                'status' => 'completed',
                'result_data' => json_encode($result),
                'completed_at' => current_time('mysql', true)
            ],
            ['external_task_id' => $task_id],
            ['%s', '%s', '%s'],
            ['%s']
        );
    }
    
    /**
     * Mark task as failed.
     *
     * @param string $task_id
     * @param string $error_message
     * @return void
     */
    private function mark_task_failed(string $task_id, string $error_message): void
    {
        global $wpdb;
        
        $wpdb->update(
            $wpdb->prefix . 'ryvr_async_tasks',
            [
                'status' => 'failed',
                'error_message' => $error_message,
                'completed_at' => current_time('mysql', true)
            ],
            ['external_task_id' => $task_id],
            ['%s', '%s', '%s'],
            ['%s']
        );
    }
    
    /**
     * Update next check time.
     *
     * @param string $task_id
     * @param int $interval_seconds
     * @return void
     */
    private function update_next_check_time(string $task_id, int $interval_seconds): void
    {
        global $wpdb;
        
        $next_check = date('Y-m-d H:i:s', time() + $interval_seconds);
        
        $wpdb->update(
            $wpdb->prefix . 'ryvr_async_tasks',
            ['next_check' => $next_check],
            ['external_task_id' => $task_id],
            ['%s'],
            ['%s']
        );
    }
    
    /**
     * Continue workflow execution after async task completion.
     *
     * @param int $workflow_run_id
     * @param array $task_result
     * @return void
     */
    private function continue_workflow(int $workflow_run_id, array $task_result): void
    {
        // Load workflow runner
        if (class_exists('\Ryvr\Engine\Runner')) {
            $runner = new \Ryvr\Engine\Runner();
            $runner->continue_after_async_task($workflow_run_id, $task_result);
        }
    }
    
    /**
     * Get pending async tasks that need checking.
     *
     * @return array
     */
    public function get_pending_tasks(): array
    {
        global $wpdb;
        
        return $wpdb->get_results(
            "SELECT * FROM {$wpdb->prefix}ryvr_async_tasks 
             WHERE status = 'pending' 
             AND next_check <= NOW() 
             ORDER BY created_at ASC"
        );
    }
    
    /**
     * Clean up old completed/failed tasks.
     *
     * @param int $days_old Days to keep completed tasks
     * @return void
     */
    public function cleanup_old_tasks(int $days_old = 7): void
    {
        global $wpdb;
        
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$wpdb->prefix}ryvr_async_tasks 
                 WHERE status IN ('completed', 'failed') 
                 AND completed_at < DATE_SUB(NOW(), INTERVAL %d DAY)",
                $days_old
            )
        );
    }
    
    /**
     * Register hooks and actions.
     *
     * @return void
     */
    public function register(): void
    {
        // Register the async task check action
        add_action('ryvr_check_async_task', [$this, 'check_task_status']);
        
        // Schedule cleanup job
        if (!wp_next_scheduled('ryvr_cleanup_async_tasks')) {
            wp_schedule_event(time(), 'daily', 'ryvr_cleanup_async_tasks');
        }
        add_action('ryvr_cleanup_async_tasks', [$this, 'cleanup_old_tasks']);
    }
}

// Auto-register if class is loaded
if (class_exists('\Ryvr\Engine\AsyncTaskManager')) {
    $async_manager = new \Ryvr\Engine\AsyncTaskManager();
    $async_manager->register();
} 