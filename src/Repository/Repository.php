<?php

namespace Lyre\Strings\Repository;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use Lyre\Strings\Exceptions\CommonException;
use Lyre\Strings\Facades\Strings;
use Lyre\Strings\Repository\Concerns\HandlesDataManipulation;
use Lyre\Strings\Repository\Concerns\HandlesFiltering;
use Lyre\Strings\Repository\Concerns\HandlesQueries;
use Lyre\Strings\Repository\Concerns\HandlesRequestPreparation;
use Lyre\Strings\Repository\Contracts\RepositoryContract;

/**
 * Main Repository class that provides a clean interface for database operations.
 * 
 * This repository class combines multiple concerns to provide a comprehensive
 * solution for database operations including querying, filtering, data manipulation,
 * and request preparation.
 * 
 * @package Lyre\Strings\Repository
 */
class Repository implements RepositoryContract
{
    use HandlesQueries, HandlesDataManipulation, HandlesFiltering, HandlesRequestPreparation;

    /**
     * The model instance.
     *
     * @var Model
     */
    protected $model;

    /**
     * Relations to be loaded.
     *
     * @var array
     */
    protected $relations = [];

    /**
     * Column filters.
     *
     * @var array
     */
    protected $columnFilters = [];

    /**
     * Range filters.
     *
     * @var array
     */
    protected $rangeFilters = [];

    /**
     * Relation filters.
     *
     * @var array
     */
    protected $relationFilters = [];

    /**
     * Search query parameters.
     *
     * @var array
     */
    protected $searchQuery = [];

    /**
     * Custom operations to perform.
     *
     * @var array
     */
    protected $operations = [];

    /**
     * Pagination settings.
     *
     * @var bool
     */
    protected $paginate = true;

    /**
     * Items per page.
     *
     * @var int
     */
    protected $perPage = 9;

    /**
     * Current page.
     *
     * @var int
     */
    protected $page = 1;

    /**
     * Available methods.
     *
     * @var array
     */
    protected $methods;

    /**
     * Method arguments.
     *
     * @var array
     */
    protected $arguments;

    /**
     * Resource class for transformation.
     *
     * @var string|null
     */
    protected $resource;

    /**
     * Silent mode (no exceptions).
     *
     * @var bool
     */
    protected $silent = false;

    /**
     * Include inactive records.
     *
     * @var bool
     */
    protected $withInactive = false;

    /**
     * Query limit.
     *
     * @var int|false
     */
    protected $limit = false;

    /**
     * Query offset.
     *
     * @var int|false
     */
    protected $offset = false;

    /**
     * Order by column.
     *
     * @var string|null
     */
    protected $orderByColumn = null;

    /**
     * Order direction.
     *
     * @var string
     */
    protected $orderByOrder = 'desc';

    /**
     * Random ordering.
     *
     * @var bool
     */
    protected $random = false;

    /**
     * No ordering.
     *
     * @var bool
     */
    protected $noOrder = false;

    /**
     * Starts with filter.
     *
     * @var string|null
     */
    protected $startsWith = null;

    /**
     * With count relations.
     *
     * @var array
     */
    protected $withCount = [];

    /**
     * Where null columns.
     *
     * @var array
     */
    protected $whereNull = [];

    /**
     * Doesn't have relations.
     *
     * @var array
     */
    protected $doesntHave = [];

    /**
     * Get first result only.
     *
     * @var bool
     */
    protected $first = false;

    /**
     * Create a new repository instance.
     *
     * @param Model $model
     */
    public function __construct($model)
    {
        $this->model = $model;
        $this->methods = get_class_methods($this);
        $this->resource = Strings::getModelResource($this->model);
    }

    /**
     * Get the model instance.
     *
     * @return Model
     */
    public function getModel(): Model
    {
        return $this->model;
    }

    /**
     * Get the resource class.
     *
     * @return string|null
     */
    public function getResource(): ?string
    {
        return $this->resource;
    }

