<?php

namespace Lyre\Strings\Repository\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Schema;

/**
 * Handles query building and filtering operations for repositories.
 * 
 * This concern provides methods for building complex queries with filters,
 * relations, search, and ordering capabilities.
 * 
 * @package Lyre\Strings\Repository\Concerns
 */
trait HandlesQueries
{
    /**
     * Build a complete query with all applied filters and operations.
     *
     * @param array|null $callbacks Additional query callbacks to apply
     * @param bool $paginate Whether to paginate results
     * @return Builder
     */
    public function buildQuery(array | null $callbacks = [], $paginate = true): Builder
    {
        $query = $this->getQuery();
        $query = $this->prepareQuery($query);
        $query = $this->linkRelations($query);
        $query = $this->applyColumnFilters($query);
        $query = $this->applyRangeFilters($query);
        $query = $this->applyRelationFilters($query);
        $query = $this->applyCallbacks($query, $callbacks);
        $query = $this->performOperations($query);
        $query = $this->search($query);

        if (!$this->random && !$this->noOrder) {
            $query = $this->order($query);
        }

        $query = $this->applyStartsWith($query);
        $query = $this->applyWithCount($query);
        $query = $this->applyWhereNull($query);
        $query = $this->applyDoesntHave($query);

        if ($this->offset) {
            $query->offset($this->offset);
        }

        if ($this->random && !$this->noOrder) {
            $query->inRandomOrder();
        }

        return $query;
    }

    /**
     * Get the base query for the model.
     *
     * @return Builder
     */
    public function getQuery(): Builder
    {
        return $this->model->query();
    }

    /**
     * Apply callbacks to the query.
     *
     * @param Builder $query
     * @param array|null $callbacks
     * @return Builder
     */
    public function applyCallbacks($query, array | null $callbacks = []): Builder
    {
        if (!$callbacks) {
            return $query;
        }

        foreach ($callbacks as $callback) {
            if (is_callable($callback)) {
                $query = call_user_func($callback, $query);
            }
        }

        return $query;
    }

    /**
     * Filter the query with given arguments.
     *
     * @param Builder $query
     * @param array $arguments
     * @param bool $disjunct Whether to use OR condition
     * @return Builder
     */
    public function filter($query, $arguments, $disjunct = false): Builder
    {
        $arguments = $this->sanitizeArguments($arguments);

        if (empty($arguments)) {
            return $query;
        }

        return $disjunct ? $query->orWhere($arguments) : $query->where($arguments);
    }

    /**
     * Apply column filters to the query.
     *
     * @param Builder $query
     * @return Builder
     */
    public function applyColumnFilters($query): Builder
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

    /**
     * Apply range filters to the query.
     *
     * @param Builder $query
     * @return Builder
     */
    public function applyRangeFilters($query): Builder
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

    /**
     * Apply relation filters to the query.
     *
     * @param Builder $query
     * @return Builder
     */
    public function applyRelationFilters($query): Builder
    {
        if (!empty($this->relationFilters)) {
            foreach ($this->relationFilters as $relation => $filter) {
                $query = filter_by_relationship($query, $relation, $filter['column'], $filter['value']);
            }
        }
        return $query;
    }

    /**
     * Apply search functionality to the query.
     *
     * @param Builder $query
     * @return Builder
     */
    public function search($query): Builder
    {
        if (!empty($this->searchQuery)) {
            $search = isset($this->searchQuery['search']) ? $this->searchQuery['search'] : null;
            if ($search) {
                $relations = isset($this->searchQuery['relations']) ? $this->searchQuery['relations'] : [];
                if ($relations) {
                    $this->model->load($relations);
                }

                $serializableColumns = $this->resource::serializableColumns($this->model)->values()->toArray();
                $query = keyword_search($query, $search, $serializableColumns, $relations);
            }
        }
        return $query;
    }

    /**
     * Apply ordering to the query.
     *
     * @param Builder $query
     * @return Builder
     */
    public function order($query): Builder
    {
        if ($this->orderByColumn) {
            if (strpos($this->orderByColumn, '.')) {
                $parts = explode('.', $this->orderByColumn);
                $relation = $parts[0];
                $joinDetails = get_join_details($relation, $this->model);
                if ($joinDetails) {
                    $query = $query->join($joinDetails['relatedTable'], $this->model->getTable() . '.' . $joinDetails['foreignKey'], '=', $joinDetails['relatedTable'] . '.' . $joinDetails['relatedKey'])
                        ->orderBy($joinDetails['relatedTable'] . '.' . $parts[1], $this->orderByOrder ?? 'desc');
                }
            } else {
                $query->orderBy($this->orderByColumn, $this->orderByOrder ?? 'desc');
            }
        }
        return $query;
    }

    /**
     * Link relations to the query.
     *
     * @param Builder $query
     * @return Builder
     */
    public function linkRelations($query): Builder
    {
        if (!empty($this->relations)) {
            $query->with($this->relations);
        }
        return $query;
    }

    /**
     * Apply starts with filter to the query.
     *
     * @param Builder $query
     * @return Builder
     */
    public function applyStartsWith($query): Builder
    {
        if (!empty($this->startsWith)) {
            $column = $this->model::NAME_COLUMN;
            $operator = \Illuminate\Support\Facades\DB::getDriverName() === 'pgsql' ? 'ILIKE' : 'LIKE';
            $query = $query->where($column, $operator, $this->startsWith . '%');
        }

        return $query;
    }

    /**
     * Apply with count to the query.
     *
     * @param Builder $query
     * @return Builder
     */
    public function applyWithCount($query): Builder
    {
        if (!empty($this->withCount)) {
            $query = $query->withCount($this->withCount);
            $countsWithSuffix = array_map(fn($item) => $item . '_count', $this->withCount);
            $this->model::setGlobalCustomColumns($countsWithSuffix);
        }
        return $query;
    }

    /**
     * Apply where null conditions to the query.
     *
     * @param Builder $query
     * @return Builder
     */
    public function applyWhereNull($query): Builder
    {
        if (!empty($this->whereNull)) {
            foreach ($this->whereNull as $column) {
                $query = $query->orWhereNull($column);
            }
        }
        return $query;
    }

    /**
     * Apply doesn't have conditions to the query.
     *
     * @param Builder $query
     * @return Builder
     */
    public function applyDoesntHave($query): Builder
    {
        if (!empty($this->doesntHave)) {
            foreach ($this->doesntHave as $relationship) {
                $query = $query->whereDoesntHave($relationship);
            }
        }
        return $query;
    }

    /**
     * Perform custom operations on the query.
     *
     * @param Builder $query
     * @return Builder
     */
    public function performOperations($query): Builder
    {
        foreach ($this->operations as $operation) {
            foreach ($operation as $method => $arguments) {
                $query = $arguments ? $query->{$method}($arguments) : $query->{$method}();
            }
        }
        return $query;
    }
}
