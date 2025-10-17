<?php

namespace Lyre\Strings\Models;

use Illuminate\Database\Eloquent\Model;
use Lyre\Strings\Model\BaseModelTrait;

/**
 * Tenant association model for multi-tenancy support.
 * 
 * @package Lyre\Strings\Models
 */
class TenantAssociation extends Model
{
    use BaseModelTrait;

    protected $fillable = [
        'tenant_id',
        'model_type',
        'model_id',
    ];

    protected $casts = [
        'tenant_id' => 'integer',
        'model_id' => 'integer',
    ];

    /**
     * Get the associated tenant.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Get the associated model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo
     */
    public function model()
    {
        return $this->morphTo();
    }
}
