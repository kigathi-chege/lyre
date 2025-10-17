<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Pluralizer;
use Illuminate\Support\Str;
use Lyre\Exceptions\CommonException;
use Lyre\Resource;
use Lyre\Facades\Lyre;
use Symfony\Component\HttpFoundation\Response;

if (!function_exists('basic_fields')) {
    function basic_fields(Illuminate\Database\Schema\Blueprint $table, $tableName)
    {
        if (!\Illuminate\Support\Facades\Schema::hasColumn($tableName, 'id')) {
            $table->id();
        }
        if (!\Illuminate\Support\Facades\Schema::hasColumn($tableName, 'created_at') && !\Illuminate\Support\Facades\Schema::hasColumn($tableName, 'updated_at')) {
            $table->timestamps();
        }
        if (!\Illuminate\Support\Facades\Schema::hasColumn($tableName, 'link')) {
            $table->string('link')->nullable();
        }
        if (!\Illuminate\Support\Facades\Schema::hasColumn($tableName, 'slug')) {
            $table->string('slug')->unique()->index();
        }
        if (!\Illuminate\Support\Facades\Schema::hasColumn($tableName, 'description')) {
            $table->text('description')->nullable();
        }
        if (!\Illuminate\Support\Facades\Schema::hasColumn($tableName, 'metadata')) {
            $connection = Schema::getConnection();
            $driver = $connection->getDriverName();
            $table->{$driver === 'pgsql' ? 'jsonb' : 'json'}('metadata')->nullable()->comment('The metadata of the transaction');
        }
    }
}

if (! function_exists("international_format_phone")) {
    function international_format_phone($phoneNumber, $countrycode = "254")
    {
        $phoneNumber = preg_replace("/[^0-9]/", "", $phoneNumber);
        if (substr($phoneNumber, 0, 1) === "0") {
            $phoneNumber = "+{$countrycode}" . substr($phoneNumber, 1);
        } elseif (substr($phoneNumber, 0, 3) === "{$countrycode}") {
            $phoneNumber = "+" . $phoneNumber;
        }
        return $phoneNumber;
    }
}

if (! function_exists("parse_validation_error_response")) {
    function parse_validation_error_response($errors)
    {
        return Lyre::parseValidationErrorResponse($errors);
    }
}

if (! function_exists("__response")) {
    function __response(
        $status,
        $message,
        $result,
        $code = 200,
        $trace = false,
        array $extra = [],
        array $headers = [],
        bool $forgetGuestUuid = false
    ) {
        return Lyre::createResponse($status, $message, $result, $code, $trace, $extra, $headers, $forgetGuestUuid);
    }
}

if (! function_exists("create_response")) {
    function create_response(
        $status,
        $message,
        $result,
        $code = 200,
        $trace = false,
        array $extra = [],
        array $headers = [],
        bool $forgetGuestUuid = false
    ) {
        return Lyre::createResponse($status, $message, $result, $code, $trace, $extra, $headers, $forgetGuestUuid);
    }
}

if (! function_exists("get_response_code")) {
    function get_response_code($response)
    {
        return Lyre::getResponseCode($response);
    }
}

if (! function_exists("get_status_code")) {
    function get_status_code($status, $model)
    {
        return Lyre::getStatusCode($status, $model);
    }
}

if (! function_exists("is_array_associative")) {
    function is_array_associative($array)
    {
        return Lyre::isArrayAssociative($array);
    }
}

if (! function_exists("is_not_numeric")) {
    function is_not_numeric($value)
    {
        return Lyre::isNotNumeric($value);
    }
}

if (! function_exists("is_json")) {
    function is_json($string)
    {
        return Lyre::isJson($string);
    }
}

if (! function_exists("get_model_classes")) {
    function get_model_classes($baseNamespace = null)
    {
        return Lyre::getModelClasses();
    }
}

if (! function_exists("get_model_resource")) {
    function get_model_resource($model)
    {
        return Lyre::getModelResource($model);
    }
}

if (! function_exists("get_model_name")) {
    function get_model_name($model)
    {
        return Lyre::getModelName($model);
    }
}

if (! function_exists("get_model_name_column")) {
    function get_model_name_column($model)
    {
        return Lyre::getModelNameColumn($model);
    }
}

if (! function_exists("get_model_id")) {
    function get_model_id($model)
    {
        return Lyre::getModelId($model);
    }
}

