<?php

namespace Lyre\Strings\Repository\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Schema;

/**
 * Handles request preparation and query building from HTTP requests.
 * 
 * This concern provides methods for preparing queries based on
 * HTTP request parameters and applying various filters and operations.
 * 
 * @package Lyre\Strings\Repository\Concerns
 */
trait HandlesRequestPreparation
{
    /**
     * Prepare query based on request parameters.
     *
     * @param Builder $query
     * @return Builder
     */
    public function prepareQuery($query): Builder
    {
        /**
         * Skip preparation if not the current request model or if explicitly skipped
         */
        if (!(Config::get('request-model') && Config::get('request-model')->getClassName() == $this->model->getClassName()) || Config::get('skip-prepare')) {
            return $query;
        }

        $originQuery = clone $query;
        if (!$this->withInactive) {
            $query = $this->filterActive($query);
        }
        if (!$this->orderByColumn) {
            $query = $query->orderBy('created_at', 'desc');
        }

        $requestQueries = request()->query();
        if (empty($requestQueries)) {
            return $query;
        }

        $query = $originQuery;

        // Handle status filtering
        if (array_key_exists('status', $requestQueries) && $requestQueries['status']) {
            $statuses = explode(',', $requestQueries['status']);
            $query->where(function ($query) use ($statuses) {
                $query->where($this->model->getTable() . '.status', get_status_code($statuses[0], $this->model));
                if (count($statuses) > 1) {
                    foreach ($statuses as $key => $status) {
                        if ($key === 0) {
                            continue;
                        } else {
                            $query->orWhere($this->model->getTable() . '.status', get_status_code($status, $this->model));
                        }
                    }
                }
            });
        } else {
            $query = $this->filterActive($query);
        }

        // Handle relations loading
        if (array_key_exists('with', $requestQueries) && $requestQueries['with']) {
            $relationships = explode(',', $requestQueries['with']);
            $validRelationships = $this->filterValidRelationships($relationships);
            $this->relations = [...$this->relations, ...$validRelationships];
        }

        // Handle search functionality
        if (array_key_exists('search', $requestQueries) && $requestQueries['search']) {
            $result['search'] = $requestQueries['search'];
            if (array_key_exists('search-relations', $requestQueries) && $requestQueries['search-relations']) {
                $parts = explode(",", $requestQueries['search-relations']);
                $preliminaryResult = [];
                for ($i = 0; $i < count($parts); $i += 2) {
                    $relation = $parts[$i];
                    $key = $parts[$i + 1];
                    $preliminaryResult[$relation] = [$key];
                }
                $result['relations'] = $preliminaryResult;
            }
            $this->searchQuery($result);
        }

        // Handle pagination
        if (array_key_exists('unpaginated', $requestQueries) && $requestQueries['unpaginated'] == 'true') {
            $this->unPaginate();
        }

        // Handle limit and offset
        if (array_key_exists('limit', $requestQueries) && $requestQueries['limit'] && is_numeric($requestQueries['limit'])) {
            $this->limit((int) $requestQueries['limit']);
        }

        if (array_key_exists('offset', $requestQueries) && $requestQueries['offset'] && is_numeric($requestQueries['offset'])) {
            $this->offset((int) $requestQueries['offset']);
        }

        // Handle relation filtering
        $this->handleRelationFiltering($requestQueries);

        // Handle range filtering
        $this->handleRangeFiltering($requestQueries);

        // Handle column filtering
        $this->handleColumnFiltering($requestQueries);

        // Handle ordering
        $this->handleOrdering($requestQueries);

        // Handle pagination parameters
        $this->handlePaginationParameters($requestQueries);

        // Handle additional filters
        $this->handleAdditionalFilters($requestQueries);

        return $query;
    }

    /**
     * Handle relation filtering from request.
     *
     * @param array $requestQueries
     * @return void
     */
    protected function handleRelationFiltering(array $requestQueries): void
    {
        // Handle relation filtering
        if (array_key_exists('relation', $requestQueries) && $requestQueries['relation']) {
            $parts = explode(",", $requestQueries['relation']);
            $result = [];
            for ($i = 0; $i < count($parts); $i += 2) {
                $relationPath = $parts[$i];
                $value = $parts[$i + 1];

                if ($relationPath) {
                    $segments = explode('.', $relationPath);
                    $relation = array_shift($segments);

                    $relatedModel = $this->model->{$relation}();
                    $relatedModelClass = get_class($relatedModel->getRelated());

                    foreach ($segments as $segment) {
                        $relatedModel = (new $relatedModelClass)->{$segment}();
                        $relatedModelClass = get_class($relatedModel->getRelated());
                    }

                    $idColumn = $relatedModelClass::ID_COLUMN;
                    $idTable = (new $relatedModelClass)->getTable();

                    $result[$relationPath] = [
                        'column' => "$idTable.$idColumn",
                        'value' => $value,
                        'relation' => $relationPath
                    ];
                }
            }
            $this->relationFilters += $result;
        }

        // Handle relation_in filtering
        if (array_key_exists('relation_in', $requestQueries) && $requestQueries['relation_in']) {
            $parts = explode(",", $requestQueries['relation_in']);
            $relation = array_shift($parts);
            $relatedModel = $this->model->{$relation}();
            $relatedModelClass = get_class($relatedModel->getRelated());
            $idColumn = $relatedModelClass::ID_COLUMN;
            $idTable = (new $relatedModelClass)->getTable();
            $filter = [
                'column' => "$idTable.$idColumn",
                'value' => $parts,
            ];
            $this->relationFilters += [$relation => $filter];
        }
    }

