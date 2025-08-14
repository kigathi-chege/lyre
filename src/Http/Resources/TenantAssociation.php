<?php

namespace Lyre\Http\Resources;

use Lyre\Models\TenantAssociation as TenantAssociationModel;
use Lyre\Resource;

class TenantAssociation extends Resource
{
    public function __construct(TenantAssociationModel $model)
    {
        parent::__construct($model);
    }
}
