<?php

namespace Lyre\Strings\Model\Concerns;

/**
 * Handles utility methods for models.
 * 
 * This concern provides utility methods for model introspection
 * and common operations.
 * 
 * @package Lyre\Strings\Model\Concerns
 */
trait HandlesUtilities
{
    /**
     * Get fillable attributes.
     *
     * @return array
     */
    public function getFillableAttributes(): array
    {
        return $this->fillable;
    }

    /**
     * Get class name without namespace.
     *
     * @return string
     */
    public static function getClassName(): string
    {
        $className = static::class;
        $classNameParts = explode('\\', $className);
        return end($classNameParts);
    }

    /**
     * Get relative namespace.
     *
     * @return string
     */
    public static function getRelativeNamespace(): string
    {
        $reflection = new \ReflectionClass(static::class);
        $namespace = $reflection->getNamespaceName();

        $namespacePrefixes = config('lyre.path.model', []);
        $relativeNamespace = '';
        foreach ($namespacePrefixes as $prefix) {
            if (!str_contains($namespace, $prefix)) {
                continue;
            }
            $relativeNamespace = trim(str_replace($prefix, '', $namespace), '\\');
        }

        return $relativeNamespace;
    }

    /**
     * Scope for getting total count.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return int
     */
    public function scopeTotal($query): int
    {
        return $query->count();
    }

    /**
     * Resolve repository instance.
     *
     * @return mixed
     */
    public static function resolveRepository()
    {
        return app(ltrim(static::getRepositoryInterfaceConfig(), '\\'));
    }
}
