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
use Illuminate\Support\Str;
use Lyre\Request as BaseRequest;
use Lyre\Traits\BaseControllerTrait;

class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests, BaseControllerTrait;

    protected $modelConfig;
    protected $modelName;
    protected $modelNamePlural;
    protected $modelInstance;
    protected $modelRepository;

    public function __construct(
        $modelConfig,
        $modelRepository
    ) {
        $this->modelConfig = $modelConfig;
        $this->modelName = $modelConfig['table'];
        $this->modelNamePlural = Pluralizer::plural($modelConfig['table']);
        $this->modelInstance = new $modelConfig['model']();
        $this->modelRepository = $modelRepository;
        Config::set('request-model', $this->modelInstance);
        $this->globalAuthorize();
    }

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

    public function store(BaseRequest $request, $scope = null)
    {
        $validatedData = $this->validateData($request);
        if ($scope) {
            $scopeName = $this->extractScopeFromRouteName();

            // NOTE: Kigathi - September 11 2025 - This assumes that all relationship columns are in singular form
            $scopeName = Str::singular($scopeName);

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
        $this->authorizeResource($this->modelConfig, $this->modelName, [
            'except' => empty($except) ? ['show', 'update', 'destroy'] : $except,
        ]);
    }

    public function localAuthorize($ability, $identifier, $findCallback = null)
    {
        $modelResource = $this->modelRepository
            ->silent()
            ->find([$this->modelConfig['id'] => $identifier], $findCallback);
        $model = $modelResource->resource ?? null;
        $this->authorize($ability, $model);
        return $modelResource;
    }

    public function validateData(Request $request, $type = "store-request")
    {
        if (isset($this->modelConfig[$type]) && class_exists($this->modelConfig[$type])) {
            $modelRequest = $this->modelConfig[$type];
            /**
             * NOTE: Kigathi - April 18 2025
             * Understand the following commends from ChatGPT, and why it was necessary to do this:
             *  This way:
             *  You don't lose file handling
             *  UploadedFile instances are properly recognized
             *  authorize() and prepareForValidation() get called as expected
             *  You get all the features of FormRequest, safely
             */
            $modelRequestInstance = app($modelRequest);
            $modelRequestInstance->setContainer(app())->setRedirector(app('redirect'));
            $modelRequestInstance->merge(array_merge($request->post(), $request->file()));
            $modelRequestInstance->validateResolved();
            return $this->sanitizeInputData(array_merge($request->post(), $request->file()), $modelRequestInstance);
        }

        return $request->post();
    }

    public function extractScopeFromRouteName()
    {
        $routeName = Route::currentRouteName();
        $segments = explode('.', $routeName);
        return $segments[0] ?? null;
    }

    public function getScopedResource($scope, $scopeName)
    {
        // NOTE: Kigathi - September 11 2025 - This assumes that all scoped model resources are related to their path parameter names distinctly

        $classBase = Str::studly(Str::singular($scopeName));
        $scopedModelClass = null;

        foreach (config('lyre.path.model', []) as $namespace) {
            $class = $namespace . '\\' . $classBase;

            if (class_exists($class)) {
                $scopedModelClass = $class;
            }
        }

        $repositoryInterface = ltrim($scopedModelClass::getRepositoryInterfaceConfig(), '\\');
        $repository = app()->make($repositoryInterface);
        $scopedResource = $repository->find(['id' => $scope]);
        return $scopedResource;
    }

    private function getScopeCallback($scope)
    {
        $scopeName = $this->extractScopeFromRouteName();

        // NOTE: Kigathi - September 11 2025 - This assumes that all relationship columns are in singular form
        $scopeName = Str::singular($scopeName);

        $scopedResource = $this->getScopedResource($scope, $scopeName);
        $scopeId = $scopedResource->resource->id;
        return fn($query) => $query->where("{$scopeName}_id", $scopeId);
    }
}
