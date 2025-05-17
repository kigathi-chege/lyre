<?php

namespace Lyre;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Schema;

class Resource extends JsonResource
{
    protected $request;

    public function __construct($resource)
    {
        parent::__construct($resource);
    }

    public function toArray(Request $request): array
    {
        $this->request = $request;
        $modelResource = get_model_resource($this->resource);
        $columnMeta = $modelResource::columnMeta($this->resource);
        $baseData = $modelResource::serializableColumns($this->resource)
            ->mapWithKeys(function ($column, $attribute) use ($columnMeta) {
                if (array_key_exists($attribute, $columnMeta)) {
                    return [$attribute => $columnMeta[$attribute]($this->{$column})];
                }
                return [$attribute => $this->{$column}];
            })
            ->toArray();
        $baseData = $this->loadRelations($modelResource, $baseData, $this->resource);
        $baseData = $this->pivotRelations($modelResource, $baseData);
        $result = array_filter($baseData, function ($value) {
            return $value !== null;
        });
        return $result;
    }

    public static function prepareCollection($collection, $resource, $paginate = true)
    {
        if (!$paginate) {
            return $resource::collection($collection);
        }

        $paginationData = [
            'current_page' => $collection->currentPage(),
            'last_page' => $collection->lastPage(),
            'per_page' => $collection->perPage(),
            'total' => $collection->total(),
            'links' => $collection->links()->elements,
        ];

        return ['data' => $resource::collection($collection), 'meta' => $paginationData];
    }

    public function loadRelations($resource, array $baseData, $modelInstance): array
    {
        $allowedRelations = $resource::loadResources($modelInstance);
        if (!empty($allowedRelations)) {
            foreach ($allowedRelations as $relation => $resource) {
                if ($this->relationLoaded($relation)) {
                    // TODO: Kigathi - March 14 2024 - Confirm that $this->resource->$relation(); works for all situations.
                    $relationObj = $this->resource->$relation();
                    $relationData = $this->whenLoaded($relation);
                    if ($relationData !== null) {
                        // TODO: Kigathi - December 19 2023 - Find an easier and more efficient way to implement this
                        if ($relationObj instanceof \Illuminate\Database\Eloquent\Relations\Relation) {
                            if (
                                $relationObj instanceof \Illuminate\Database\Eloquent\Relations\HasMany  ||
                                $relationObj instanceof \Illuminate\Database\Eloquent\Relations\BelongsToMany  ||
                                $relationObj instanceof \Illuminate\Database\Eloquent\Relations\HasManyThrough  ||
                                $relationObj instanceof \Illuminate\Database\Eloquent\Relations\MorphMany  ||
                                $relationObj instanceof \Illuminate\Database\Eloquent\Relations\MorphToMany
                            ) {
                                // TODO: Kigathi - December 24 2023 - Return paginated collection results
                                $baseData[$relation] = $resource::collection($relationData);
                            } else {
                                $baseData[$relation] = new $resource($relationData);
                            }
                        }
                    }
                }
            }
        }
        return $baseData;
    }

    public function pivotRelations($resource, array $baseData): array
    {
        $allowedRelations = $resource::pivotResources();
        if (!empty($allowedRelations)) {
            foreach ($allowedRelations as $relation => $resource) {
                $baseData[$relation] = $resource::collection($this->whenPivotLoaded($relation, fn() => $this->pivot));
            }
        }
        return $baseData;
    }

    public static function serializableColumns($resource)
    {
        $custom = $resource->getCustomColumns();

        $baseColumns = $resource->getFillableAttributes();

        if (empty($baseColumns)) {
            $baseColumns = Schema::getColumnListing($resource->getTable());
        }

        $baseColumns = array_merge($baseColumns, $custom);

        $columnsToSet = $resource->getIncluded();
        $columnsToUnset = $resource->getExcluded();

        if (!empty($columnsToSet)) {
            $baseColumns = array_merge($baseColumns, $columnsToSet);
        }

        if (!empty($columnsToUnset)) {
            foreach ($columnsToUnset as $column) {
                if (($key = array_search($column, $baseColumns)) !== false) {
                    unset($baseColumns[$key]);
                }
            }
        }

        $associatedBaseColumns = array_combine($baseColumns, $baseColumns);

        $filteredBaseColumns = [];
        foreach ($associatedBaseColumns as $key => $value) {
            if (!preg_match('/_id$/', $key)) {
                $filteredBaseColumns[$key] = $value;
            }
        }

        $columns = $filteredBaseColumns + [
            "id" => get_model_id_column($resource),
            "name" => get_model_name_column($resource),
            "status" => "status",
        ];

        return collect($columns);
    }

    public static function columnMeta($resource = null)
    {
        return [];
    }

    public static function loadResources($resource = null): array
    {
        if (!$resource || ($resource instanceof \App\Models\User)) {
            return [];
        }

        return collect($resource->getModelRelationships())->map(function ($value, $key) {
            return $value::generateConfig()['resource'];
        })->filter()->toArray();
    }

    public static function pivotResources(): array
    {
        return [];
    }
}
