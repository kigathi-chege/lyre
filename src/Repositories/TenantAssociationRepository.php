<?php

namespace Lyre\Repositories;

use Lyre\Repository;
use Lyre\Models\TenantAssociation;
use Lyre\Contracts\TenantAssociationRepositoryInterface;

class TenantAssociationRepository extends Repository implements TenantAssociationRepositoryInterface
{
    protected $model;

    public function __construct(TenantAssociation $model)
    {
        parent::__construct($model);
    }
}
