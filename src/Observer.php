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
        if (tenant()) {
            $model->associateWithTenant(tenant());
        }

        if (config('lyre.activity-log')) {
            activity('created')
                ->performedOn($model)
                ->causedBy(request()->user())
                ->withProperties($model->toArray())
                ->log(Lyre::getModelName($model) ?? Lyre::getModelId($model));
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
