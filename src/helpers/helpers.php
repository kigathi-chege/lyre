<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Pluralizer;
use Illuminate\Support\Str;
use Lyre\Exceptions\CommonException;
use Lyre\Resource;
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
        return response()->json([
            "status"   => false,
            "message"  => "Validation errors",
            "response" => $errors,
        ]);
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
        $responseData = array_merge([
            "status"   => $status,
            "message"  => $message,
            "result" => $result,
            "code"     => $code,
        ], $extra);

        if ($trace !== false && env("APP_DEBUG", false)) {
            $responseData['trace'] = $trace;
        }

        $httpCode = $status
            ? 200
            : (isset(Response::$statusTexts[$code])
                ? $code
                : ($code == 0
                    ? Response::HTTP_INTERNAL_SERVER_ERROR
                    : Response::HTTP_EXPECTATION_FAILED));

        $jsonResponse = response()->json($responseData, $httpCode, $headers);

        if ($forgetGuestUuid) {
            $jsonResponse->withCookie(\Illuminate\Support\Facades\Cookie::forget('guest_uuid'));
        }

        return $jsonResponse;
    }
}

if (! function_exists('generate_slug')) {
    function generate_slug($model, $defaultSlug = null)
    {
        $baseSlug = $defaultSlug ?? Str::limit(Str::slug(get_model_name($model)), 120, '');
        $slug     = $baseSlug ?? Str::random(10);

        $counter    = 1;
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

        if ($slug == '') {
            $slug = generate_slug($model, Str::random(10));
        }

        return $slug;
    }
}

if (! function_exists("generate_uuid")) {
    function generate_uuid($model)
    {
        $uuid = \Illuminate\Support\Str::uuid();
        if ($model::where("uuid", $uuid)->exists()) {
            do {
                $uuid = \Illuminate\Support\Str::uuid();
            } while ($model::where("uuid", $uuid)->exists());
        }
        return $uuid;
    }
}

if (! function_exists("set_slug")) {
    function set_slug($model)
    {
        $slug = generate_slug($model);
        $model->setAttribute("slug", $slug);
    }
}

if (! function_exists("set_uuid")) {
    function set_uuid($model)
    {
        $uuid = generate_uuid($model);
        $model->setAttribute("uuid", $uuid);
    }
}

