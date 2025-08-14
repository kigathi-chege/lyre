<?php

namespace Lyre\Http\Resources;

use Lyre\Models\Tenant as TenantModel;
use Lyre\Resource;

class Tenant extends Resource
{
    public function __construct(TenantModel $model)
    {
        parent::__construct($model);
    }
}
