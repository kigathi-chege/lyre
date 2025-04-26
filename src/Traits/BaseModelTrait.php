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
        $relativeNamespace = trim(str_replace('App\Models', '', $namespace), '\\');
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
        return self::resolveNamespacedClass('App\Http\Resources');
    }

    public static function getRepositoryConfig()
    {
        return self::resolveNamespacedClass(
            baseNamespace: 'App\Repositories',
            suffix: 'Repository'
        );
    }

    public static function getRepositoryInterfaceConfig()
    {
        return self::resolveNamespacedClass(
            baseNamespace: 'App\Repositories\Interface',
            suffix: 'RepositoryInterface',
            checkInterface: true
        );
    }

    public static function getStoreRequestConfig()
    {
        return self::resolveNamespacedClass(
            baseNamespace: 'App\Http\Requests',
            prefix: 'Store',
            suffix: 'Request'
        );
    }

    public static function getUpdateRequestConfig()
    {
        return self::resolveNamespacedClass(
            baseNamespace: 'App\Http\Requests',
            prefix: 'Update',
            suffix: 'Request'
        );
    }

    protected static function resolveNamespacedClass(string $baseNamespace, string $prefix = '', string $suffix = '', bool $checkInterface = false): ?string
    {
        $class = self::getClassName();
        $relativeNamespace = self::getRelativeNamespace();
        $fullClass = "\\" . $baseNamespace . ($relativeNamespace ? "\\{$relativeNamespace}" : "") . "\\{$prefix}{$class}{$suffix}";

        if ($checkInterface) {
            return interface_exists($fullClass) ? $fullClass : null;
        }

        return class_exists($fullClass) ? $fullClass : null;
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

    public static function excludeSerializableColumns()
    {
        return [];
    }

    public static function includeSerializableColumns()
    {
        return [];
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

    // TODO: Kigathi - April 26 2025 - This should only be included if the content package is installed, and the current model supports files
    public function attachFile($fileId, $single = false)
    {
        if ($single) {
            $this->attachments()->delete();
        }
        return $this->attachments()->create([
            'file_id' => $fileId,
            'attachable_id' => $this->id,
            'attachable_type' => static::class,
        ]);
    }
}