if (! function_exists('get_namespace_path')) {
    function get_namespace_path(string $namespace): ?string
    {
        // Normalize and trim namespace
        $namespace = trim($namespace, '\\');

        // Get Composer's ClassLoader
        $loader = require base_path('vendor/autoload.php');

        // Combine all prefixes (classmap also works if needed)
        $prefixes = array_merge(
            $loader->getPrefixesPsr4(), // includes app and vendor
        );

        foreach ($prefixes as $prefix => $dirs) {
            if (str_starts_with($namespace, rtrim($prefix, '\\'))) {
                $relativeNamespace = Str::of($namespace)->after($prefix)->replace('\\', DIRECTORY_SEPARATOR);

                foreach ($dirs as $dir) {
                    $path = rtrim($dir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $relativeNamespace;
                    if (is_dir($path)) {
                        return $path;
                    }
                }
            }
        }

        return null;
    }
}

if (! function_exists('get_filament_resources_for_namespace')) {
    function get_filament_resources_for_namespace(string $resourceNamespace): array
    {
        $resourcePath = get_namespace_path($resourceNamespace);

        if (!$resourcePath) {
            throw CommonException::fromMessage("Resource namespace not found");
        }

        $resources = collect((new \Symfony\Component\Finder\Finder)->files()->in($resourcePath)->name('*.php'))
            ->map(function ($file) use ($resourceNamespace, $resourcePath) {

                $relativePath = str_replace('.php', '', $file->getRelativePathname());

                $class = $resourceNamespace . '\\' . str_replace('/', '\\', $relativePath);

                return class_exists($class) && is_subclass_of($class, \Filament\Resources\Resource::class) ? $class : null;
            })
            ->filter()
            ->values()
            ->toArray();

        return $resources;
    }
}

if (! function_exists('get_filament_resource_for_model')) {
    function get_filament_resource_for_model(string $modelClass): ?string
    {
        $resources = \Filament\Facades\Filament::getResources(); // Returns all registered resources

        foreach ($resources as $resourceClass) {
            if (method_exists($resourceClass, 'getModel') && $resourceClass::getModel() === $modelClass) {
                return $resourceClass;
            }
        }

        return null;
    }
}

if (! function_exists('get_filament_shield_permissions_for_model')) {
    function get_filament_shield_permissions_for_model(string $modelClass): mixed
    {
        $resourceClass = get_filament_resource_for_model($modelClass);
        $filamentShieldResources = \BezhanSalleh\FilamentShield\Facades\FilamentShield::getResources();

        foreach ($filamentShieldResources as $entry) {
            if (($entry['fqcn'] ?? null) === $resourceClass) {
                $resourceByFQCN = $entry['fqcn'];
                $permissionPrefixes = \BezhanSalleh\FilamentShield\Support\Utils::getResourcePermissionPrefixes($resourceByFQCN);
                $permissions = collect();
                collect($permissionPrefixes)
                    ->each(function ($prefix) use ($entry, $permissions) {
                        $permissions->push($prefix . '_' . $entry['resource']);
                    });
                return $permissions;
            }
        }

        return null;
    }
}

if (! function_exists('get_filament_shield_permission_by_prefix')) {
    function get_filament_shield_permission_by_prefix($modelClass, $prefix)
    {
        $resourceClass = get_filament_resource_for_model($modelClass);
        if (!$resourceClass) {
            return $prefix . '_' . (new $modelClass)->getTable();
        }

        $filamentShieldResources = \BezhanSalleh\FilamentShield\Facades\FilamentShield::getResources();
        $entry = collect($filamentShieldResources)->first(fn($entry) => $entry['fqcn'] === $resourceClass);
        $resourceByFQCN = $entry['fqcn'];
        $permissionPrefixes = \BezhanSalleh\FilamentShield\Support\Utils::getResourcePermissionPrefixes($resourceByFQCN);

        if (in_array($prefix, $permissionPrefixes)) {
            return $prefix . '_' . $entry['resource'];
        }

        throw CommonException::fromMessage("Permission not found for prefix {$prefix} of {$modelClass} - {$resourceClass}");
    }
}

if (! function_exists('get_model_permission_by_prefix')) {
    function get_model_permission_by_prefix(string $modelClass, string $prefix)
    {
        if (config('lyre.filament-shield')) {
            $prefix = str_replace('-', '_', $prefix);
            return get_filament_shield_permission_by_prefix($modelClass, $prefix);
        } else {
            $tableName = (new ($modelClass))->getTable();
            $prefix = str_replace('_', '-', $prefix);
            return "{$prefix}-{$tableName}";
        }
    }
}

if (! function_exists('get_model_classes')) {
    function get_model_classes($baseNamespace = null)
    {
        if ($baseNamespace !== null) {
            return cache()->rememberForever("app_model_classes:{$baseNamespace}", fn() => scan_for_models($baseNamespace));
        }

        $defaultNamespaces = config('lyre.path.model', ['App\\Models']);
        return cache()->rememberForever('app_model_classes', fn() => scan_for_models($defaultNamespaces));
    }
}

if (! function_exists('scan_for_models')) {
    /**
     * Helper to scan for model classes given one or more namespaces.
     *
     * @param string|array $namespaces
     * @return array<string, string> Map model short name => FQCN
     */
    function scan_for_models($namespaces)
    {
        $namespaces = is_array($namespaces) ? $namespaces : [$namespaces];
        logger("Scanning for models in namespaces: " . implode(', ', $namespaces));
        $modelClasses   = [];

        foreach ($namespaces as $namespace) {
            $namespace = trim($namespace, '\\');
            $namespacePath = get_namespace_path($namespace);

            if (!$namespacePath || !file_exists($namespacePath)) {
                continue;
            }

            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($namespacePath),
                RecursiveIteratorIterator::LEAVES_ONLY
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

                $reflection = new ReflectionClass($className);

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
}

if (! function_exists('get_model_instance')) {
    function get_model_instance($model)
    {
        $modelsConfig   = config('models');
        $modelInstances = [];
        foreach ($modelsConfig as $key => $modelConfig) {
            if ($model instanceof $modelConfig['model']) {
                return $modelConfig['model'] ?? null;
            }
        }
        return $modelInstances;
    }
}

if (! function_exists('get_model_name')) {
    function get_model_name($model)
    {
        $name_col = get_model_name_column($model);
        if ($name_col) {
            return $model->$name_col;
        }
        return class_basename($model) ?? "MODEL";
    }
}

if (! function_exists('get_model_name_column')) {
    function get_model_name_column($model)
    {
        $modelConfig = $model->generateConfig();
        return $modelConfig['name'] ?? null;
    }
}

if (! function_exists("get_model_id")) {
    function get_model_id($model)
    {
        $id_col = get_model_id_column($model);
        if ($id_col) {
            return $model->$id_col;
        }
    }
}

if (! function_exists('get_model_id_column')) {
    function get_model_id_column($model)
    {
        $modelConfig = $model->generateConfig();
        return $modelConfig['id'] ?? null;
    }
}

if (! function_exists("get_model_resource")) {
    function get_model_resource($model)
    {
        $modelConfig = $model->generateConfig();
        return $modelConfig['resource'] ?? Resource::class;
    }
}

if (! function_exists('get_role_name')) {
    function get_role_name($role)
    {
        $roles    = config('constant.role');
        $roleName = array_search($role, $roles);

        if (! $roleName) {
            throw CommonException::fromMessage("Role not found");
        }

        return $roleName;
    }
}

if (! function_exists("escape_like")) {
    function escape_like(string $value, string $char = '\\'): string
    {
        return str_replace(
            [$char, '%', '_'],
            [$char . $char, $char . '%', $char . '_'],
            $value
        );
    }
}

if (! function_exists("keyword_search")) {
    function keyword_search($query, $keyword, $columns, $relations = [])
    {
        $keyword_formatted = '%' . escape_like($keyword) . '%';

        $query->where(function ($query) use ($keyword_formatted, $columns, $relations) {
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
        });

        return $query;
    }
}

// if (! function_exists("filter_by_relationship")) {
//     function filter_by_relationship($query, $relation, $column, $value)
//     {
//         return $query->whereHas($relation, function ($query) use ($column, $value) {
//             $query->when(
//                 is_array($value),
//                 fn($q) => $q->whereIn($column, $value),
//                 fn($q) => $q->where($column, $value)
//             );
//         });
//     }
// }

if (! function_exists("filter_by_relationship")) {
    function filter_by_relationship($query, $relation, $column, $value)
    {
        return $query->whereHas($relation, function ($q) use ($column, $value) {
            // Laravel automatically aliases the related table
            $from = $q->getQuery()->from;

            if (str_contains(strtolower($from), ' as ')) {
                // Split on ' as ' (case-insensitive)
                $parts = preg_split('/\s+as\s+/i', $from);
                $alias = $parts[1]; // "laravel_reserved_0"
            } else {
                // No alias, just table name
                $alias = $from; // "lyre_facet_values"
            }

            // If column has a dot (table.column), replace table with alias
            if (str_contains($column, '.')) {
                [$table, $col] = explode('.', $column, 2);
                $column = "$alias.$col";
            }

            $q->when(
                is_array($value),
                fn($q2) => $q2->whereIn($column, $value),
                fn($q2) => $q2->where($column, $value)
            );
        });
    }
}


if (! function_exists("filter_by_range")) {
    /**
     * Filter by range on related models, supporting nested relationships
     * Handles BelongsTo, HasMany, and HasManyThrough relationships
     * 
     * Example: filter_by_range($query, 'variant.userProductVariants.prices', 'price', 100, 500)
     * This will return products where ANY variant has ANY userProductVariant with ANY price in the range [100, 500]
     */
    function filter_by_range($query, $relationPath, $column, $value1, $value2)
    {
        $relations = explode('.', $relationPath);
        $finalColumn = null;

        // If column has a table prefix, extract it
        if (strpos($column, '.') !== false) {
            [$table, $finalColumn] = explode('.', $column, 2);
        } else {
            $finalColumn = $column;
        }

        // Build nested whereHas for each relationship level
        return build_nested_range_filter($query, $relations, $finalColumn, $value1, $value2);
    }
}


if (! function_exists("build_nested_range_filter")) {
    /**
     * Recursively build nested whereHas queries for relationship chains
     */
    function build_nested_range_filter($query, $relations, $column, $value1, $value2, $depth = 0)
    {
        if (empty($relations)) {
            return $query;
        }

        $relation = array_shift($relations);

        if (empty($relations)) {
            // This is the final relationship level - apply the range filter here
            return $query->whereHas($relation, function ($q) use ($column, $value1, $value2) {
                // Get the actual table name/alias from the query
                $from = $q->getQuery()->from;

                if (str_contains(strtolower($from), ' as ')) {
                    $parts = preg_split('/\s+as\s+/i', $from);
                    $alias = $parts[1];
                } else {
                    $alias = $from;
                }

                // If column has a dot (table.column), replace table with alias
                if (str_contains($column, '.')) {
                    [$table, $col] = explode('.', $column, 2);
                    $column = "$alias.$col";
                }

                $q->whereBetween($column, [$value1, $value2]);
            });
        } else {
            // Intermediate relationship level - build nested whereHas
            return $query->whereHas($relation, function ($q) use ($relations, $column, $value1, $value2) {
                build_nested_range_filter($q, $relations, $column, $value1, $value2);
            });
        }
    }
}


if (! function_exists("column_exists")) {
    function column_exists($table, $column)
    {
        return Schema::hasColumn($table, $column);
    }
}

if (! function_exists("is_nan")) {
    function is_nan($value)
    {
        return ! is_numeric($value) || ! ctype_digit((string) $value);
    }
}

if (! function_exists("generate_basic_model_permissions")) {
    function generate_basic_model_permissions()
    {
        $permissions = [];
        $models = get_model_classes();
        foreach ($models as $model) {
            $name = (new $model)->getTable();

            // Required permissions — will throw if missing
            $modelPermissions = [
                get_model_permission_by_prefix($model, 'view-any'),
                get_model_permission_by_prefix($model, 'view'),
                get_model_permission_by_prefix($model, 'create'),
                get_model_permission_by_prefix($model, 'update'),
                get_model_permission_by_prefix($model, 'delete'),
            ];

            // Optional permissions — skip if not found
            foreach (['restore', 'force-delete'] as $optional) {
                try {
                    $modelPermissions[] = get_model_permission_by_prefix($model, $optional);
                } catch (\Throwable $th) {
                    logger("Optional permission missing: {$optional} for model {$model}");
                }
            }

            $permissions[$name] = $modelPermissions;
        }
        return $permissions;
    }
}

if (! function_exists("get_permissions_from_types")) {
    function get_permissions_from_types($typesToInclude)
    {
        $permissions = [];
        $allPermissions = generate_basic_model_permissions();
        foreach ($typesToInclude as $key => $type) {
            if (is_array($type)) {
                $typeModel = get_model_class_from_table_name($key);
                $typePermissions = [];
                foreach ($type as $permissionPrefix) {
                    $typePermissions[] = get_model_permission_by_prefix($typeModel, $permissionPrefix);
                }
                $permissions = array_merge($permissions, $typePermissions);
            } elseif (is_string($type)) {
                $permissions = array_merge($permissions, $allPermissions[$type]);
            }
        }
        return $permissions;
    }
}

function get_model_class_from_table_name($table_name)
{
    $allModels = get_model_classes();
    foreach ($allModels as $model) {
        if ((new $model)->getTable() === $table_name) {
            return $model;
        }
    }
    throw CommonException::fromMessage("Model with table name {$table_name} not found");
}

if (! function_exists("generate_basic_model_response_codes")) {
    function generate_basic_model_response_codes()
    {
        $responseCodes = [];
        $responseCode  = 10001;
        $modelClasses  = get_model_classes();
        foreach ($modelClasses as $modelClass) {
            if (method_exists($modelClass, 'generateConfig')) {
                $config     = $modelClass::generateConfig();
                $pluralName = $config['table'];
                $name       = Pluralizer::singular($pluralName);
                $responseCodes += [
                    $responseCode++ => "get-{$pluralName}",
                    $responseCode++ => "find-{$name}",
                    $responseCode++ => "create-{$name}",
                    $responseCode++ => "update-{$name}",
                    $responseCode++ => "destroy-{$name}",
                    $responseCode++ => "restore-{$name}",
                ];
            }
        }
        return $responseCodes;
    }
}

if (! function_exists('get_response_code')) {
    function get_response_code($response)
    {
        $staticCodes = config('response-codes');
        $dynamicCodes = generate_basic_model_response_codes();
        $responseCodes = $staticCodes + $dynamicCodes;
        $code           = array_search($response, $responseCodes);
        return $code ?? $responseCodes[0000];
    }
}

if (! function_exists('get_status_code')) {
    function get_status_code($status, $model)
    {
        $configPath = config("models.{$model->getTable()}.status") ?? 'constant.status';
        $config     = config($configPath);
        if (! $config) {
            throw CommonException::fromMessage("Status config not found for model {$model->getTable()}");
        }
        if (! is_array($config)) {
            throw CommonException::fromMessage("Status config must be an array");
        }
        if (is_array_associative($config)) {
            $code = ($config[$status] ?? throw CommonException::fromMessage("Status `{$status}` not found for model {$model->getTable()}"));
        } else {
            $code = in_array($status, $config) ? $status : throw CommonException::fromMessage("Status `{$status}` not found for model {$model->getTable()}");
        }
        return $code;
    }
}

if (! function_exists('get_transaction_type_name')) {
    function get_transaction_type_name($type)
    {
        $types                 = config('constant.transaction.types');
        $transaction_type_name = null;
        foreach ($types as $key => $transactionType) {
            if ($transactionType['reference'] == $type) {
                $transaction_type_name = $key;
            }
        }
        if (! $transaction_type_name) {
            throw CommonException::fromMessage("Transaction type {$type} not found");
        }
        return $transaction_type_name;
    }
}

if (! function_exists("get_delivery_time_days")) {
    function get_delivery_time_days($deliveryTime)
    {
        $conversionMap = [
            'one day'      => 1,
            'two days'     => 2,
            'three days'   => 3,
            'one week'     => 7,
            'two weeks'    => 14,
            'three weeks'  => 21,
            'one month'    => 30, // Assuming an average of 30 days in a month
            'two months'   => 60,
            'three months' => 90,
            'six months'   => 180,
            'one year'     => 365, // Assuming a non-leap year
        ];
        return isset($conversionMap[$deliveryTime]) ? $conversionMap[$deliveryTime] : null;
    }
}

if (! function_exists("format_price")) {
    function format_price($price, $locale = "en_KE", $currency = "KES")
    {
        $fmt             = numfmt_create($locale, NumberFormatter::CURRENCY);
        $formatted_price = numfmt_format_currency($fmt, $price, $currency);
        $formatted_price = explode('.', $formatted_price)[0];
        return $formatted_price;
    }
}

if (! function_exists("is_array_associative")) {
    function is_array_associative($array)
    {
        if (! is_array($array)) {
            throw CommonException::fromMessage("Argument must be an array");
        }
        $keys = array_keys($array);
        return array_keys($keys) !== $keys;
    }
}

if (! function_exists("get_all_tables")) {
    function get_all_tables()
    {
        $database = config('database.default');
        switch ($database) {
            case 'mysql':
                $tables = DB::select('SHOW TABLES');
                return array_map(function ($table) {
                    return array_values((array) $table)[0];
                }, $tables);
            case 'pgsql':
                $tables = DB::select('SELECT table_name FROM information_schema.tables WHERE table_schema = \'public\'');
                return array_map(function ($table) {
                    return $table->table_name;
                }, $tables);
            case 'sqlite':
                $tables = DB::select('SELECT name FROM sqlite_master WHERE type = \'table\'');
                return array_map(function ($table) {
                    return $table->name;
                }, $tables);
            default:
                throw new \Exception('Unsupported database driver');
        }
    }
}

if (! function_exists('get_join_details')) {
    function get_join_details($relationName, $model)
    {
        $relation = $model->$relationName();

        if ($relation instanceof \Illuminate\Database\Eloquent\Relations\BelongsTo) {
            $relatedModel = $relation->getRelated();
            return [
                'foreignKey'   => $relation->getForeignKeyName(),
                'relatedKey'   => $relation->getOwnerKeyName(),
                'relatedTable' => $relatedModel->getTable(),
            ];
        } elseif ($relation instanceof \Illuminate\Database\Eloquent\Relations\HasMany) {
            $relatedModel = $relation->getRelated();
            return [
                'foreignKey'   => $relation->getForeignKeyName(),
                'relatedKey'   => $relation->getLocalKeyName(),
                'relatedTable' => $relatedModel->getTable(),
            ];
        }

        return null;
    }
}

if (! function_exists('retrieve_json_contents')) {
    function retrieve_json_contents($filePath)
    {
        $data = [];
        if (file_exists($filePath)) {
            $jsonData = file_get_contents($filePath);
            $data     = json_decode($jsonData, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw CommonException::fromMessage('Error parsing JSON file: ' . json_last_error_msg());
            }
        } else {
            throw CommonException::fromMessage('JSON file not found.');
        }
        return $data;
    }
}

if (! function_exists('clean_str')) {
    function clean_str($string, $lowercase = true, $replacer = '-')
    {
        $string = str_replace(' ', $replacer, $string);          // Replaces all spaces with hyphens.
        $string = preg_replace('/[^A-Za-z0-9\-]/', '', $string); // Removes special chars.
        if ($lowercase) {
            $string = strtolower($string); // Converts string to lowercase.
        }
        return $string;
    }
}

if (! function_exists('register_global_observers')) {
    function register_global_observers(string $modelsBaseNamespace, ?string $observersNamespace = null)
    {
        $MODELS = collect(get_model_classes($modelsBaseNamespace));

        $derivedNamespace = $observersNamespace ?? str_replace('Models', 'Observers', $modelsBaseNamespace);
        $derivedPath = get_namespace_path($derivedNamespace);

        $defaultNamespace = "App\\Observers";
        $defaultPath = app_path("Observers");

        $activeNamespace = file_exists($derivedPath) ? $derivedNamespace : $defaultNamespace;
        $activePath = file_exists($derivedPath) ? $derivedPath : $defaultPath;

        // static $registered = [];

        if (file_exists($activePath)) {
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($activePath),
                RecursiveIteratorIterator::LEAVES_ONLY
            );

            foreach ($iterator as $file) {
                if (
                    !$file->isFile() ||
                    $file->getExtension() !== 'php' ||
                    $file->getFilename() === 'BaseObserver.php'
                ) {
                    continue;
                }

                $relativePath = str_replace($activePath . DIRECTORY_SEPARATOR, '', $file->getPathname());
                $classPath = str_replace(
                    [DIRECTORY_SEPARATOR, '.php'],
                    ['\\', ''],
                    $relativePath
                );

                $observerClass = $activeNamespace . '\\' . $classPath;
                $observerName  = class_basename($observerClass);
                $modelName     = str_replace('Observer', '', $observerName);

                if (isset($MODELS[$modelName]) && class_exists($observerClass)) {
                    $modelClass = $MODELS[$modelName];
                    // TODO: Kigathi - August 13 2025 - Implement indempotence to ensure observers are not registered multiple times
                    // if (!isset($registered[$modelClass][$observerClass])) {
                    $modelClass::observe($observerClass);
                    $MODELS->forget($modelName);
                    //     $registered[$modelClass][$observerClass] = true;
                    // }
                }
            }
        }

        foreach ($MODELS as $MODEL) {
            // if (!isset($registered[$modelClass][$observerClass])) {
            $MODEL::observe(\Lyre\Observer::class);
            //     $registered[$modelClass][$observerClass] = true;
            // }
        }
    }
}

if (! function_exists('register_repositories')) {
    function register_repositories($app, string $repositoriesBaseNamespace, string $contractsBaseNamespace)
    {
        $repositoriesPath = get_namespace_path($repositoriesBaseNamespace);
        $contractsPath = get_namespace_path($contractsBaseNamespace);

        if (! file_exists($repositoriesPath)) {
            \Illuminate\Support\Facades\File::makeDirectory($repositoriesPath);
        }

        if (! file_exists($contractsPath)) {
            \Illuminate\Support\Facades\File::makeDirectory($contractsPath);
        }

        // NOTE: Kigathi - September 2 2025 - Logic to make repository helpers discoverable
        // $packageRoot = dirname(__DIR__, 2);
        // $helpersFilePath = $packageRoot . '/_ide_repositories.php';
        // $helpersFileContent = "<?php\n\n/**\n * AUTO-GENERATED by register_repositories()\n * Do NOT edit manually. Add to .gitignore.\n */\n\n";

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($repositoriesPath)
        );

        // $added = [];

        foreach ($iterator as $file) {
            if (!$file->isFile()) continue;

            $fileName = $file->getFilename();

            // TODO: Kigathi - April 21 2025 - Allow overriding the Repository.php by introducing a BaseRepository.php file at the repositoryPath
            if (!Str::endsWith($fileName, 'Repository.php') || $fileName === 'BaseRepository.php') {
                continue;
            }

            // Get relative path from the Repositories directory
            $relativePath = Str::after($file->getPathname(), $repositoriesPath . DIRECTORY_SEPARATOR);
            $namespacePath = str_replace(['/', '\\'], '\\', Str::replaceLast('.php', '', $relativePath));

            // Interface path must match the same relative structure
            $interfaceNamespace = $contractsBaseNamespace . '\\' . $namespacePath . 'Interface';
            $implementationNamespace = $repositoriesBaseNamespace . '\\' . $namespacePath;

            // Interface file must exist
            $interfaceFilePath = $contractsPath . DIRECTORY_SEPARATOR . Str::replaceLast('Repository.php', 'RepositoryInterface.php', $relativePath);

            $helperName = Str::of($namespacePath)
                ->camel();

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

                //                 if (! in_array($helperName, $added, true)) {
                //                     $added[] = $helperName;

                //                     $helpersFileContent .= <<<PHP
                // /**
                //  * @return \\{$interfaceNamespace}
                //  */
                // function {$helperName}() {
                //     return app('{$interfaceNamespace}');
                // }
                // \n
                // PHP;
                //                 }
            }
        }

        // if (! file_exists($helpersFilePath) || md5_file($helpersFilePath) !== md5($helpersFileContent)) {
        //     file_put_contents($helpersFilePath, $helpersFileContent);
        // }
    }
}

