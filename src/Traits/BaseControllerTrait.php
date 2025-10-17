<?php

namespace Lyre\Strings\Traits;

/**
 * Base controller trait for the Strings package.
 * 
 * This trait provides common functionality for controllers
 * in the Strings package.
 * 
 * @package Lyre\Strings\Traits
 */
trait BaseControllerTrait
{
    /**
     * Authorize a resource action.
     *
     * @param array $modelConfig
     * @param string $modelName
     * @param array $options
     * @return void
     */
    protected function authorizeResource(array $modelConfig, string $modelName, array $options = []): void
    {
        // Implementation for resource authorization
        // This would typically integrate with Laravel's authorization system
    }
}
