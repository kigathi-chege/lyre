<?php

namespace Lyre\Models;

use Illuminate\Database\Eloquent\Relations\MorphPivot;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Lyre\Traits\BaseModelTrait;

class TenantAssociation extends MorphPivot
{
    use HasFactory, BaseModelTrait;

    protected $guarded = ['id'];

    public $incrementing = true;

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $prefix = config('lyre.table_prefix');
        $this->table = $prefix . 'tenant_associations';
    }

    public function tenantable()
    {
        return $this->morphTo();
    }
}