if (! function_exists("get_model_id_column")) {
    function get_model_id_column($model)
    {
        return Lyre::getModelIdColumn($model);
    }
}

if (! function_exists("get_model_class_from_table_name")) {
    function get_model_class_from_table_name($table_name)
    {
        // This function needs to be implemented in the service classes
        // For now, we'll keep the original implementation
        $modelClasses = get_model_classes();
        foreach ($modelClasses as $modelClass) {
            if (class_exists($modelClass)) {
                $model = new $modelClass;
                if ($model->getTable() === $table_name) {
                    return $modelClass;
                }
            }
        }
        return null;
    }
}

if (! function_exists("get_all_tables")) {
    function get_all_tables()
    {
        return Lyre::getAllTables();
    }
}

if (! function_exists("get_table_foreign_columns")) {
    function get_table_foreign_columns($table, $schema = null)
    {
        return Lyre::getTableForeignColumns($table, $schema);
    }
}

if (! function_exists("column_exists")) {
    function column_exists($table, $column)
    {
        return Lyre::columnExists($table, $column);
    }
}

if (! function_exists("get_join_details")) {
    function get_join_details($relationName, $model)
    {
        return Lyre::getJoinDetails($relationName, $model);
    }
}

if (! function_exists("keyword_search")) {
    function keyword_search($query, $search, $serializableColumns, $relations = [])
    {
        $query->where(function ($query) use ($search, $serializableColumns, $relations) {
            foreach ($serializableColumns as $column) {
                $query->orWhere($column, 'LIKE', "%{$search}%");
            }

            if (!empty($relations)) {
                foreach ($relations as $relation) {
                    $query->orWhereHas($relation, function ($query) use ($search) {
                        $query->where('name', 'LIKE', "%{$search}%");
                    });
                }
            }
        });

        return $query;
    }
}

if (! function_exists("filter_by_relationship")) {
    function filter_by_relationship($query, $relation, $column, $value)
    {
        return $query->whereHas($relation, function ($query) use ($column, $value) {
            if (is_array($value)) {
                $query->whereIn($column, $value);
            } else {
                $query->where($column, $value);
            }
        });
    }
}

if (! function_exists("register_repositories")) {
    function register_repositories($app, string $repositoriesBaseNamespace, string $contractsBaseNamespace)
    {
        return Lyre::registerRepositories($app, $repositoriesBaseNamespace, $contractsBaseNamespace);
    }
}

if (! function_exists("register_global_observers")) {
    function register_global_observers($modelsNamespace)
    {
        return Lyre::registerGlobalObservers($modelsNamespace);
    }
}

if (! function_exists("get_namespace_path")) {
    function get_namespace_path($namespace)
    {
        $namespace = str_replace('\\', '/', $namespace);
        $basePath = app_path();

        if (str_starts_with($namespace, 'App/')) {
            return $basePath . '/' . str_replace('App/', '', $namespace);
        }

        if (str_starts_with($namespace, 'Lyre/')) {
            return base_path('vendor/lyre/lyre/src/' . str_replace('Lyre/', '', $namespace));
        }

        return $basePath . '/' . $namespace;
    }
}

if (! function_exists("tenant")) {
    function tenant()
    {
        return app()->bound('tenant') ? app('tenant') : null;
    }
}

// Additional helper functions that were in the original file
if (! function_exists("get_file_name_without_extension")) {
    function get_file_name_without_extension($file, $name = null)
    {
        $extension = $file->getClientOriginalExtension();
        $fileName = $name ? $name : pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        if (substr_compare($fileName, $extension, -strlen($extension)) === 0) {
            $fileName = str_replace($extension, '', $fileName);
            if (substr($fileName, -1) === '.') {
                $fileName = substr($fileName, 0, -1);
            }
        }
        return $fileName;
    }
}

if (! function_exists("get_file_extension")) {
    function get_file_extension($file, $extension = null)
    {
        $extension = $extension ? $extension : ($file->getClientOriginalExtension() ? $file->getClientOriginalExtension() : "jpg");
        return strtolower($extension);
    }
}

if (! function_exists("generate_resized_versions")) {
    function generate_resized_versions($file, $sizes = [])
    {
        // Implementation for generating resized versions
        // This would need to be implemented based on the original logic
        return [];
    }
}

// Add any other helper functions that were in the original file
// I'll need to check the original file for more functions