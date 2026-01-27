<?php

namespace Lyre\Traits;

use Filament\Facades\Filament;
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
            /*
             |--------------------------------------------------------------------------
             | Bypass tenant scoping for Filament
             |--------------------------------------------------------------------------
             |
             | Filament is an administrative context and should not be tenant-restricted
             | unless explicitly desired. This avoids polluting every Filament query
             | with `withoutGlobalScope('tenant')`.
             |
             */
            if (app()->runningInConsole() === false && Filament::isServing()) {
                return;
            }

            // Skip tenant infrastructure models to prevent infinite recursion
            if (is_a(static::class, Tenant::class, true) || is_a(static::class, TenantAssociation::class, true)) {
                return;
            }

            // List of additional models that should NOT be tenant-scoped
            $excludedModels = [
                'App\Models\User',
                'App\Models\Role',
                'App\Models\Permission',
                'Lyre\Content\Models\InteractionType',
            ];

            // Skip if this model should not be scoped
            if (in_array(static::class, $excludedModels)) {
                return;
            }

            // Skip if user is super-admin
            if (auth()->check()) {
                $user = auth()->user();
                if (method_exists($user, 'hasRole') && call_user_func([$user, 'hasRole'], config('lyre.super-admin'))) {
                    return;
                }
            }

            // Get current tenant
            $tenant = tenant();
            if (! $tenant) {
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
        $prefix    = config('lyre.table_prefix');
        $userModel = config('lyre.user_model');
        $user      = auth()->user();

        if (auth()->check() && $user instanceof $userModel && method_exists($user, 'hasRole') && $user->hasRole(config('lyre.super-admin'))) {
            return $query; // no restriction
        }

        return $query->whereHas('associatedTenants', function ($q) use ($prefix, $tenant) {
            $q->where("{$prefix}tenants.id", $tenant->id);
        });
    }

    public function scopeForCurrentTenant($query)
    {
        $tenant = tenant();
        if (! $tenant) {
            return $query; // no tenant, no restriction
        }

        return $query->forTenant($tenant);
    }

    public function associateWithTenant(Tenant | int $tenant): void
    {
        if (is_int($tenant)) {
            $tenant = Tenant::find($tenant);
        }

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
            'tenantable',                    // morph name (based on tenantable_id / tenantable_type in TenantAssociation)
            $prefix . 'tenant_associations', // pivot table
            'tenantable_id',                 // FK to your model
            'tenant_id'                      // FK to Tenant
        )
            ->using(TenantAssociation::class)
            ->withTimestamps();
    }
}
