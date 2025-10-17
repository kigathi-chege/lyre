<?php

namespace Lyre\Facades;

use Illuminate\Support\Facades\Facade;
use Illuminate\Support\Str;
use Lyre\Services\Database\DatabaseService;
use Lyre\Services\ModelService;
use Lyre\Services\Response\ResponseService;
use Lyre\Services\Validation\ValidationService;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * Lyre Facade
 * 
 * This facade provides easy access to all Lyre package functionality
 * including model operations, response handling, validation, and database utilities.
 * 
 * @package Lyre\Facades
 * 
 * @method static \Lyre\Services\ModelService model() Get the model service instance
 * @method static \Lyre\Services\Response\ResponseService response() Get the response service instance
 * @method static \Lyre\Services\Validation\ValidationService validation() Get the validation service instance
 * @method static \Lyre\Services\Database\DatabaseService database() Get the database service instance
 * @method static array getModelClasses() Get all model classes
 * @method static string getModelResource(mixed $model) Get model resource class
 * @method static string getModelName(mixed $model) Get model name
 * @method static string|null getModelNameColumn(mixed $model) Get model name column
 * @method static mixed getModelId(mixed $model) Get model ID
 * @method static string|null getModelIdColumn(mixed $model) Get model ID column
 * @method static string getModelClassFromTableName(string $table_name) Get model class from table name
 * @method static \Illuminate\Http\JsonResponse createResponse(bool $status, string $message, mixed $result, int $code = 200, mixed $trace = false, array $extra = [], array $headers = [], bool $forgetGuestUuid = false) Create standardized response
 * @method static \Illuminate\Http\JsonResponse parseValidationErrorResponse(mixed $errors) Parse validation error response
 * @method static int getResponseCode(string $response) Get response code
 * @method static mixed getStatusCode(string $status, mixed $model) Get status code
 * @method static bool isArrayAssociative(array $array) Check if array is associative
 * @method static bool isNotNumeric(mixed $value) Check if value is not numeric
 * @method static bool isJson(mixed $string) Check if string is valid JSON
 * @method static array getAllTables() Get all database tables
 * @method static array getTableForeignColumns(string $table, string|null $schema = null) Get foreign key columns for table
 * @method static bool columnExists(string $table, string $column) Check if column exists
 * @method static array|null getJoinDetails(string $relationName, mixed $model) Get join details for relationship
 */
