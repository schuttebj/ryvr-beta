<?php
declare(strict_types=1);

namespace Ryvr\Workflows;

/**
 * Interface for workflows.
 *
 * @since 1.0.0
 */
interface WorkflowInterface
{
    /**
     * Get the workflow ID.
     *
     * @return string Unique workflow identifier.
     *
     * @since 1.0.0
     */
    public function get_id(): string;
    
    /**
     * Get the workflow name.
     *
     * @return string Human-readable workflow name.
     *
     * @since 1.0.0
     */
    public function get_name(): string;
    
    /**
     * Get the workflow description.
     *
     * @return string Human-readable workflow description.
     *
     * @since 1.0.0
     */
    public function get_description(): string;
    
    /**
     * Get the workflow definition.
     *
     * @return array The workflow definition.
     *
     * @since 1.0.0
     */
    public function get_definition(): array;
    
    /**
     * Get the workflow steps.
     *
     * @return array List of step definitions.
     *
     * @since 1.0.0
     */
    public function get_steps(): array;
    
    /**
     * Validate the workflow.
     *
     * @return bool|array True if valid, array of errors if invalid.
     *
     * @since 1.0.0
     */
    public function validate();
    
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
    public function execute(array $input = []): array;
} 