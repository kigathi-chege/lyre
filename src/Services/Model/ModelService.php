<?php

namespace Lyre\Strings\Services\Model;

use Lyre\Strings\Exceptions\CommonException;
use Lyre\Strings\Resource\Resource;

/**
 * Service class for model-related operations.
 * 
 * This service provides methods for working with models including
 * class discovery, configuration generation, and resource resolution.
 * 
 * @package Lyre\Strings\Services\Model
 */
class ModelService
{
    /**
     * Get all model classes from configured namespaces.
     *
     * @param string|null $baseNamespace
     * @return array
     */
    public function getModelClasses($baseNamespace = null): array
    {
        if ($baseNamespace !== null) {
            return cache()->rememberForever("app_model_classes:{$baseNamespace}", fn() => $this->scanForModels($baseNamespace));
        }

        $defaultNamespaces = config('lyre.path.model', ['App\\Models']);
        return cache()->rememberForever('app_model_classes', fn() => $this->scanForModels($defaultNamespaces));
    }

    /**
     * Scan for model classes in given namespaces.
     *
     * @param string|array $namespaces
     * @return array
     */
    public function scanForModels($namespaces): array
    {
        $namespaces = is_array($namespaces) ? $namespaces : [$namespaces];
        logger("Scanning for models in namespaces: " . implode(', ', $namespaces));
        $modelClasses = [];

        foreach ($namespaces as $namespace) {
            $namespace = trim($namespace, '\\');
            $namespacePath = get_namespace_path($namespace);

            if (!$namespacePath || !file_exists($namespacePath)) {
                continue;
            }

            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($namespacePath),
                \RecursiveIteratorIterator::LEAVES_ONLY
            );

            foreach ($iterator as $file) {
                if (
                    !$file->isFile() ||
                    $file->getExtension() !== 'php' ||
                    $file->getFilename() === 'BaseModel.php'
                ) {
                    continue;
                }

                $relativePath = str_replace($namespacePath . DIRECTORY_SEPARATOR, '', $file->getPathname());

                $classPath = str_replace(
                    [DIRECTORY_SEPARATOR, '.php'],
                    ['\\', ''],
                    $relativePath
                );

                $className = $namespace . '\\' . $classPath;

                if (!class_exists($className)) {
                    continue;
                }

                $reflection = new \ReflectionClass($className);

                if (
                    !$reflection->isInstantiable() ||
                    !$reflection->isSubclassOf(\Illuminate\Database\Eloquent\Model::class)
                ) {
                    continue;
                }

                $modelName = class_basename($className);
                $modelClasses[$modelName] = $className;
            }
        }

        return $modelClasses;
    }

    /**
     * Get model instance from configuration.
     *
     * @param mixed $model
     * @return mixed
     */
    public function getModelInstance($model)
    {
        $modelsConfig = config('models');
        $modelInstances = [];
        foreach ($modelsConfig as $key => $modelConfig) {
            if ($model instanceof $modelConfig['model']) {
                return $modelConfig['model'] ?? null;
            }
        }
        return $modelInstances;
    }

    /**
     * Get model name.
     *
     * @param mixed $model
     * @return string
     */
    public function getModelName($model): string
    {
        $name_col = $this->getModelNameColumn($model);
        if ($name_col) {
            return $model->$name_col;
        }
        return class_basename($model) ?? "MODEL";
    }

    /**
     * Get model name column.
     *
     * @param mixed $model
     * @return string|null
     */
    public function getModelNameColumn($model): ?string
    {
        $modelConfig = $model->generateConfig();
        return $modelConfig['name'] ?? null;
    }

    /**
     * Get model ID.
     *
     * @param mixed $model
     * @return mixed
     */
    public function getModelId($model)
    {
        $id_col = $this->getModelIdColumn($model);
        if ($id_col) {
            return $model->$id_col;
        }
    }

    /**
     * Get model ID column.
     *
     * @param mixed $model
     * @return string|null
     */
    public function getModelIdColumn($model): ?string
    {
        $modelConfig = $model->generateConfig();
        return $modelConfig['id'] ?? null;
    }

    /**
     * Get model resource class.
     *
     * @param mixed $model
     * @return string
     */
    public function getModelResource($model): string
    {
        $modelConfig = $model->generateConfig();
        return $modelConfig['resource'] ?? Resource::class;
    }

    /**
     * Get model class from table name.
     *
     * @param string $table_name
     * @return string
     * @throws CommonException
     */
    public function getModelClassFromTableName($table_name): string
    {
        $allModels = $this->getModelClasses();
        foreach ($allModels as $model) {
            if ((new $model)->getTable() === $table_name) {
                return $model;
            }
        }
        throw CommonException::fromMessage("Model with table name {$table_name} not found");
    }
}