    /**
     * Get all records with optional callbacks and pagination.
     *
     * @param array|null $callbacks
     * @param bool $paginate
     * @return Collection|array
     */
    public function all(array | null $callbacks = [], $paginate = true)
    {
        $query = $this->buildQuery($callbacks, $paginate);

        if ($this->first) {
            return $query->first();
        }

        $results = $this->limit ?
            $query->limit($this->limit)->get() : ($paginate && $this->paginate ?
                $query->paginate($this->perPage ?? 10, ['*'], 'page', $this->page) :
                $query->get());

        return $this->collectResource(
            query: $results,
            paginate: $this->limit ? false : $paginate && $this->paginate
        );
    }

    /**
     * Find a record by ID or criteria.
     *
     * @param mixed $arguments
     * @param array|null $callbacks
     * @return Model|mixed
     * @throws CommonException
     */
    public function find($arguments, array | null $callbacks = [])
    {
        $query = $this->getQuery();
        $query = $this->prepareQuery($query);

        if (!is_array($arguments)) {
            $table = $this->model->getTable();
            $idColumn = get_model_id_column($this->model);
            $query = $this->model->newQuery();

            // Determine the column type from the database schema
            $columnType = Schema::getColumnType($table, $idColumn);

            // If it's an integer/bigint column and $arguments is numeric
            if (in_array($columnType, ['integer', 'bigint', 'smallint']) && is_numeric($arguments)) {
                $query->where($idColumn, $arguments);
            }
            // If it's a UUID/text column — or a string argument
            elseif (in_array($columnType, ['uuid', 'string', 'char', 'varchar', 'text'])) {
                $query->where($idColumn, $arguments);
            }

            // Optionally search slug/uuid as fallbacks
            if (Schema::hasColumn($table, 'slug')) {
                $query->orWhere('slug', $arguments);
            }

            if (Schema::hasColumn($this->model->getTable(), 'uuid')) {
                $query->orWhere('uuid', $arguments);
            }
        } else {
            $query = $this->filter($query, $arguments);
        }

        $query = $this->linkRelations($query);
        $query = $this->applyCallbacks($query, $callbacks);
        $query = $this->performOperations($query);
        $query = $this->applyWithCount($query);
        $resource = $query->first();

        if (!$resource) {
            if ($this->silent) {
                return null;
            }
            throw CommonException::fromMessage(
                class_basename($this->model) . " not found for arguments: " . json_encode($arguments)
            );
        }

        return $this->resource ? new $this->resource($resource) : $resource;
    }

    /**
     * Find any record matching multiple conditions.
     *
     * @param array ...$conditions
     * @return Model|mixed
     * @throws CommonException
     */
    public function findAny(array ...$conditions)
    {
        $query = $this->getQuery();
        $query = $this->prepareQuery($query);

        foreach ($conditions as $index => $condition) {
            $query = $this->filter($query, $condition, $index > 0);
        }

        $query = $this->linkRelations($query);
        $query = $this->performOperations($query);
        $query = $this->applyWithCount($query);

        $resource = $query->first();

        if (!$resource) {
            if ($this->silent) {
                return null;
            }
            throw CommonException::fromMessage("{$this->model->getTable()} not found");
        }

        return $this->resource ? new $this->resource($resource) : $resource;
    }

    /**
     * Collect and transform resources.
     *
     * @param mixed $query
     * @param bool $paginate
     * @return Collection|array
     */
    public function collectResource($query, $paginate = true)
    {
        if ($query instanceof Builder) {
            $query = $query->get();
        }
        if (!$this->resource) {
            return $query;
        }
        return $this->resource::prepareCollection($query, $this->resource, $paginate);
    }

    /**
     * Generate model configuration.
     *
     * @return array
     */
    public function generateConfig(): array
    {
        return $this->model->generateConfig();
    }
}