if (!function_exists('tenant')) {
    function tenant()
    {
        return app()->bound('tenant') ? app('tenant') : null;
    }
}

if (!function_exists('get_table_foreign_columns')) {
    function get_table_foreign_columns($table, $schema = null)
    {
        $driver = DB::getDriverName();

        if ($driver === 'pgsql') {
            $schema = $schema ?? 'public';

            $foreignKeys = DB::select("
                SELECT DISTINCT
                    kcu.column_name,
                    ccu.table_name AS foreign_table,
                    ccu.column_name AS foreign_column
                FROM
                    information_schema.table_constraints AS tc
                JOIN information_schema.key_column_usage AS kcu
                  ON tc.constraint_name = kcu.constraint_name
                 AND tc.constraint_schema = kcu.constraint_schema
                JOIN information_schema.constraint_column_usage AS ccu
                  ON ccu.constraint_name = tc.constraint_name
                 AND ccu.constraint_schema = tc.constraint_schema
                WHERE tc.constraint_type = 'FOREIGN KEY'
                  AND tc.table_name = ?
                  AND tc.table_schema = ?
            ", [$table, $schema]);
        } elseif ($driver === 'mysql') {
            $schema = $schema ?? DB::getDatabaseName();

            $foreignKeys = DB::select("
                SELECT DISTINCT
                    kcu.COLUMN_NAME AS column_name,
                    kcu.REFERENCED_TABLE_NAME AS foreign_table,
                    kcu.REFERENCED_COLUMN_NAME AS foreign_column
                FROM
                    information_schema.KEY_COLUMN_USAGE kcu
                WHERE
                    kcu.TABLE_NAME = ?
                    AND kcu.TABLE_SCHEMA = ?
                    AND kcu.REFERENCED_TABLE_NAME IS NOT NULL
            ", [$table, $schema]);
        } else {
            throw new \Exception("Unsupported driver: {$driver}");
        }

        $columns = [];
        foreach ($foreignKeys as $fk) {
            $columns[] = $fk->column_name;
        }

        return array_values(array_unique($columns));
    }
}


if (!function_exists('resolve_from_resource')) {
    function resolve_from_resource($resource)
    {
        // TODO: Kigathi - September 5 2025 - Should implement __destruct on repository to ensure we can add a ->resolve chain to do this automatically
        return json_decode(response()->json($resource)->getContent());
    }
}

/**
 * Get the user model.
 *
 * @return string
 */
if (!function_exists('get_user_model')) {
    function get_user_model()
    {
        return config('lyre.user_model', \App\Models\User::class);
    }
}

if (! function_exists('is_json')) {
    function is_json($string): bool
    {
        if (! is_string($string)) {
            return false;
        }
        json_decode($string);
        return json_last_error() === JSON_ERROR_NONE;
    }
}

if (!function_exists('fill_template')) {
    function fill_template(string $template, $model): string
    {
        return preg_replace_callback('/{{\s*([\w\.]+)\s*}}/', function ($matches) use ($model) {
            $path = $matches[1];

            // Handle nested paths like user.name
            $keys = explode('.', $path);

            $value = $model;

            foreach ($keys as $key) {
                if (is_array($value) && array_key_exists($key, $value)) {
                    $value = $value[$key];
                } elseif (is_object($value) && isset($value->{$key})) {
                    $value = $value->{$key};
                } else {
                    // return original placeholder if not found
                    return $matches[0];
                }
            }

            return (string) $value;
        }, $template);
    }
}
