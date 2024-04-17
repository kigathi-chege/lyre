<?php

namespace Lyre;

use App\Http\Controllers\Controller as BaseController;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Pluralizer;
use Lyre\Request as BaseRequest;
use Lyre\Traits\BaseControllerTrait;

class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests, BaseControllerTrait;

    protected $model;
    protected $modelName;
    protected $modelNamePlural;
    protected $modelInstance;
    protected $modelRepository;

    public function __construct(
        $model, $modelRepository
    ) {
        $this->model = $model;
        $this->modelName = $model['table'];
        $this->modelNamePlural = Pluralizer::plural($model['table']);
        $this->modelInstance = new $model['model']();
        $this->modelRepository = $modelRepository;
        Config::set('request-model', $this->modelInstance);
        $this->globalAuthorize();
    }

    public function index(Request $request, $scope = null)
    {
        $filterCallBack = $scope ? $this->getScopeCallback($scope) : null;
        return curate_response(
            true,
            "Get {$this->modelNamePlural}",
            $this->modelRepository->all($filterCallBack),
            get_response_code("get-{$this->modelNamePlural}")
        );
    }

    public function store(BaseRequest $request, $scope = null)
    {
        $validatedData = $this->validateData($request);
        if ($scope) {
            $scopeName = $this->extractScopeFromRouteName();
            $scopedResource = $this->getScopedResource($scope, $scopeName);
            $validatedData["{$scopeName}_id"] = $scopedResource->resource->id;
        }
        return curate_response(
            true,
            "Create {$this->modelNamePlural}",
            $this->modelRepository->create($validatedData),
            get_response_code("create-{$this->modelName}")
        );
    }

    public function show(Request $request, $slug, $scope = null)
    {
        $modelResource = $this->localAuthorize('view', $slug);
        return curate_response(
            true,
            "Find {$this->modelName}",
            $modelResource,
            get_response_code("find-{$this->modelName}")
        );
    }

    public function update(Request $request, $slug, $scope = null)
    {
        $modelResource = $this->localAuthorize('update', $scope ?? $slug);
        $validatedData = $this->validateData($request, 'update-request');
        return curate_response(
            true,
            "Update {$this->modelName}",
            $this->modelRepository->update($validatedData, $scope ?? $slug, $modelResource->resource),
            get_response_code("update-{$this->modelName}")
        );
    }

    public function destroy($slug, $scope = null)
    {
        $modelResource = $this->localAuthorize('destroy', $slug);
        $this->modelRepository->delete($slug);
        return curate_response(
            true,
            "Delete {$this->modelName}",
            $modelResource,
            get_response_code("destroy-{$this->modelName}")
        );
    }

    public function sanitizeInputData($rawData, $requestInstance)
    {
        $validator = Validator::make($rawData, $requestInstance->rules(), $requestInstance->messages());
        if ($validator->fails()) {
            $requestInstance->failedValidation($validator);
        }
        return $validator->validated();
    }

    public function globalAuthorize(array $except = [])
    {
        $this->authorizeResource($this->model, $this->modelName, [
            'except' => empty($except) ? ['show', 'update', 'destroy'] : $except,
        ]);
    }

    public function localAuthorize($ability, $identifier, $findCallback = null)
    {
        $modelResource = $this->modelRepository
            ->find(["id" => $identifier], $findCallback);
        $model = $modelResource->resource;
        $this->authorize($ability, $model);
        return $modelResource;
    }

    public function validateData(Request $request, $type = "store-request")
    {
        if (isset($this->model[$type]) && class_exists($this->model[$type])) {
            $modelRequest = $this->model[$type];
            $modelRequestInstance = new $modelRequest($request->post());
            return $this->sanitizeInputData($request->post(), $modelRequestInstance);
        }

        return $request->post();
    }

    private function extractScopeFromRouteName()
    {
        $routeName = Route::currentRouteName();
        $segments = explode('.', $routeName);
        return $segments[0] ?? null;
    }

    private function getScopedResource($scope, $scopeName)
    {
        $repositoryInterface = config("models")[$scopeName]['repository-interface'];
        $repository = app()->make($repositoryInterface);
        $scopedResource = $repository->find(['id' => $scope]);
        return $scopedResource;
    }

    private function getScopeCallback($scope)
    {
        $scopeName = $this->extractScopeFromRouteName();
        $scopedResource = $this->getScopedResource($scope, $scopeName);
        $scopeId = $scopedResource->resource->id;
        return fn($query) => $query->where("{$scopeName}_id", $scopeId);
    }
}