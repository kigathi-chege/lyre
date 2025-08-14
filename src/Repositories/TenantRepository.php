<?php

namespace Lyre\Repositories;

use Lyre\Repository;
use Lyre\Models\Tenant;
use Lyre\Contracts\TenantRepositoryInterface;

class TenantRepository extends Repository implements TenantRepositoryInterface
{
    protected $model;

    public function __construct(Tenant $model)
    {
        parent::__construct($model);
    }
}
