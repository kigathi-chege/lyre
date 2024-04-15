<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Pluralizer;
use Illuminate\Support\Str;
use Lyre\Exceptions\CommonException;
use Lyre\Resource;
use Symfony\Component\HttpFoundation\Response;

if (!function_exists("international_format_phone")) {
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

if (!function_exists("parse_validation_error_response")) {
    function parse_validation_error_response($errors)
    {
        return response()->json([
            "status" => false,
            "message" => "Validation errors",
            "response" => $errors,
        ]);
    }
}

if (!function_exists("curate_response")) {
    function curate_response($status, $message, $response, $code = 200, $trace = false)
    {
        $responseData = [
            "status" => $status,
            "message" => $message,
            "response" => $response,
            "code" => $code,
        ];
        if ($trace !== false && env("APP_DEBUG", false)) {
            $responseData['trace'] = $trace;
        }
        return response()->json(
            $responseData,
            $status
            ? 200
            : (isset(Response::$statusTexts[$code])
                ? $code
                : Response::HTTP_EXPECTATION_FAILED)
        );
    }
}

if (!function_exists('generate_slug')) {
    function generate_slug($model)
    {
        $baseSlug = Str::slug(get_model_name($model));
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
}

if (!function_exists("generate_uuid")) {
    function generate_uuid($model, $length = 8)
    {
        $uuid = substr(\Illuminate\Support\Str::uuid(), 0, $length);
        if ($model::where("uuid", $uuid)->exists()) {
            do {
                $uuid = substr(\Illuminate\Support\Str::uuid(), 0, $length);
            } while ($model::where("uuid", $uuid)->exists());
        }
        return $uuid;
    }
}

if (!function_exists("set_slug")) {
    function set_slug($model)
    {
        $slug = generate_slug($model);
        $model->setAttribute("slug", $slug);
    }
}

if (!function_exists("set_uuid")) {
    function set_uuid($model)
    {
        $uuid = generate_uuid($model);
        $model->setAttribute("uuid", $uuid);
    }
}

if (!function_exists('get_model_classes')) {
    function get_model_classes()
    {
        $modelsPath = app_path("Models");
        $models = scandir($modelsPath);
        $modelClasses = [];

        foreach ($models as $model) {
            if ($model === '.' || $model === '..' || $model === 'BaseModel.php') {
                continue;
            }
            $modelName = str_replace('.php', '', $model);
            $model = "App\Models\\{$modelName}";
            $modelClasses[$modelName] = $model;
        }

        return $modelClasses;
    }
}

if (!function_exists('get_model_instance')) {
    function get_model_instance($model)
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
}

if (!function_exists('get_model_name')) {
    function get_model_name($model)
    {
        $name_col = get_model_name_column($model);
        if ($name_col) {
            return $model->$name_col;
        }
        return class_basename($model) || "MODEL";
    }
}

if (!function_exists('get_model_name_column')) {
    function get_model_name_column($model)
    {
        $modelConfig = $model->generateConfig();
        return $modelConfig['name'] ?? null;
    }
}

if (!function_exists("get_model_id")) {
    function get_model_id($model)
    {
        $id_col = get_model_id_column($model);
        if ($id_col) {
            return $model->$id_col;
        }
    }
}

if (!function_exists('get_model_id_column')) {
    function get_model_id_column($model)
    {
        $modelConfig = $model->generateConfig();
        return $modelConfig['id'] ?? null;
    }
}

if (!function_exists("get_model_resource")) {
    function get_model_resource($model)
    {
        $modelConfig = $model->generateConfig();
        return $modelConfig['resource'] ?? Resource::class;
    }
}

if (!function_exists('get_role_name')) {
    function get_role_name($role)
    {
        $roles = config('constant.role');
        $roleName = array_search($role, $roles);

        if (!$roleName) {
            throw CommonException::fromCode(404, ["model" => "Role"]);
        }

        return $roleName;

    }
}

if (!function_exists("escape_like")) {
    function escape_like(string $value, string $char = '\\'): string
    {
        return str_replace(
            [$char, '%', '_'],
            [$char . $char, $char . '%', $char . '_'],
            $value
        );
    }
}

if (!function_exists("keyword_search")) {
    function keyword_search($query, $keyword, $columns, $relations = [])
    {
        $keyword_formatted = '%' . escape_like($keyword) . '%';

        foreach ($columns as $index => $column) {
            if (column_exists($query->getModel()->getTable(), $column)) {
                if ($index === 0) {
                    $query->where($column, 'LIKE', $keyword_formatted);
                } else {
                    $query->orWhere($column, 'LIKE', $keyword_formatted);
                }
            }
        }

        /**
         * Kigathi - April 1 2024 - Revisit this code later to understand how to retrieve a models relations
         *
         * This will be important because it will open the way
         * for easy searching of related models through their serializable columns
         * instead of having to define the columns in the search query
         *   */
        // $modelRelations = ($query->getModel())->getRelations();
        // foreach ($modelRelations as $relationName => $relation) {
        // Check if the related model is the Vehicle model
        // if ($relation instanceof \Illuminate\Database\Eloquent\Relations\Relation) {
        //     if ($relation->getRelated() === Vehicle::class) {
        //         // VehicleMake model has a relationship with Vehicle model
        //         $relationship = $relationName;
        //         break;
        //     }
        // }
        // }

        foreach ($relations as $relation => $relationColumns) {
            $query->orWhereHas($relation, function ($query) use ($keyword_formatted, $relationColumns) {
                foreach ($relationColumns as $index => $column) {
                    if (column_exists($query->getModel()->getTable(), $column)) {
                        if ($index === 0) {
                            $query->where($column, 'LIKE', $keyword_formatted);
                        } else {
                            $query->orWhere($column, 'LIKE', $keyword_formatted);
                        }
                    }
                }
            });
        }

        return $query;
    }
}

if (!function_exists("filter_by_relationship")) {
    function filter_by_relationship($query, $relation, $column, $value)
    {
        return $query->WhereHas($relation, function ($query) use ($column, $value) {
            $query->where($column, $value);
        });
    }
}

if (!function_exists("column_exists")) {
    function column_exists($table, $column)
    {
        return Schema::hasColumn($table, $column);
    }
}

if (!function_exists("is_nan")) {
    function is_nan($value)
    {
        return !is_numeric($value) || !ctype_digit((string) $value);
    }
}

if (!function_exists("generate_basic_model_permissions")) {
    function generate_basic_model_permissions()
    {
        $permissions = [];
        $models = get_model_classes();
        foreach ($models as $model) {
            $name = (new $model())->getTable();
            $permissions[$name] = [
                "view-any-{$name}",
                "view-{$name}",
                "create-{$name}",
                "update-{$name}",
                "delete-{$name}",
                "restore-{$name}",
                "force-delete-{$name}",
            ];
        }
        return $permissions;
    }
}

if (!function_exists("generate_basic_model_response_codes")) {
    function generate_basic_model_response_codes()
    {
        $responseCodes = [];
        $responseCode = 10001;
        $modelClasses = get_model_classes();
        foreach ($modelClasses as $modelClass) {
            $config = (new $modelClass())->generateConfig();
            $pluralName = $config['table'];
            $name = Pluralizer::singular($pluralName);
            $responseCodes += [
                $responseCode++=> "get-{$pluralName}",
                $responseCode++=> "find-{$name}",
                $responseCode++=> "create-{$name}",
                $responseCode++=> "update-{$name}",
                $responseCode++=> "destroy-{$name}",
                $responseCode++=> "restore-{$name}",
            ];
        }
        return $responseCodes;
    }
}

if (!function_exists('get_response_code')) {
    function get_response_code($response)
    {
        $response_codes = config('response-codes');
        $code = array_search($response, $response_codes);
        return $code ?? $response_codes[0000];
    }
}

if (!function_exists('get_status_code')) {
    function get_status_code($status, $model)
    {
        $configPath = config("models.{$model->getTable()}.status") ?? 'constant.status';
        $config = config($configPath);
        return $config[$status] ?? throw CommonException::fromMessage("Status `{$status}` not found for model {$model->getTable()}");
    }
}

if (!function_exists('get_transaction_type_name')) {
    function get_transaction_type_name($type)
    {
        $types = config('constant.transaction.types');
        $transaction_type_name = null;
        foreach ($types as $key => $transactionType) {
            if ($transactionType['reference'] == $type) {
                $transaction_type_name = $key;
            }
        }
        if (!$transaction_type_name) {
            throw CommonException::fromCode(404, ["model" => "Transaction type {$type}"]);
        }
        return $transaction_type_name;
    }
}

if (!function_exists("get_delivery_time_days")) {
    function get_delivery_time_days($deliveryTime)
    {
        $conversionMap = [
            'one day' => 1,
            'two days' => 2,
            'three days' => 3,
            'one week' => 7,
            'two weeks' => 14,
            'three weeks' => 21,
            'one month' => 30, // Assuming an average of 30 days in a month
            'two months' => 60,
            'three months' => 90,
            'six months' => 180,
            'one year' => 365, // Assuming a non-leap year
        ];
        return isset($conversionMap[$deliveryTime]) ? $conversionMap[$deliveryTime] : null;
    }
}

if (!function_exists("format_price")) {
    function format_price($price, $locale = "en_KE", $currency = "KES")
    {
        $fmt = numfmt_create($locale, NumberFormatter::CURRENCY);
        $formatted_price = numfmt_format_currency($fmt, $price, $currency);
        $formatted_price = explode('.', $formatted_price)[0];
        return $formatted_price;
    }
}
