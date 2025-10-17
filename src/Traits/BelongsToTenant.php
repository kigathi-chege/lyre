<?php

namespace Lyre\Strings\Traits;

/**
 * Trait for handling tenant relationships.
 * 
 * This trait provides methods for managing tenant associations
 * in multi-tenant applications.
 * 
 * @package Lyre\Strings\Traits
 */
trait BelongsToTenant
{
    /**
     * Associate model with tenant.
     *
     * @param mixed $tenant
     * @return void
     */
    public function associateWithTenant($tenant): void
    {
        if (method_exists($this, 'tenant')) {
            $this->tenant()->associate($tenant);
        }
    }

    /**
     * Get the tenant relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function tenant()
    {
        return $this->belongsTo(config('lyre.tenancy.model', \Lyre\Strings\Models\Tenant::class));
    }
}
