<?php

namespace Lyre\Traits;

trait BaseModelTrait
{
    use CanIncludeColumns;

    const ID_COLUMN = 'id';
    const NAME_COLUMN = 'name';
    const STATUS_CONFIG = 'constant.status';
    const ORDER_COLUMN = 'created_at';
    const ORDER_DIRECTION = 'desc';

    protected $customColumns = [];
    protected static $globalCustomColumns = [];

    public function getFillableAttributes()
    {
        return $this->fillable;
    }

    public static function getClassName()
    {
        $className = static::class;
        $classNameParts = explode('\\', $className);
        return end($classNameParts);
    }

    public static function getRelativeNamespace()
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

    public static function generateConfig()
    {
        $config = [];
        $config['model'] = static::getModelNameConfig();
        $config['resource'] = static::getResourceConfig();
        $config['repository'] = static::getRepositoryConfig();
        $config['repository-interface'] = static::getRepositoryInterfaceConfig();
        $config['store-request'] = static::getStoreRequestConfig();
        $config['update-request'] = static::getUpdateRequestConfig();
        $config['order-column'] = static::ORDER_COLUMN;
        $config['order-direction'] = static::ORDER_DIRECTION;
        $config['status'] = static::STATUS_CONFIG;
        $config['table'] = (new static())->getTable();
        $config['name'] = static::NAME_COLUMN;
        $config['id'] = static::ID_COLUMN;

        return $config;
    }

    public static function getModelNameConfig()
    {
        return static::class;
    }

    public static function getResourceConfig()
    {
        return self::resolveNamespacedClass(config('lyre.path.resource'));
    }

    public static function getRepositoryConfig()
    {
        return self::resolveNamespacedClass(
            baseNamespace: config('lyre.path.repository'),
            suffix: 'Repository'
        );
    }

    public static function getRepositoryInterfaceConfig()
    {
        return self::resolveNamespacedClass(
            baseNamespace: config('lyre.path.contracts'),
            suffix: 'RepositoryInterface',
            checkInterface: true
        );
    }

    public static function getStoreRequestConfig()
    {
        return self::resolveNamespacedClass(
            baseNamespace: config('lyre.path.request'),
            prefix: 'Store',
            suffix: 'Request'
        );
    }

    public static function getUpdateRequestConfig()
    {
        return self::resolveNamespacedClass(
            baseNamespace: config('lyre.path.request'),
            prefix: 'Update',
            suffix: 'Request'
        );
    }

    protected static function resolveNamespacedClass(string|array $baseNamespace, string $prefix = '', string $suffix = '', bool $checkInterface = false): ?string
    {
        $class = self::getClassName();
        $relativeNamespace = self::getRelativeNamespace();

        if (is_array($baseNamespace)) {
            foreach ($baseNamespace as $namespace) {
                $fullClass = self::retrieveNamespacedClass($namespace, $relativeNamespace, $class, $prefix, $suffix, $checkInterface);
                if ($fullClass) break;
            }

            return $fullClass;
        }

        return self::retrieveNamespacedClass($baseNamespace, $relativeNamespace, $class, $prefix, $suffix, $checkInterface);
    }

    public static function retrieveNamespacedClass(string|array $baseNamespace, string $relativeNamespace = '', string $class = '', string $prefix = '', string $suffix = '', bool $checkInterface = false): ?string
    {
        $fullClass = "\\" . trim($baseNamespace, "\\") . ($relativeNamespace ? "\\{$relativeNamespace}" : "") . "\\{$prefix}{$class}{$suffix}";

        if ($checkInterface && interface_exists($fullClass)) {
            return $fullClass;
        }

        if (!$checkInterface && class_exists($fullClass)) {
            return $fullClass;
        }

        return null;
    }


    public static function getModelRelationships(int | null $depth = null): array
    {
        $depth = $depth ?? config('lyre.relationship_depth', 1);
        // $cacheKey = 'model_relationships_' . static::getClassName() . "_depth_$depth";
        $cacheKey = 'model_relationships_' . static::getClassName();

        return cache()->rememberForever($cacheKey, function () use ($depth) {
            return static::extractModelRelationships(new static(), $depth);
        });
    }

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


    public function searcheableRelations()
    {
        return [];
    }

    public static function setGlobalCustomColumns(array $columns)
    {
        static::$globalCustomColumns = $columns;
    }

    /**
     * NOTE: Kigathi - July 23 2025
     * Changed method signature to avoid conflict with tenancy
     */
    public function resolveCustomColumns()
    {
        return $this->customColumns ?: static::$globalCustomColumns;
    }

    public function setCustomColumns(array $columns)
    {
        $this->customColumns = $columns;
    }

    public function scopeTotal($query)
    {
        return $query->count();
    }

    public static function resolveRepository()
    {
        return app(ltrim(static::getRepositoryInterfaceConfig(), '\\'));
    }
}