class Lyre extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor(): string
    {
        return 'lyre';
    }

    /**
     * Get the model service instance.
     *
     * @return ModelService
     */
    public static function model(): ModelService
    {
        return app(ModelService::class);
    }

    /**
     * Get the response service instance.
     *
     * @return ResponseService
     */
    public static function response(): ResponseService
    {
        return app(ResponseService::class);
    }

    /**
     * Get the validation service instance.
     *
     * @return ValidationService
     */
    public static function validation(): ValidationService
    {
        return app(ValidationService::class);
    }

    /**
     * Get the database service instance.
     *
     * @return DatabaseService
     */
    public static function database(): DatabaseService
    {
        return app(DatabaseService::class);
    }

    /**
     * Get all model classes.
     *
     * @return array
     */
    public static function getModelClasses(): array
    {
        return static::model()->getModelClasses();
    }

    /**
     * Get model resource class.
     *
     * @param mixed $model
     * @return string
     */
    public static function getModelResource($model): string
    {
        return static::model()->getModelResource($model);
    }

    /**
     * Get model name.
     *
     * @param mixed $model
     * @return string
     */
    public static function getModelName($model): string
    {
        return static::model()->getModelName($model);
    }

    /**
     * Get model name column.
     *
     * @param mixed $model
     * @return string|null
     */
    public static function getModelNameColumn($model): ?string
    {
        return static::model()->getModelNameColumn($model);
    }

    /**
     * Get model ID.
     *
     * @param mixed $model
     * @return mixed
     */
    public static function getModelId($model)
    {
        return static::model()->getModelId($model);
    }

    /**
     * Get model ID column.
     *
     * @param mixed $model
     * @return string|null
     */
    public static function getModelIdColumn($model): ?string
    {
        return static::model()->getModelIdColumn($model);
    }

    /**
     * Generate slug for model.
     *
     * @param mixed $model
     * @return string
     */
    public static function generateSlug($model): string
    {
        return static::model()->generateSlug($model);
    }

    /**
     * Register repositories helper function.
     *
     * @param mixed $app
     * @param string $repositoriesBaseNamespace
     * @param string $contractsBaseNamespace
     * @return void
     */
    public static function registerRepositories($app, string $repositoriesBaseNamespace, string $contractsBaseNamespace): void
    {
        $repositoriesPath = get_namespace_path($repositoriesBaseNamespace);
        $contractsPath = get_namespace_path($contractsBaseNamespace);

        if (! file_exists($repositoriesPath)) {
            \Illuminate\Support\Facades\File::makeDirectory($repositoriesPath);
        }

        if (! file_exists($contractsPath)) {
            \Illuminate\Support\Facades\File::makeDirectory($contractsPath);
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($repositoriesPath)
        );

        foreach ($iterator as $file) {
            if (!$file->isFile()) continue;

            $fileName = $file->getFilename();

            if (!Str::endsWith($fileName, 'Repository.php') || $fileName === 'BaseRepository.php') {
                continue;
            }

            $relativePath = Str::after($file->getPathname(), $repositoriesPath . DIRECTORY_SEPARATOR);
            $namespacePath = str_replace(['/', '\\'], '\\', Str::replaceLast('.php', '', $relativePath));

            $interfaceNamespace = $contractsBaseNamespace . '\\' . $namespacePath . 'Interface';
            $implementationNamespace = $repositoriesBaseNamespace . '\\' . $namespacePath;

            $interfaceFilePath = $contractsPath . DIRECTORY_SEPARATOR . Str::replaceLast('Repository.php', 'RepositoryInterface.php', $relativePath);

            $helperName = Str::of($namespacePath)->camel();

            if (file_exists($interfaceFilePath)) {
                $app->bind($interfaceNamespace, function ($app) use ($implementationNamespace) {
                    return $app->make($implementationNamespace);
                });

                if (! function_exists($helperName)) {
                    eval("
                        function {$helperName}() {
                            return app('{$interfaceNamespace}');
                        }
                    ");
                }
            }
        }
    }

    /**
     * Register global observers helper function.
     *
     * @param string $modelsNamespace
     * @return void
     */
    public static function registerGlobalObservers(string $modelsNamespace): void
    {
        $modelsPath = get_namespace_path($modelsNamespace);

        if (!file_exists($modelsPath)) {
            return;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($modelsPath)
        );

        foreach ($iterator as $file) {
            if (!$file->isFile() || !Str::endsWith($file->getFilename(), '.php')) {
                continue;
            }

            $relativePath = Str::after($file->getPathname(), $modelsPath . DIRECTORY_SEPARATOR);
            $namespacePath = str_replace(['/', '\\'], '\\', Str::replaceLast('.php', '', $relativePath));
            $modelClass = $modelsNamespace . '\\' . $namespacePath;

            if (class_exists($modelClass)) {
                $observerClass = $modelClass . 'Observer';

                if (class_exists($observerClass)) {
                    $modelClass::observe($observerClass);
                }
            }
        }
    }

    /**
     * Create standardized response.
     *
     * @param bool $status
     * @param string $message
     * @param mixed $result
     * @param int $code
     * @param mixed $trace
     * @param array $extra
     * @param array $headers
     * @param bool $forgetGuestUuid
     * @return \Illuminate\Http\JsonResponse
     */
    public static function createResponse(
        $status,
        $message,
        $result,
        $code = 200,
        $trace = false,
        array $extra = [],
        array $headers = [],
        bool $forgetGuestUuid = false
    ): \Illuminate\Http\JsonResponse {
        return static::response()->create($status, $message, $result, $code, $trace, $extra, $headers, $forgetGuestUuid);
    }

    /**
     * Parse validation error response.
     *
     * @param mixed $errors
     * @return \Illuminate\Http\JsonResponse
     */
    public static function parseValidationErrorResponse($errors): \Illuminate\Http\JsonResponse
    {
        return static::response()->parseValidationErrorResponse($errors);
    }

    /**
     * Get response code.
     *
     * @param string $response
     * @return int
     */
    public static function getResponseCode($response): int
    {
        return static::response()->getResponseCode($response);
    }

    /**
     * Get status code.
     *
     * @param string $status
     * @param mixed $model
     * @return mixed
     */
    public static function getStatusCode($status, $model)
    {
        return static::validation()->getStatusCode($status, $model);
    }

    /**
     * Check if array is associative.
     *
     * @param array $array
     * @return bool
     */
    public static function isArrayAssociative($array): bool
    {
        return static::validation()->isArrayAssociative($array);
    }

    /**
     * Check if value is not numeric.
     *
     * @param mixed $value
     * @return bool
     */
    public static function isNotNumeric($value): bool
    {
        return static::validation()->isNotNumeric($value);
    }

    /**
     * Check if string is valid JSON.
     *
     * @param mixed $string
     * @return bool
     */
    public static function isJson($string): bool
    {
        return static::validation()->isJson($string);
    }

    /**
     * Get all database tables.
     *
     * @return array
     */
    public static function getAllTables(): array
    {
        return static::database()->getAllTables();
    }

    /**
     * Get foreign key columns for table.
     *
     * @param string $table
     * @param string|null $schema
     * @return array
     */
    public static function getTableForeignColumns($table, $schema = null): array
    {
        return static::database()->getTableForeignColumns($table, $schema);
    }

    /**
     * Check if column exists.
     *
     * @param string $table
     * @param string $column
     * @return bool
     */
    public static function columnExists($table, $column): bool
    {
        return static::database()->columnExists($table, $column);
    }

    /**
     * Get join details for relationship.
     *
     * @param string $relationName
     * @param mixed $model
     * @return array|null
     */
    public static function getJoinDetails($relationName, $model): ?array
    {
        return static::database()->getJoinDetails($relationName, $model);
    }
}
