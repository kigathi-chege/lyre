<?php

namespace Lyre\Traits;

use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Lyre\Models\Tenant;
use Lyre\Models\TenantAssociation;

trait BelongsToTenant
{
    // public static function bootBelongsToTenant()
    // {
    //     static::addGlobalScope('tenant', function (Builder $query) {
    //         $tenantModel = tenant();

    //         if (! $tenantModel) {
    //             logger("No tenant found, skipping tenant scope for model " . static::class);
    //             return;
    //         }

    //         $tenantId = $tenantModel?->id;

    //         logger("Applying tenant scope with ID: {$tenantId} for model " . static::class);

    //         if ($tenantId) {
    //             logger("Tenant ID found: {$tenantId}, applying scope...");
    //             $query->whereHas('tenants', function ($q) use ($tenantId) {
    //                 $q->where('tenants.id', $tenantId);
    //             });
    //         }

    //         logger("Tenant scope applied to query: " . $query->toSql());
    //     });
    // }

    public function scopeForCurrentTenant($query)
    {
        // TODO: Kigathi - August 14 2025 - Should only check role if user model implements `hasRole` method
        if (auth()->user()->hasRole(config('lyre.super-admin'))) {
            return $query; // no restriction
        }

        return $query->whereHas('associatedTenants', function ($q) {
            $q->where('tenants.id', tenant()->id);
        });
    }

    public function associateWithTenant(Tenant $tenant): void
    {
        $tenantClass = app()->make(Tenant::class)::class;

        if (! $tenant instanceof $tenantClass) {
            $tenant = $tenantClass::findOrFail($tenant->id);
        }

        $this->tenantAssociations()->firstOrCreate([
            'tenant_id' => $tenant->id,
        ]);
    }

    public function tenantAssociations()
    {
        return $this->morphMany(TenantAssociation::class, 'tenantable');
    }

    public function associatedTenants(): MorphToMany
    {
        $tenantClass = app()->make(Tenant::class)::class;

        $prefix = config('lyre.table_prefix');

        return $this->morphToMany(
            $tenantClass,
            'tenantable',              // morph name (based on tenantable_id / tenantable_type in TenantAssociation)
            $prefix . 'tenant_associations',     // pivot table
            'tenantable_id',           // FK to your model
            'tenant_id'                 // FK to Tenant
        )
            ->using(TenantAssociation::class)
            ->withTimestamps();
    }
}
