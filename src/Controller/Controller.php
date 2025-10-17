<?php

namespace Lyre\Strings\Controller;

use App\Http\Controllers\Controller as BaseController;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Pluralizer;
use Lyre\Strings\Controller\Concerns\HandlesAuthorization;
use Lyre\Strings\Controller\Concerns\HandlesCRUD;
use Lyre\Strings\Controller\Concerns\HandlesScoping;
use Lyre\Strings\Controller\Concerns\HandlesValidation;
use Lyre\Strings\Traits\BaseControllerTrait;

/**
 * Main Controller class for handling HTTP requests.
 * 
 * This controller class combines multiple concerns to provide a comprehensive
 * solution for handling CRUD operations, authorization, validation, and scoping.
 * 
 * @package Lyre\Strings\Controller
 */
class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests, BaseControllerTrait;
    use HandlesCRUD, HandlesAuthorization, HandlesValidation, HandlesScoping;

    /**
     * Model configuration.
     *
     * @var array
     */
    protected $modelConfig;

    /**
     * Model name.
     *
     * @var string
     */
    protected $modelName;

    /**
     * Plural model name.
     *
     * @var string
     */
    protected $modelNamePlural;

    /**
     * Model instance.
     *
     * @var mixed
     */
    protected $modelInstance;

    /**
     * Model repository.
     *
     * @var mixed
     */
    protected $modelRepository;

    /**
     * Create a new controller instance.
     *
     * @param array $modelConfig
     * @param mixed $modelRepository
     */
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
}
