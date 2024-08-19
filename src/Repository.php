<?php

namespace Lyre;

use Illuminate\Support\Facades\Schema;
use Lyre\Exceptions\CommonException;
use Lyre\Facades\Lyre;
use Lyre\Interface\RepositoryInterface;

class Repository implements RepositoryInterface
{
    protected $model;
    protected $relations = [];
    protected $columnFilters = [];
    protected $rangeFilters = [];
    protected $relationFilters = [];
    protected $searchQuery = [];
    protected $operations = [];
    protected $paginate = true;
    protected $perPage = 9;
    protected $page = 1;
    protected $methods;
    protected $arguments;
    protected $resource;
    protected $silent = false;
    protected $withInactive = false;
    protected $limit = false;
    protected $orderByColumn = null;
    protected $orderByOrder = 'desc';

    public function __construct($model)
    {
        $this->model = $model;
        $this->methods = get_class_methods($this);
        $this->resource = Lyre::getModelResource($this->model);
    }

    public function all($filterCallback = null, $paginate = true)
    {
        $query = $this->model->query();
        $query = $this->prepareQuery($query);
        $query = $this->linkRelations($query);
        $query = $this->applyColumnFilters($query);
        $query = $this->applyRangeFilters($query);
        $query = $this->applyRelationFilters($query);
        if ($filterCallback !== null && is_callable($filterCallback)) {
            $query = call_user_func($filterCallback, $query);
        }
        $query = $this->performOperations($query);
        $query = $this->search($query);
        $query = $this->order($query);
        $results = $this->limit ? $query->limit($this->limit)->get() : ($paginate && $this->paginate ? $query->paginate($this->perPage ?? 10, ['*'], 'page', $this->page) : $query->get());
        return $this->collectResource($results, $this->limit ? false : $paginate && $this->paginate);
    }

    public function trashed()
    {
        return $this->model->onlyTrashed()->get();
    }

    public function find($arguments, $filterCallback = null)
    {
        $query = $this->model->query();
        $query = $this->prepareQuery($query);
        $query = $this->filter($query, $arguments);
        $query = $this->linkRelations($query);
        if ($filterCallback !== null && is_callable($filterCallback)) {
            $query = call_user_func($filterCallback, $query);
        }
        $query = $this->performOperations($query);
        $resource = $query->first();
        if (!$resource) {
            if ($this->silent) {
                return null;
            }
            throw CommonException::fromMessage("{$this->model->getTable()} not found");
        }
        return $this->resource ? new $this->resource($resource) : $query->first();
    }

    public function latest()
    {
        $latest = $this->model->latest()->first();
        return $this->resource ? new $this->resource($latest) : $latest;
    }

    public function create(array $data)
    {
        $thisModel = $this->model->create($data);
        return $this->resource ? new $this->resource($thisModel) : $thisModel;
    }

    public function firstOrCreate(array $search, array $data = [])
    {
        $thisModel = $this->model->firstOrCreate($search, $data);
        $result = $this->resource ? new $this->resource($thisModel) : $thisModel;
        $result->wasRecentlyCreated = $thisModel->wasRecentlyCreated;
        return $result;
    }

    public function updateOrCreate(array $search, array $data = [])
    {
        $thisModel = $this->model->updateOrCreate($search, $data);
        $result = $this->resource ? new $this->resource($thisModel) : $thisModel;
        $result->wasRecentlyCreated = $thisModel->wasRecentlyCreated;
        return $result;
    }

    public function update(array $data, string $slug, $thisModel = null)
    {
        if (!$thisModel) {
            $thisModel = $this->model->where([Lyre::getModelIdColumn($this->model) => $slug])->first();
            if (!$thisModel) {
                throw CommonException::fromMessage("{$this->model->getTable()} not found");
            }
        }
        $data = array_filter($data);
        if (empty($data)) {
            throw CommonException::fromMessage("Nothing to update!");
        }
        if (isset($data['status'])) {
            $data['status'] = get_status_code($data['status'], $thisModel);
        }
        $thisModel->update($data);
        return $this->resource ? new $this->resource($thisModel) : $thisModel;
    }

    public function delete($slug)
    {
        $thisModel = $this->model->where(["slug" => $slug])->first();
        if (!$thisModel) {
            throw new \Exception("Resource not found", 404);
        }
        $thisModel->delete();
        return $this->resource ? new $this->resource($thisModel) : $thisModel;
    }

    public function relations(array $relations)
    {
        $this->relations = $relations;
        return $this;
    }

    public function paginate(int $perPage, $page = 1)
    {
        $this->perPage = $perPage;
        $this->page = $page;
        return $this;
    }

    public function unPaginate()
    {
        $this->paginate = false;
        return $this;
    }

    public function limit(int $limit)
    {
        $this->limit = $limit;
        return $this;
    }

    public function columnFilters(array $columnFilters)
    {
        $this->columnFilters = $columnFilters;
        return $this;
    }

    public function rangeFilters(array $rangeFilters)
    {
        $this->rangeFilters = $rangeFilters;
        return $this;
    }

