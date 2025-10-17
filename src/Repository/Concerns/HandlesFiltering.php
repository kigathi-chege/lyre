<?php

namespace Lyre\Strings\Repository\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Schema;

/**
 * Handles filtering operations for repositories.
 * 
 * This concern provides methods for applying various types of filters
 * to queries including column filters, range filters, and relation filters.
 * 
 * @package Lyre\Strings\Repository\Concerns
 */
trait HandlesFiltering
{
    /**
     * Set column filters for the query.
     *
     * @param array $columnFilters
     * @return $this
     */
    public function columnFilters(array $columnFilters): static
    {
        $this->columnFilters = $columnFilters;
        return $this;
    }

    /**
     * Set range filters for the query.
     *
     * @param array $rangeFilters
     * @return $this
     */
    public function rangeFilters(array $rangeFilters): static
    {
        $this->rangeFilters = $rangeFilters;
        return $this;
    }

    /**
     * Set relation filters for the query.
     * 
     * Expected format:
     * [
     *   'relation' => [
     *       'column' => 'column',
     *       'value' => 'value'
     *   ],
     *   'relation1' => [
     *       'column' => 'column1',
     *       'value' => 'value1'
     *   ],
     *   'relation2' => [
     *       'column' => 'column2',
     *       'value' => ['value2', 'value3']
     *   ],
     * ]
     *
     * @param array $relationFilters
     * @return $this
     */
    public function relationFilters(array $relationFilters): static
    {
        $this->relationFilters = $relationFilters;
        return $this;
    }

    /**
     * Set search query parameters.
     *
     * @param array $searchQuery
     * @return $this
     */
    public function searchQuery(array $searchQuery): static
    {
        $this->searchQuery = $searchQuery;
        return $this;
    }

    /**
     * Set relations to be loaded with the query.
     *
     * @param array $relations
     * @return $this
     */
    public function relations(array $relations): static
    {
        $this->relations = $relations;
        return $this;
    }

    /**
     * Set ordering for the query.
     *
     * @param string $column
     * @param string $order
     * @return $this
     */
    public function orderBy(string $column, string $order = 'desc'): static
    {
        $this->orderByColumn = $column;
        $this->orderByOrder = $order;
        return $this;
    }

    /**
     * Set starts with filter.
     *
     * @param string $startsWith
     * @return $this
     */
    public function startsWith(string $startsWith): static
    {
        $this->startsWith = $startsWith;
        return $this;
    }

    /**
     * Set with count relations.
     *
     * @param string|array $relation
     * @return $this
     */
    public function withCount(string | array $relation): static
    {
        $this->withCount += $relation;
        return $this;
    }

    /**
     * Set where null conditions.
     *
     * @param array $columns
     * @return $this
     */
    public function whereNull(array $columns): static
    {
        $this->whereNull += $columns;
        return $this;
    }

    /**
     * Set doesn't have conditions.
     *
     * @param array $relationships
     * @return $this
     */
    public function doesntHave(array $relationships): static
    {
        $this->doesntHave += $relationships;
        return $this;
    }

    /**
     * Enable silent mode (no exceptions thrown).
     *
     * @return $this
     */
    public function silent(): static
    {
        $this->silent = true;
        return $this;
    }

    /**
     * Include inactive records.
     *
     * @return $this
     */
    public function withInactive(): static
    {
        $this->withInactive = true;
        return $this;
    }

    /**
     * Set random ordering.
     *
     * @return $this
     */
    public function random(): static
    {
        $this->random = true;
        return $this;
    }

    /**
     * Disable ordering.
     *
     * @return $this
     */
    public function noOrder(): static
    {
        $this->noOrder = true;
        return $this;
    }

    /**
     * Set limit for the query.
     *
     * @param int $limit
     * @return $this
     */
    public function limit(int $limit): static
    {
        $this->limit = $limit;
        return $this;
    }

    /**
     * Set offset for the query.
     *
     * @param int $offset
     * @return $this
     */
    public function offset(int $offset): static
    {
        $this->offset = $offset;
        return $this;
    }

    /**
     * Set pagination parameters.
     *
     * @param int $perPage
     * @param int $page
     * @return $this
     */
    public function paginate(int $perPage, $page = 1): static
    {
        $this->perPage = $perPage;
        $this->page = $page;
        return $this;
    }

    /**
     * Disable pagination.
     *
     * @return $this
     */
    public function unPaginate(): static
    {
        $this->paginate = false;
        return $this;
    }

    /**
     * Get only the first result.
     *
     * @return $this
     */
    public function first(): static
    {
        $this->first = true;
        return $this;
    }

    /**
     * Sanitize arguments for security.
     *
     * @param array $arguments
     * @return array
     */
    public function sanitizeArguments($arguments): array
    {
        $modelFillables = $this->model->getFillableAttributes();
        $allowedColumns = $this->resource::serializableColumns($this->model);
        $allowedColumnKeys = array_unique(array_merge($allowedColumns->keys()->toArray(), $modelFillables));
        $sanitizedArguments = collect($arguments)->only($allowedColumnKeys)->all();
        $preparedArguments = $this->prepareArguments($this->allKnownKeys($modelFillables, $allowedColumns->toArray()), $sanitizedArguments);
        return $preparedArguments;
    }

    /**
     * Get all known keys from arrays.
     *
     * @param array $array1
     * @param array $array2
     * @return array
     */
    public function allKnownKeys($array1, $array2): array
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

    /**
     * Prepare arguments for query.
     *
     * @param array $keys
     * @param array $values
     * @return array
     */
    public function prepareArguments($keys, $values): array
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
}
