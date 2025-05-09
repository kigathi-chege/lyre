<?php

namespace Lyre\Traits;

trait BaseModelTrait
{
    const ID_COLUMN = 'id';
    const NAME_COLUMN = 'name';
    const STATUS_CONFIG = 'constant.status';
    const ORDER_COLUMN = 'created_at';
    const ORDER_DIRECTION = 'desc';

    protected $customColumns = [];
    protected static $globalCustomColumns = [];
    protected static $excludedSerializableColumns = [];
    protected static $includedSerializableColumns = [];

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


    public static function getModelRelationships(): array
    {
        $cacheKey = 'model_relationships_' . static::getClassName();

        return cache()->rememberForever($cacheKey, function () {

            $model = new static();
            $class = new \ReflectionClass($model);
            $methods = $class->getMethods();

            $relationships = [];

            foreach ($methods as $method) {
                if ($method->class != get_class($model)) continue;
                if (!empty($method->getParameters())) continue;

                try {
                    $relation = $method->invoke($model);

                    if ($relation instanceof \Illuminate\Database\Eloquent\Relations\Relation) {
                        $relatedModel = get_class($relation->getRelated());
                        $relationships[$method->getName()] = $relatedModel;
                    }
                } catch (\Throwable $e) {
                    // skip methods that can't be invoked
                }
            }

            return $relationships;
        });
    }

    public function searcheableRelations()
    {
        return [];
    }

    public static function setExcludedSerializableColumns($columns = [])
    {
        static::$excludedSerializableColumns = array_merge(static::$excludedSerializableColumns, [static::class => $columns]);
    }

    public function getExcludedSerializableColumns()
    {
        $filtered = array_filter(static::$excludedSerializableColumns, function ($_, $key) {
            return !is_int($key);
        }, ARRAY_FILTER_USE_BOTH);

        $currentExclusions = [];

        if (count($filtered) > 0) {
            $currentExclusions = collect($filtered)->filter(fn($_, $key) =>  $key == $this::class)->flatten()->values()->toArray();
        }

        return array_merge($currentExclusions, $this->getHidden());
    }

    public static function setIncludedSerializableColumns($columns = [])
    {
        static::$includedSerializableColumns = array_merge(static::$includedSerializableColumns, [static::class => $columns]);
    }

    public function getIncludedSerializableColumns()
    {
        $filtered = array_filter(static::$includedSerializableColumns, function ($_, $key) {
            return !is_int($key);
        }, ARRAY_FILTER_USE_BOTH);

        $currentInclusions = [];

        if (count($filtered) > 0) {
            $currentInclusions = collect($filtered)->filter(fn($_, $key) =>  $key == $this::class)->flatten()->values()->toArray();
        }

        return array_merge($currentInclusions, $this->getVisible());
    }

    public static function setGlobalCustomColumns(array $columns)
    {
        static::$globalCustomColumns = $columns;
    }

    public function getCustomColumns()
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
