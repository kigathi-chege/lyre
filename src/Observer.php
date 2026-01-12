<?php

namespace Lyre;

use Illuminate\Support\Facades\Schema;
use Lyre\Facades\Lyre;

class Observer
{
    public function creating($model)
    {
        if (Schema::hasColumn($model->getTable(), 'slug')) {
            // Lyre::setSlug($model);
            set_slug($model);
        }

        if (Schema::hasColumn($model->getTable(), 'uuid')) {
            // Lyre::setUuid($model);
            set_uuid($model);
        }
    }

    public function updating($model)
    {
        if (config('lyre.update-slug')) {
            if (
                Lyre::getModelIdColumn($model) === "slug" &&
                $model->isDirty(Lyre::getModelNameColumn($model))
            ) {
                // Lyre::setSlug($model);
                set_slug($model);
            }
        }
    }

    public function created($model): void
    {
        // Associate with tenant asynchronously to avoid blocking
        $tenant = tenant();
        if ($tenant && !($model instanceof \Lyre\Models\TenantAssociation || $model instanceof \Lyre\Models\Tenant)) {
            try {
                $model->associateWithTenant($tenant);
            } catch (\Throwable $e) {
                // Log but don't fail the request if tenant association fails
                logger()->warning('Failed to associate model with tenant', [
                    'model' => get_class($model),
                    'model_id' => $model->id,
                    'tenant_id' => $tenant?->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Activity logging can be slow, do it asynchronously if possible
        if (config('lyre.activity-log')) {
            try {
                activity('created')
                    ->performedOn($model)
                    ->causedBy(request()->user())
                    ->withProperties($model->toArray())
                    ->log(Lyre::getModelName($model) ?? Lyre::getModelId($model));
            } catch (\Throwable $e) {
                // Log but don't fail the request if activity logging fails
                logger()->debug('Failed to log activity', [
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    public function updated($model): void
    {
        if (config('lyre.activity-log')) {
            activity('updated')
                ->performedOn($model)
                ->causedBy(request()->user())
                ->withProperties($model->getChanges())
                ->log(Lyre::getModelName($model) ?? Lyre::getModelId($model));
        }
    }

    public function deleted($model): void
    {
        if (config('lyre.activity-log')) {
            activity('deleted')
                ->performedOn($model)
                ->causedBy(request()->user())
                ->withProperties($model->toArray())
                ->log(Lyre::getModelName($model) ?? Lyre::getModelId($model));
        }
    }

    public function restored($model): void
    {
        if (config('lyre.activity-log')) {
            activity('restored')
                ->performedOn($model)
                ->causedBy(request()->user())
                ->withProperties($model->toArray())
                ->log(Lyre::getModelName($model) ?? Lyre::getModelId($model));
        }
    }

    public function forceDeleted($model): void
    {
        if (config('lyre.activity-log')) {
            activity('force deleted')
                ->performedOn($model)
                ->causedBy(request()->user())
                ->withProperties($model->toArray())
                ->log(Lyre::getModelName($model) ?? Lyre::getModelId($model));
        }
    }
}
