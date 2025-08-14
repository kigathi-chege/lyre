<?php

namespace Lyre\Http\Controllers;

use Lyre\Models\TenantAssociation;
use Lyre\Contracts\TenantAssociationRepositoryInterface;
use Lyre\Controller;

class TenantAssociationController extends Controller
{
    public function __construct(
        TenantAssociationRepositoryInterface $modelRepository
    ) {
        $model = new TenantAssociation();
        $modelConfig = $model->generateConfig();
        parent::__construct($modelConfig, $modelRepository);
    }
}
