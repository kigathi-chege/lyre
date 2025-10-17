<?php

namespace Lyre\Strings\Controller\Concerns;

use Illuminate\Support\Str;

/**
 * Handles scoping for controllers.
 * 
 * This concern provides methods for handling scoped resources
 * and route-based scoping.
 * 
 * @package Lyre\Strings\Controller\Concerns
 */
trait HandlesScoping
{
    /**
     * Extract scope from route name.
     *
     * @return string|null
     */
    public function extractScopeFromRouteName(): ?string
    {
        $routeName = \Illuminate\Support\Facades\Route::currentRouteName();
        $segments = explode('.', $routeName);
        return $segments[0] ?? null;
    }

    /**
     * Get scoped resource.
     *
     * @param mixed $scope
     * @param string $scopeName
     * @return mixed
     */
    public function getScopedResource($scope, $scopeName)
    {
        // NOTE: This assumes that all scoped model resources are related to their path parameter names distinctly

        $classBase = Str::studly(Str::singular($scopeName));
        $scopedModelClass = null;

        foreach (config('lyre.path.model', []) as $namespace) {
            $class = $namespace . '\\' . $classBase;

            if (class_exists($class)) {
                $scopedModelClass = $class;
            }
        }

        $repositoryInterface = ltrim($scopedModelClass::getRepositoryInterfaceConfig(), '\\');
        $repository = app()->make($repositoryInterface);
        $scopedResource = $repository->find(['id' => $scope]);
        return $scopedResource;
    }

    /**
     * Get scope callback for filtering.
     *
     * @param mixed $scope
     * @return callable
     */
    private function getScopeCallback($scope): callable
    {
        $scopeName = $this->extractScopeFromRouteName();

        // NOTE: This assumes that all relationship columns are in singular form
        $scopeName = Str::singular($scopeName);

        $scopedResource = $this->getScopedResource($scope, $scopeName);
        $scopeId = $scopedResource->resource->id;
        return fn($query) => $query->where("{$scopeName}_id", $scopeId);
    }
}
