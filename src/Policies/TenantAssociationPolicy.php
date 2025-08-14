<?php

namespace Lyre\Policies;

use Lyre\Models\TenantAssociation;
use Lyre\Policy;

class TenantAssociationPolicy extends Policy
{
    public function __construct(TenantAssociation $model)
    {
        parent::__construct($model);
    }
}
