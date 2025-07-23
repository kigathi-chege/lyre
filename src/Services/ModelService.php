<?php

namespace Lyre\Services;

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Lyre\Resource;

class ModelService
{
    public static function getModelResource($model)
    {
        $modelConfig = $model->generateConfig();
        return $modelConfig['resource'] ?? Resource::class;
    }

    public static function getModelClasses(): array
    {
        $modelsPath = app_path("Models");
        $models = scandir($modelsPath);
        $modelClasses = [];

        foreach ($models as $model) {
            if ($model === '.' || $model === '..' || $model === 'BaseModel.php') {
                continue;
            }
            $modelName = str_replace('.php', '', $model);
            $model = config('lyre.model-path') . $modelName;
            $modelClasses[$modelName] = $model;
        }

        return $modelClasses;
    }

    public static function generateSlug($model)
    {
        $baseSlug = Str::slug(self::getModelName($model));
        $slug = $baseSlug;

        $counter = 1;
        $modelClass = get_class($model);
        do {
            if ($counter > 1) {
                $slug = $baseSlug . "-" . Str::random(10);
            }
            $counter++;
            if ($counter > 100) {
                throw new \RuntimeException("Unable to generate a unique slug.");
            }
        } while ($modelClass::where("slug", $slug)->when(
            $model->id && $modelClass::where("id", $model->id)->exists(),
            function ($query) use ($model) {
                return $query->whereNot("id", $model->id);
            }
        )->count());

        return $slug;
    }

    public static function setSlug($model)
    {
        $slug = self::generateSlug($model);
        $model->setAttribute("slug", $slug);
    }

    public static function generateUuid($model)
    {
        $uuid = Str::uuid();
        if ($model::where("uuid", $uuid)->exists()) {
            do {
                $uuid = Str::uuid();
            } while ($model::where("uuid", $uuid)->exists());
        }
        return $uuid;
    }

    public static function setUuid($model)
    {
        $uuid = self::generateUuid($model);
        $model->setAttribute("uuid", $uuid);
    }

    public static function getModelColumn(array $config, string $column)
    {
        if (Schema::hasColumn($config['table'], $column)) {
            return $column;
        }
        return null;
    }

    public static function getModelIdColumn($model)
    {
        $modelConfig = $model->generateConfig();
        if ($modelConfig['id']) {
            return self::getModelColumn($modelConfig, $modelConfig['id']);
        }
        return null;
    }

    public static function getModelId($model)
    {
        $id_col = self::getModelIdColumn($model);
        if ($id_col) {
            return $model->$id_col;
        }
    }

    public static function getModelNameColumn($model)
    {
        $modelConfig = $model->generateConfig();
        if ($modelConfig['name']) {
            return self::getModelColumn($modelConfig, $modelConfig['name']);
        }
        return null;
    }

    public static function getModelName($model)
    {
        $name_col = self::getModelNameColumn($model);
        if ($name_col) {
            return $model->$name_col;
        }
    }

    public static function getStatusName($status, $config = null)
    {
        $defaultConfigPath = config('lyre.status-config');
        $config = $config ?? config($defaultConfigPath);
        if (!is_array($config)) {
            $config = config($config);
        }
        $name = array_search($status, $config);
        return $name;
    }
}
