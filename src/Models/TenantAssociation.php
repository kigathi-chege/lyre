<?php

namespace Lyre\Models;

use Illuminate\Database\Eloquent\Relations\MorphPivot;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Lyre\Traits\BaseModelTrait;

class TenantAssociation extends MorphPivot
{
    use HasFactory, BaseModelTrait;

    protected $guarded = ['id'];

    protected $table = 'tenant_associations';

    public $incrementing = true;

    public function tenantable()
    {
        return $this->morphTo();
    }
}