    public function relationFilters(array $relationFilters)
    {
        $this->relationFilters = $relationFilters;
        return $this;
    }

    public function silent()
    {
        $this->silent = true;
        return $this;
    }

    public function searchQuery(array $searchQuery)
    {
        $this->searchQuery = $searchQuery;
        /**
         * TODO: Kigathi - April 2 2024
         *
         * Revisit this code to ensure there is a way to retrieve a model's relations from the instance
         * This will enable us to fetch the model's serializable columns from its resource
         * This will in turn remove the necessity for declaring the search columns
         */
        // $relations = isset($this->searchQuery['relations']) ? $this->searchQuery['relations'] : [];
        // if ($relations) {
        //     $this->relations += $relations;
        // }

        return $this;
    }

    public function withInactive()
    {
        $this->withInactive = true;
        return $this;
    }

    public function filter($query, $arguments)
    {
        $arguments = $this->sanitizeArguments($arguments);
        if (!empty($arguments)) {
            $query->where($arguments);
        }
        return $query;
    }

    public function search($query)
    {
        if (!empty($this->searchQuery)) {
            $search = isset($this->searchQuery['search']) ? $this->searchQuery['search'] : null;
            if ($search) {
                $relations = isset($this->searchQuery['relations']) ? $this->searchQuery['relations'] : [];
                if ($relations) {
                    $this->model->load($relations);
                }

                $serializableColumns = $this->resource::serializableColumns()->values()->toArray();
                $query = keyword_search($query, $search, $serializableColumns, $relations);
            }
        }
        return $query;
    }

    public function orderBy(string $column, string $order = 'desc')
    {
        $this->orderByColumn = $column;
        $this->orderByOrder = $order;
        return $this;
    }

    public function order($query)
    {
        if ($this->orderByColumn) {
            $query->orderBy($this->orderByColumn, $this->orderByOrder ?? 'desc');
        }
        return $query;
    }

    public function linkRelations($query)
    {
        if (!empty($this->relations)) {
            $query->with($this->relations);
        }
        return $query;
    }

    public function applyColumnFilters($query)
    {
        if (!empty($this->columnFilters)) {
            foreach ($this->columnFilters as $key => $value) {
                if (is_array($value)) {
                    $query = $query->whereIn($key, $value);
                } else {
                    $query = $query->where($key, $value);
                }
            }
        }
        return $query;
    }

    public function applyRangeFilters($query)
    {
        if (!empty($this->rangeFilters)) {
            if (is_array($this->rangeFilters)) {
                foreach ($this->rangeFilters as $key => $value) {
                    $query = $query->whereBetween($key, $value);
                }
            }
        }
        return $query;
    }

    public function applyRelationFilters($query)
    {
        if (!empty($this->relationFilters)) {
            foreach ($this->relationFilters as $relation => $filter) {
                $columnValue = explode(',', $filter);
                $query = filter_by_relationship($query, $relation, $columnValue[0], $columnValue[1]);
            }
        }
        return $query;
    }

    public function sanitizeArguments($arguments)
    {
        $modelFillables = $this->model->getFillableAttributes();
        $allowedColumns = $this->resource::serializableColumns($this->model);
        $allowedColumnKeys = array_unique(array_merge($allowedColumns->keys()->toArray(), $modelFillables));
        $sanitizedArguments = collect($arguments)->only($allowedColumnKeys)->all();
        $preparedArguments = $this->prepareArguments($this->allKnownKeys($modelFillables, $allowedColumns->toArray()), $sanitizedArguments);
        return $preparedArguments;
    }

    public function allKnownKeys($array1, $array2)
    {
        $result = [];
        foreach ($array1 as $element) {
            if (!array_key_exists($element, $array2)) {
                $result[$element] = $element;
            }
        }
        $result = array_merge($array2, $result);
        return $result;
    }

    public function prepareArguments($keys, $values)
    {
        $result = [];
        foreach ($keys as $key => $newKey) {
            if (isset($values[$key])) {
                $result[$newKey] = $values[$key];
            } else if (isset($values[$newKey])) {
                $result[$newKey] = $values[$newKey];
            }
        }

        return $result;
    }

