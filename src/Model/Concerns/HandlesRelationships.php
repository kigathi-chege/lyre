<?php

namespace Lyre\Strings\Model\Concerns;

/**
 * Handles model relationships and introspection.
 * 
 * This concern provides methods for discovering and managing
 * model relationships with configurable depth.
 * 
 * @package Lyre\Strings\Model\Concerns
 */
trait HandlesRelationships
{
    /**
     * Get model relationships with configurable depth.
     *
     * @param int|null $depth
     * @return array
     */
    public static function getModelRelationships(int | null $depth = null): array
    {
        $depth = $depth ?? config('lyre.relationship_depth', 1);
        $cacheKey = 'model_relationships_' . static::getClassName();

        return cache()->rememberForever($cacheKey, function () use ($depth) {
            return static::extractModelRelationships(new static(), $depth);
        });
    }

    /**
     * Extract model relationships recursively.
     *
     * @param mixed $model
     * @param int $depth
     * @param string $prefix
     * @return array
     */
    protected static function extractModelRelationships($model, int $depth, string $prefix = ''): array
    {
        $relationships = [];
        $class = new \ReflectionClass($model);
        $methods = $class->getMethods();

        foreach ($methods as $method) {
            if ($method->class !== get_class($model)) continue;
            if (!empty($method->getParameters())) continue;

            try {
                $relation = $method->invoke($model);

                if ($relation instanceof \Illuminate\Database\Eloquent\Relations\Relation) {
                    $relationName = $prefix . $method->getName();
                    $relatedModel = $relation->getRelated();
                    $relationships[$relationName] = get_class($relatedModel);

                    // Recurse if depth allows
                    if ($depth > 1) {
                        $nested = static::extractModelRelationships($relatedModel, $depth - 1, $relationName . '.');
                        $relationships = array_merge($relationships, $nested);
                    }
                }
            } catch (\Throwable $e) {
                // Skip methods that throw on invocation
            }
        }

        return $relationships;
    }

    /**
     * Get searchable relations for the model.
     *
     * @return array
     */
    public function searcheableRelations(): array
    {
        return [];
    }
}
