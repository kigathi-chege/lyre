<?php

namespace Lyre\Repositories;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Lyre\Exceptions\CommonException;
use Lyre\Facades\Lyre;
use Lyre\Repositories\Contracts\RepositoryContract;
use Illuminate\Support\Facades\Config;

abstract class BaseRepository implements RepositoryContract
{
    protected $resource;

    public function __construct(public $model)
    {
        $this->resource = get_model_resource($this->model);
    }
}