    /**
     * Handle range filtering from request.
     *
     * @param array $requestQueries
     * @return void
     */
    protected function handleRangeFiltering(array $requestQueries): void
    {
        if (array_key_exists('range', $requestQueries) && $requestQueries['range']) {
            $parts = explode(",", $requestQueries['range']);
            $result = [];
            for ($i = 0; $i < count($parts); $i += 3) {
                $columnType = Schema::getColumnType($this->model->getTable(), $parts[$i]);

                $value1 = $parts[$i + 1];
                $value2 = $parts[$i + 2];

                switch ($columnType) {
                    case 'integer':
                        $value1 = (int) $value1;
                        $value2 = (int) $value2;
                        break;
                    case 'float':
                    case 'double':
                    case 'decimal':
                        $value1 = (float) $value1;
                        $value2 = (float) $value2;
                        break;
                    case 'date':
                    case 'datetime':
                    case 'timestamp':
                        $value1 = \Carbon\Carbon::parse($value1)->startOfDay();
                        $value2 = \Carbon\Carbon::parse($value2)->endOfDay();
                        break;
                    default:
                        break;
                }
                $result[$parts[$i]] = [$value1, $value2];
            }
            $this->rangeFilters = $result;
        }
    }

    /**
     * Handle column filtering from request.
     *
     * @param array $requestQueries
     * @return void
     */
    protected function handleColumnFiltering(array $requestQueries): void
    {
        if (array_key_exists('filter', $requestQueries) && $requestQueries['filter']) {
            $parts = explode(',', $requestQueries['filter']);
            $result = [];
            for ($i = 0; $i < count($parts); $i += 2) {
                $result[$parts[$i]] = $parts[$i + 1];
            }
            $this->columnFilters = $result;
        }
    }

    /**
     * Handle ordering from request.
     *
     * @param array $requestQueries
     * @return void
     */
    protected function handleOrdering(array $requestQueries): void
    {
        if (array_key_exists('order', $requestQueries) && $requestQueries['order']) {
            $this->orderByColumn = $requestQueries['order'] ? explode(',', $requestQueries['order'])[0] : 'created_at';
            $this->orderByOrder = $requestQueries['order'] ? explode(',', $requestQueries['order'])[1] : 'desc';
        }
    }

    /**
     * Handle pagination parameters from request.
     *
     * @param array $requestQueries
     * @return void
     */
    protected function handlePaginationParameters(array $requestQueries): void
    {
        if (array_key_exists('per_page', $requestQueries) && $requestQueries['per_page']) {
            $perPage = (int) $requestQueries['per_page'];
            $this->paginate($perPage);
        }

        if (array_key_exists('page', $requestQueries) && $requestQueries['page']) {
            $perPage = array_key_exists('per_page', $requestQueries) && $requestQueries['per_page'] ? (int) $requestQueries['per_page'] : $this->perPage;
            $page = (int) $requestQueries['page'];
            $this->paginate($perPage, $page);
        }
    }

    /**
     * Handle additional filters from request.
     *
     * @param array $requestQueries
     * @return void
     */
    protected function handleAdditionalFilters(array $requestQueries): void
    {
        if (array_key_exists('startswith', $requestQueries) && $requestQueries['startswith']) {
            $startsWith = $requestQueries['startswith'];
            $this->startsWith($startsWith);
        }

        if (array_key_exists('withcount', $requestQueries) && $requestQueries['withcount']) {
            $withCount = explode(',', $requestQueries['withcount']);
            $this->withCount($withCount);
        }

        if (array_key_exists('wherenull', $requestQueries) && $requestQueries['wherenull']) {
            $whereNull = explode(',', $requestQueries['wherenull']);
            $this->whereNull($whereNull);
        }

        if (array_key_exists('doesnthave', $requestQueries) && $requestQueries['doesnthave']) {
            $doesntHave = explode(',', $requestQueries['doesnthave']);
            $this->doesntHave($doesntHave);
        }

        if (array_key_exists('random', $requestQueries) && $requestQueries['random']) {
            $this->random();
        }

        if (array_key_exists('first', $requestQueries) && $requestQueries['first']) {
            $this->first();
        }

        // Handle dynamic relation filtering
        if (count($requestQueries) > 0) {
            foreach ($requestQueries as $key => $value) {
                if (!$key == "search-relations") {
                    $relatedModel = $this->model->{$key}();
                    $relatedModelClass = get_class($relatedModel->getRelated());
                    $idColumn = $relatedModelClass::ID_COLUMN;
                    $idTable = (new $relatedModelClass)->getTable();
                    $result[$key] = [
                        'column' => "$idTable.$idColumn",
                        'value' => $value,
                    ];
                    $this->relationFilters += $result;
                }
            }
        }
    }

    /**
     * Filter active records.
     *
     * @param Builder $query
     * @return Builder
     */
    public function filterActive($query): Builder
    {
        // TODO: Implement active filtering logic
        return $query;
    }

    /**
     * Filter valid relationships.
     *
     * @param array $relationships
     * @return array
     */
    private function filterValidRelationships(array $relationships): array
    {
        $validRelationships = [];

        foreach ($relationships as $relationship) {
            $parts = explode('.', $relationship);
            $model = $this->model;

            $isValid = true;
            foreach ($parts as $part) {
                $available = $model::getModelRelationships();
                if (!isset($available[$part])) {
                    $isValid = false;
                    break;
                }

                // Drill down into the next related model
                $relatedClass = $available[$part];
                $model = new $relatedClass;
            }

            if ($isValid) {
                $validRelationships[] = $relationship;
            }
        }

        return $validRelationships;
    }
}
