<?php

namespace Lyre\Strings\Resource\Concerns;

/**
 * Handles relationship loading and transformation for resources.
 * 
 * This concern provides methods for loading and transforming
 * related models into proper resource format.
 * 
 * @package Lyre\Strings\Resource\Concerns
 */
trait HandlesRelations
{
    /**
     * Load and transform relations for the resource.
     *
     * @param mixed $resource
     * @param array $baseData
     * @param mixed $modelInstance
     * @return array
     */
    public function loadRelations($resource, array $baseData, $modelInstance): array
    {
        $allowedRelations = $resource::loadResources($modelInstance);
        if (!empty($allowedRelations)) {
            foreach ($allowedRelations as $relation => $resource) {
                if ($this->relationLoaded($relation)) {
                    $relationObj = $this->resource->$relation();
                    $relationData = $this->whenLoaded($relation);
                    if ($relationData !== null) {
                        if ($relationObj instanceof \Illuminate\Database\Eloquent\Relations\Relation) {
                            if (
                                $relationObj instanceof \Illuminate\Database\Eloquent\Relations\HasMany  ||
                                $relationObj instanceof \Illuminate\Database\Eloquent\Relations\BelongsToMany  ||
                                $relationObj instanceof \Illuminate\Database\Eloquent\Relations\HasManyThrough  ||
                                $relationObj instanceof \Illuminate\Database\Eloquent\Relations\MorphMany  ||
                                $relationObj instanceof \Illuminate\Database\Eloquent\Relations\MorphToMany
                            ) {
                                $baseData[$relation] = $resource::collection($relationData);
                            } else {
                                if ($relationObj instanceof \Illuminate\Database\Eloquent\Relations\MorphTo) {
                                    if (method_exists($relationData, 'getResourceConfig')) {
                                        $resource = $relationData::getResourceConfig();
                                    }
                                }
                                $baseData[$relation] = new $resource($relationData);
                            }
                        }
                    }
                }
            }
        }
        return $baseData;
    }

    /**
     * Handle pivot relations for the resource.
     *
     * @param mixed $resource
     * @param array $baseData
     * @return array
     */
    public function pivotRelations($resource, array $baseData): array
    {
        $allowedRelations = $resource::pivotResources();
        if (!empty($allowedRelations)) {
            foreach ($allowedRelations as $relation => $resource) {
                $baseData[$relation] = $resource::collection($this->whenPivotLoaded($relation, fn() => $this->pivot));
            }
        }
        return $baseData;
    }

    /**
     * Get loadable resources for a model.
     *
     * @param mixed $resource
     * @return array
     */
    public static function loadResources($resource = null): array
    {
        if (!$resource || (class_exists('\App\Models\User') && $resource instanceof \App\Models\User)) {
            return [];
        }

        return collect($resource->getModelRelationships())->map(function ($value, $key) {
            return $value::generateConfig()['resource'];
        })->filter()->toArray();
    }

    /**
     * Get pivot resources for a model.
     *
     * @return array
     */
    public static function pivotResources(): array
    {
        return [];
    }
}
