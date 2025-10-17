<?php

namespace Lyre\Strings\Resource\Concerns;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

/**
 * Handles serialization of model data to array format.
 * 
 * This concern provides methods for transforming model instances
 * into serializable arrays with proper column handling and metadata.
 * 
 * @package Lyre\Strings\Resource\Concerns
 */
trait HandlesSerialization
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array
     */
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

    /**
     * Get serializable columns for a model.
     *
     * @param mixed $resource
     * @return \Illuminate\Support\Collection
     */
    public static function serializableColumns($resource)
    {
        $custom = $resource->resolveCustomColumns();
        $fillableColumns = $resource->getFillableAttributes();
        $columnListing = Schema::getColumnListing($resource->getTable());
        $baseColumns = array_merge($fillableColumns, $columnListing, $custom);

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

        $foreignColumns = get_table_foreign_columns($resource->getTable());

        $filteredBaseColumns = array_diff_key(
            $associatedBaseColumns,
            array_flip($foreignColumns)
        );

        $columns = $filteredBaseColumns + [
            "id" => get_model_id_column($resource),
            "name" => get_model_name_column($resource),
            "status" => "status",
        ];

        return collect($columns);
    }

    /**
     * Get column metadata for transformation.
     *
     * @param mixed $resource
     * @return array
     */
    public static function columnMeta($resource = null): array
    {
        return [];
    }
}
