<?php

namespace Kigathi\Lyre;

use Illuminate\Support\Facades\Schema;
use Kigathi\Lyre\Exceptions\CommonException;
use Kigathi\Lyre\Facades\Lyre;
use Kigathi\Lyre\Interface\RepositoryInterface;

class Repository implements RepositoryInterface
{
    protected $model;
    protected $relations = [];
    protected $columnFilters = [];
    protected $rangeFilters = [];
    protected $relationFilters = [];
    protected $searchQuery = [];
    protected $operations = [];
    protected $per_page = 9;
    protected $methods;
    protected $arguments;
    protected $resource;

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
        $results = $paginate ? $query->paginate($this->per_page ?? 10) : $query->get();
        return $this->collectResource($results, $paginate);
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
            throw CommonException::fromCode(404, ['model' => $this->model->getTable()]);
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

    public function update(array $data, string $slug, $thisModel = null)
    {
        if (!$thisModel) {
            $thisModel = $this->model->where([Lyre::getModelIdColumn($this->model) => $slug])->first();
            if (!$thisModel) {
                throw CommonException::fromCode(404, ['model' => $this->model->getTable()]);
            }
        }
        $data = array_filter($data);
        if (empty($data)) {
            throw CommonException::fromCode(706);
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

    public function paginate(int $perPage)
    {
        $this->per_page = $perPage;
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

    public function filter($query, $arguments)
    {
        $arguments = $this->sanitizeArguments($arguments);
        if ($arguments !== null) {
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

    public function order($query, array | null $order)
    {
        if ($order) {
            $query->orderBy($order);
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
            $query = $query->where($this->columnFilters);
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

    public function sanitizeArguments($arguments)
    {
        $modelFillables = $this->model->getFillableAttributes();
        $allowedColumns = $this->resource::serializableColumns($this->model);
        $allowedColumnKeys = array_unique(array_merge($allowedColumns->keys()->toArray(), $modelFillables));
        $sanitizedArguments = collect($arguments)->only($allowedColumnKeys)->all();
        $preparedArguments = $this->prepareArguments($allowedColumns, $sanitizedArguments);
        return $preparedArguments;
    }

    public function prepareArguments($keys, $values)
    {
        $result = [];
        foreach ($keys as $key => $newKey) {
            if (isset($values[$key])) {
                $result[$newKey] = $values[$key];
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
        $query = $this->filterActive($query);
        $query = $query->orderBy('created_at', 'desc');
        $requestQueries = request()->query();
        if (empty($requestQueries)) {
            return $query;
        }

        $query = $originQuery;

        if (array_key_exists('status', $requestQueries)) {
            $query = $query->where('status', get_status_code($requestQueries['status'], $this->model));
        } else {
            $query = $this->filterActive($query);
        }

        if (array_key_exists('with', $requestQueries)) {
            $relationships = explode(',', $requestQueries['with']);
            $validRelationships = $this->filterValidRelationships($relationships);
            $query = $query->with($validRelationships);
        }

        // TODO: Kigathi - December 23 2023 - Sanitize order by column keys
        if (array_key_exists('order', $requestQueries)) {
            $query = $query->orderBy(
                $requestQueries['order'] ? explode(',', $requestQueries['order'])[0] : 'created_at',
                $requestQueries['order'] ? explode(',', $requestQueries['order'])[1] : 'desc'
            );
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
            $statusConfigPath = isset($this->model->generateConfig()['status']) ? config($this->model->generateConfig()['status']) : "constant.status";
            $statusConfig = config($statusConfigPath);
            if (!$statusConfig || in_array("active", $statusConfig)) {
                $query = $query->where('status', 'active');
            }
        }
        return $query;
    }

    // TODO: Kigathi - January 29 2024 - Complete implementation for below method
    // public function instance($arguments = null)
    // {
    //     $arguments = $this->model->find($arguments);
    //     $thisModel = $this->model->where([get_model_id_column($this->model) => $slug])->first();
    //     if (!$thisModel) {
    //         throw CommonException::fromCode(404, ['model' => $this->model->getTable()]);
    //     }
    // }
}
