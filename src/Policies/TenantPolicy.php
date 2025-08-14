<?php

namespace Lyre\Policies;

use Lyre\Models\Tenant;
use Lyre\Policy;

class TenantPolicy extends Policy
{
    public function __construct(Tenant $model)
    {
        parent::__construct($model);
    }
}