    public function prepareQuery($query)
    {
        /**
         * Kigathi - February 28 2024
         * Added this line because we need to skip prepare several times
         * This is especially necessary if we are finding to manipulate, and not to return
         * All `find` requests and `all` requests hit this method
         */
        // if (!(Config::get('request-model') && Config::get('request-model')->getClassName() == $this->model->getClassName()) || Config::get('skip-prepare')) {
        //     return $query;
        // }
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

        if (array_key_exists('status', $requestQueries) && $requestQueries['status']) {
            // TODO: Kigathi - July 6 2024 - Confirm that this code yields the expected results.
            $query = $query->where('status', get_status_code($requestQueries['status'], $this->model));
        } else {
            $query = $this->filterActive($query);
        }

        if (array_key_exists('with', $requestQueries) && $requestQueries['with']) {
            $relationships = explode(',', $requestQueries['with']);
            $validRelationships = $this->filterValidRelationships($relationships);
            // TODO: Kigathi - 16:27 July 6 2024 - Replace this with $this->relations += $validRelationships;
            $query = $query->with($validRelationships);
        }

        if (array_key_exists('search', $requestQueries) && $requestQueries['search']) {
            $result['search'] = $requestQueries['search'];
            if (array_key_exists('search-relations', $requestQueries) && $requestQueries['search-relations']) {
                $parts = explode(",", $requestQueries['search-relations']);
                $preliminaryResult = [];
                for ($i = 0; $i < count($parts); $i += 2) {
                    $relation = $parts[$i];
                    $key = $parts[$i + 1];
                    /**
                     * TODO: Kigathi - 14:37 July 7 2024 - Key is an array that expects many relation columns could be searched.
                     * Lyre currently supports searching through one column only, this should be resolved in the future.
                     *  */
                    $preliminaryResult[$relation] = [$key];
                }
                $result['relations'] = $preliminaryResult;
            }
            $this->searchQuery($result);
        }

        if (array_key_exists('unpaginated', $requestQueries) && $requestQueries['unpaginated'] == 'true') {
            $this->unPaginate();
        }

        if (array_key_exists('limit', $requestQueries) && $requestQueries['limit'] && is_numeric($requestQueries['limit'])) {
            $this->limit((int) $requestQueries['limit']);
        }

        // TODO: Kigathi - July 6 2024 - Confirm that this code yields the expected results.
        if (array_key_exists('relation', $requestQueries) && $requestQueries['relation']) {
            $parts = explode(",", $requestQueries['relation']);
            $result = [];
            for ($i = 0; $i < count($parts); $i += 2) {
                $relatedModel = $this->model->{$parts[$i]}();
                $relatedModelClass = get_class($relatedModel->getRelated());
                $idColumn = $relatedModelClass::ID_COLUMN;
                $result[$parts[$i]] = "{$idColumn},{$parts[$i + 1]}";
            }
            $this->relationFilters = $result;
        }

        if (array_key_exists('relation_in', $requestQueries) && $requestQueries['relation_in']) {
            $parts = explode(",", $requestQueries['relation_in']);
            $relation = $parts[0];
            unset($parts[0]);
            $this->relationFilters = [$relation => $parts];
        }

        // TODO: Kigathi - July 6 2024 - Confirm that this code yields the expected results.
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

        if (array_key_exists('filter', $requestQueries) && $requestQueries['filter']) {
            $parts = explode(',', $requestQueries['filter']);
            $result = [];
            for ($i = 0; $i < count($parts); $i += 2) {
                $result[$parts[$i]] = $parts[$i + 1];
            }
            $this->columnFilters = $result;
        }

        // TODO: Kigathi - December 23 2023 - Sanitize order by column keys
        if (array_key_exists('order', $requestQueries) && $requestQueries['order']) {
            $this->orderByColumn = $requestQueries['order'] ? explode(',', $requestQueries['order'])[0] : 'created_at';
            $this->orderByOrder = $requestQueries['order'] ? explode(',', $requestQueries['order'])[1] : 'desc';
        }

        return $query;
    }

    private function filterValidRelationships(array $relationships): array
    {
        $loadResources = $this->resource::loadResources();
        $pivotResources = $this->resource::pivotResources();
        $validRelationships = [];
        foreach ($relationships as $relationship) {
            if (isset($loadResources[$relationship]) || isset($pivotResources[$relationship])) {
                $validRelationships[] = $relationship;
            }
        }
        return $validRelationships;
    }

    public function performOperations($query)
    {
        foreach ($this->operations as $operation) {
            foreach ($operation as $method => $arguments) {
                $query = $arguments ? $query->{$method}($arguments) : $query->{$method}();
            }
        }
        return $query;
    }

    public function filterActive($query)
    {
        if (Schema::hasColumn($this->model->getTable(), 'status')) {
            /**
             * TODO: Kigathi - 18:00 July 6 2024 - Add a status config variable in lyre.php
             * This provision should also allow users to choose whether or not to order by desc default, as well as whether to filter by active status by default
             */
            $defaultConfigPath = config('lyre.status-config');
            $statusConfig = isset($this->model->generateConfig()['status']) ? config($this->model->generateConfig()['status']) : config($defaultConfigPath);
            if (!$statusConfig || in_array("active", $statusConfig)) {
                $query = $query->where('status', 'active');
            }
        }
        return $query;
    }

    public function collectResource($query, $paginate = true)
    {
        if ($query instanceof \Illuminate\Database\Eloquent\Builder) {
            $query = $query->get();
        }
        if (!$this->resource) {
            return $query;
        }
        return $this->resource::prepareCollection($query, $this->resource, $paginate);
    }

    public function instance(array $arguments)
    {
        $thisModel = $this->model->where($arguments)->first();
        if (!$thisModel) {
            throw CommonException::fromMessage("{$this->model->getTable()} not found");
        }
        return $thisModel;
    }

    public function generateConfig()
    {
        return $this->model->generateConfig();
    }
}
