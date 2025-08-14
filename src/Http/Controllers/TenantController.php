<?php

namespace Lyre\Http\Controllers;

use Lyre\Models\Tenant;
use App\Models\User;
use Lyre\Contracts\TenantRepositoryInterface;
use Illuminate\Support\Facades\Crypt;
use Lyre\Controller;

class TenantController extends Controller
{
    public function __construct(
        TenantRepositoryInterface $modelRepository
    ) {
        $model = new Tenant();
        $modelConfig = $model->generateConfig();
        parent::__construct($modelConfig, $modelRepository);
    }
}
