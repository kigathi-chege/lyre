<?php

namespace Lyre\Strings\Repository\Concerns;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Lyre\Strings\Exceptions\CommonException;

/**
 * Handles data manipulation operations for repositories.
 * 
 * This concern provides methods for creating, updating, and deleting
 * model instances with proper validation and error handling.
 * 
 * @package Lyre\Strings\Repository\Concerns
 */
trait HandlesDataManipulation
{
    /**
     * Create a new model instance.
     *
     * @param array $data
     * @return Model|mixed
     */
    public function create(array $data)
    {
        $thisModel = $this->model->create($data);

        if (tenant()) {
            $thisModel->associateWithTenant(tenant());
        }

        return $this->resource ? new $this->resource($thisModel) : $thisModel;
    }

    /**
     * Find the first model matching the given criteria or create it.
     *
     * @param array $search
     * @param array $data
     * @return Model|mixed
     */
    public function firstOrCreate(array $search, array $data = [])
    {
        $thisModel = $this->model->firstOrCreate($search, $data);
        $result = $this->resource ? new $this->resource($thisModel) : $thisModel;
        $result->wasRecentlyCreated = $thisModel->wasRecentlyCreated;
        return $result;
    }

    /**
     * Update an existing model or create it if it doesn't exist.
     *
     * @param array $search
     * @param array $data
     * @return Model|mixed
     */
    public function updateOrCreate(array $search, array $data = [])
    {
        $thisModel = $this->model->updateOrCreate($search, $data);
        $result = $this->resource ? new $this->resource($thisModel) : $thisModel;
        $result->wasRecentlyCreated = $thisModel->wasRecentlyCreated;
        return $result;
    }

    /**
     * Update one or more model instances.
     *
     * @param array $data
     * @param string|int $slug
     * @param mixed $thisModel
     * @return Model|Collection|mixed
     * @throws CommonException
     */
    public function update(array $data, string | int $slug, $thisModel = null)
    {
        $slugs = [$slug];
        if ($thisModel) {
            $thisModel = collect([$thisModel]);
        } else {
            $slugs = explode(',', $slug);
            $thisModel = $this->model->whereIn(get_model_id_column($this->model), $slugs)->get();
        }

        if ($thisModel->isEmpty() || $thisModel->count() != count($slugs)) {
            throw CommonException::fromCode(404, ['model' => $this->model->getTable()]);
        }

        $data = array_filter($data);
        if (empty($data)) {
            throw CommonException::fromCode(706);
        }

        if (isset($data['status'])) {
            $data['status'] = get_status_code($data['status'], $thisModel->first());
        }

        foreach ($thisModel as $model) {
            $model->update($data);
        }

        if ($thisModel->count() === 1) {
            $single = $thisModel->first();
            return $this->resource
                ? new $this->resource($single)
                : $single;
        }

        return $this->collectResource(query: $thisModel, paginate: false);
    }

    /**
     * Delete one or more model instances.
     *
     * @param string|array $slug
     * @return Collection|mixed
     * @throws \Exception
     */
    public function delete($slug)
    {
        $query = $this->getQuery();

        if (!is_array($slug)) {
            $slug = [$slug];
        }

        $idColumn = get_model_id_column($this->model);
        $query = $this->model->whereIn($idColumn, $slug);

        if (\Illuminate\Support\Facades\Schema::hasColumn($this->model->getTable(), 'slug')) {
            $query->orWhereIn('slug', $slug);
        }

        $thisModel = $query->get();

        if (!$thisModel) {
            throw new \Exception("Resource not found", 404);
        }

        $thisModel->each->delete();

        return $this->resource
            ? $this->resource::collection($thisModel)
            : $thisModel;
    }

    /**
     * Get trashed models.
     *
     * @return Collection
     */
    public function trashed()
    {
        return $this->model->onlyTrashed()->get();
    }

    /**
     * Get the latest model instance.
     *
     * @return Model|mixed
     */
    public function latest()
    {
        $latest = $this->model->latest()->first();
        return $this->resource ? new $this->resource($latest) : $latest;
    }

    /**
     * Get a model instance by criteria.
     *
     * @param array $arguments
     * @return Model
     * @throws CommonException
     */
    public function instance(array $arguments): Model
    {
        $thisModel = $this->model->where($arguments)->first();
        if (!$thisModel) {
            throw CommonException::fromMessage("{$this->model->getTable()} not found");
        }
        return $thisModel;
    }
}
