<?php

namespace Lyre\Interface;

interface RepositoryInterface
{
    public function all($filterCallback = null, $paginate = true);
    public function trashed();
    public function find($arguments, $filterCallback = null);
    public function latest();
    public function limit(int $limit);
    public function create(array $data);
    public function update(array $data, string $slug, $model = null);
    public function firstOrCreate(array $search, array $data = []);
    public function updateOrCreate(array $search, array $data = []);
    public function delete($id);
    public function relations(array $relations);
    public function columnFilters(array $filters);
    public function rangeFilters(array $filters);
    public function relationFilters(array $relationFilters);
    public function searchQuery(array $searchQuery);
    public function filter($query, $arguments);
    public function paginate(int $perPage);
    public function applyColumnFilters($query);
    public function applyRangeFilters($query);
    public function applyRelationFilters($query);
    public function search($query);
    public function order($query, array $order);
    public function linkRelations($query);
    public function collectResource($query);
    public function sanitizeArguments($arguments);
    public function performOperations($query);
    public function silent();
    public function generateConfig();
    public function instance(array $arguments);
    public function withInactive();
}
