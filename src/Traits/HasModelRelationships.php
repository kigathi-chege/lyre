<?php

namespace Lyre\Traits;

use Illuminate\Database\Eloquent\Model;

trait HasModelRelationships
{
    /**
     * Build relation filter array for a model based on request queries
     */
    public function buildRelationFilters(array $requestQueries, ?Model $model = null): array
    {
        $model = $model ?? $this; // use $this if model is not passed
        $result = [];

        if (count($requestQueries) === 0) {
            return $result;
        }

        foreach ($requestQueries as $key => $value) {

            $modelRelationships = $model->getModelRelationships();

            if (!empty($modelRelationships[$key]) && array_key_exists($key, $modelRelationships)) {

                if (!$model->relationLoaded($key)) {
                    $model->load($key);
                }

                $relatedModel = $model->{$key}();
                $relatedModelClass = get_class($relatedModel->getRelated());

                $relatedModelIDColumn = $relatedModelClass::ID_COLUMN;
                $relatedModelTable = (new $relatedModelClass)->getTable();

                $result[$key] = [
                    'column' => "$relatedModelTable.$relatedModelIDColumn",
                    'value' => $value,
                ];
            }
        }

        return $result;
    }
}
