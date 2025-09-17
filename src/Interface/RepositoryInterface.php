<?php

namespace Lyre\Interface;

interface RepositoryInterface
{
    public function getModel();
    public function getResource();
    public function getQuery();
    public function all(array | null $callbacks = [], $paginate = true);
    public function trashed();
    public function find($arguments, array | null $callbacks = []);
    public function findAny(array ...$conditions);
    public function latest();
    public function create(array $data);
    public function update(array $data, string | int $slug, $thisModel = null);
    public function random();
    public function limit(int $limit);
    public function firstOrCreate(array $search, array $data = []);
    public function updateOrCreate(array $search, array $data = []);
    public function delete($id);
    public function relations(array $relations);
    public function unPaginate();
    public function paginate(int $perPage, $page = 1);
    public function columnFilters(array $filters);
    public function rangeFilters(array $filters);
    public function relationFilters(array $relationFilters);
    public function searchQuery(array $searchQuery);
    public function filter($query, $arguments, $disjunct = false);
    public function applyColumnFilters($query);
    public function applyRangeFilters($query);
    public function applyRelationFilters($query);
    public function search($query);
    public function orderBy(string $column, string $order = 'desc');
    public function order($query);
    public function noOrder();
    public function linkRelations($query);
    public function collectResource($query);
    public function sanitizeArguments($arguments);
    public function performOperations($query);
    public function silent();
    public function generateConfig();
    public function instance(array $arguments);
    public function withInactive();
}
