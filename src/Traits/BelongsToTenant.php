<?php

namespace Lyre\Traits;

use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Lyre\Models\Tenant;
use Lyre\Models\TenantAssociation;

trait BelongsToTenant
{
    /**
     * Boot the BelongsToTenant trait for a model.
     *
     * Automatically scopes all queries to the current tenant for non-super-admin users.
     * Super-admins can see data across all tenants.
     *
     * To bypass this scope in specific queries, use:
     * Model::withoutGlobalScope('tenant')->get();
     */
    public static function bootBelongsToTenant()
    {
        static::addGlobalScope('tenant', function ($query) {
            // List of models that should NOT be tenant-scoped
            $excludedModels = [
                // 'App\Models\User',
                // 'App\Models\Tenant',
                // 'Lyre\Models\Tenant',
                // 'App\Models\Role',
                // 'App\Models\Permission',
            ];

            // Skip if this model should not be scoped
            if (in_array(static::class, $excludedModels)) {
                return;
            }

            // Only apply if user is authenticated
            if (!auth()->check()) {
                return;
            }

            // Skip if user is super-admin
            $user = auth()->user();
            if (method_exists($user, 'hasRole') && call_user_func([$user, 'hasRole'], config('lyre.super-admin'))) {
                return;
            }

            // Get current tenant
            $tenant = tenant();
            if (!$tenant) {
                return;
            }

            // Apply tenant scope using the associatedTenants relationship
            $prefix = config('lyre.table_prefix', '');
            $query->whereHas('associatedTenants', function ($q) use ($prefix, $tenant) {
                $q->where("{$prefix}tenants.id", $tenant->id);
            });
        });
    }

    public function scopeForTenant($query, Tenant $tenant)
    {
        $prefix = config('lyre.table_prefix');

        // TODO: Kigathi - August 14 2025 - Should only check role if user model implements `hasRole` method
        if (auth()->check() && auth()->user()->hasRole(config('lyre.super-admin'))) {
            return $query; // no restriction
        }

        return $query->whereHas('associatedTenants', function ($q) use ($prefix, $tenant) {
            $q->where("{$prefix}tenants.id", $tenant->id);
        });
    }

    public function scopeForCurrentTenant($query)
    {
        $tenant = tenant();
        if (!$tenant) {
            return $query; // no tenant, no restriction
        }

        return $query->forTenant($tenant);
    }

    public function associateWithTenant(Tenant | int $tenant): void
    {
        if (is_int($tenant)) {
            $tenant = Tenant::find($tenant);
        }

        logger("Associating model {$this->getTable()} {$this->id} with tenant {$tenant->id}");

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
