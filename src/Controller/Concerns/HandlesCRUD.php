<?php

namespace Lyre\Strings\Controller\Concerns;

use Illuminate\Http\Request;
use Illuminate\Support\Pluralizer;
use Lyre\Strings\Request as BaseRequest;

/**
 * Handles CRUD operations for controllers.
 * 
 * This concern provides methods for standard CRUD operations
 * including index, store, show, update, and destroy methods.
 * 
 * @package Lyre\Strings\Controller\Concerns
 */
trait HandlesCRUD
{
    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @param mixed $scope
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request, $scope = null)
    {
        $filterCallBack = $scope ? $this->getScopeCallback($scope) : null;

        $perPage = isset($request->query()['per_page']) ? $request->query()['per_page'] : config('lyre.per-page');
        $currentPage = isset($request->query()['page']) ? (int) $request->query()['page'] : 1;
        $latest = isset($request->query()['latest']) ? (int) $request->query()['latest'] : null;
        $paginate = isset($request->query()['paginate']) ? ($request->query()['paginate'] == 'true' ? true : false) : true;
        $order = isset($request->query()['order']) ? $request->query()['order'] : null;
        $trueOrder = explode(',', $order);
        $orderColumn = $trueOrder[0];
        $orderDirection = isset($trueOrder[1]) ? $trueOrder[1] : 'desc';
        if ($orderDirection !== 'asc' && $orderDirection !== 'desc') {
            $orderDirection = 'desc';
        }
        $query = $this->modelRepository;
        if ($order) {
            $query = $query->orderBy($orderColumn, $orderDirection);
        } else {
            $query = $query->orderBy($this->modelInstance::ORDER_COLUMN, $this->modelInstance::ORDER_DIRECTION);
        }
        if ($latest) {
            $data = $query->limit($latest)->all($filterCallBack);
        } else {
            $data = $query->paginate($perPage, $currentPage)->all($filterCallBack, $paginate);
        }

        return __response(
            true,
            "Get {$this->modelNamePlural}",
            $data,
            get_response_code("get-{$this->modelNamePlural}")
        );
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param BaseRequest $request
     * @param mixed $scope
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(BaseRequest $request, $scope = null)
    {
        $validatedData = $this->validateData($request);
        if ($scope) {
            $scopeName = $this->extractScopeFromRouteName();

            // NOTE: This assumes that all relationship columns are in singular form
            $scopeName = \Illuminate\Support\Str::singular($scopeName);

            $scopedResource = $this->getScopedResource($scope, $scopeName);
            $validatedData["{$scopeName}_id"] = $scopedResource->resource->id;
        }
        return __response(
            true,
            "Create {$this->modelNamePlural}",
            $this->modelRepository->create($validatedData),
            get_response_code("create-{$this->modelName}")
        );
    }

    /**
     * Display the specified resource.
     *
     * @param Request $request
     * @param mixed $slug
     * @param mixed $scope
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Request $request, $slug, $scope = null)
    {
        $modelResource = $this->localAuthorize('view', $slug);
        return __response(
            true,
            "Find {$this->modelName}",
            $modelResource,
            get_response_code("find-{$this->modelName}")
        );
    }

    /**
     * Update the specified resource in storage.
     *
     * @param Request $request
     * @param mixed $slug
     * @param mixed $scope
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $slug, $scope = null)
    {
        $modelResource = $this->localAuthorize(count(explode(',', $slug)) > 1 ? 'bulkUpdate' : 'update', $scope ?? $slug);
        $validatedData = $this->validateData($request, 'update-request');
        return __response(
            true,
            "Update {$this->modelName}",
            $this->modelRepository->update($validatedData, $scope ?? $slug, $modelResource->resource ?? null),
            get_response_code("update-{$this->modelName}")
        );
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param mixed $slug
     * @param mixed $scope
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($slug, $scope = null)
    {
        $modelResource = $this->localAuthorize('destroy', $slug);
        $this->modelRepository->delete($slug);
        return __response(
            true,
            "Delete {$this->modelName}",
            $modelResource,
            get_response_code("destroy-{$this->modelName}")
        );
    }
}
