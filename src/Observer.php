<?php

namespace Lyre;

use Illuminate\Support\Facades\Schema;
use Lyre\Facades\Lyre;

class Observer
{
    public function creating($model)
    {
        if (Schema::hasColumn($model->getTable(), 'slug')) {
            Lyre::setSlug($model);
        }

        if (Schema::hasColumn($model->getTable(), 'uuid')) {
            Lyre::setUuid($model);
        }
    }

    public function updating($model)
    {
        if (
            Lyre::getModelIdColumn($model) === "slug" &&
            $model->isDirty(Lyre::getModelNameColumn($model))
        ) {
            Lyre::setSlug($model);
        }
    }

    public function created($model): void
    {
        activity('created')
            ->performedOn($model)
            ->causedBy(request()->user())
            ->withProperties($model->toArray())
            ->log(Lyre::getModelName($model) ?? Lyre::getModelId($model));
    }

    public function updated($model): void
    {
        activity('updated')
            ->performedOn($model)
            ->causedBy(request()->user())
            ->withProperties($model->getChanges())
            ->log(Lyre::getModelName($model) ?? Lyre::getModelId($model));
    }

    public function deleted($model): void
    {
        activity('deleted')
            ->performedOn($model)
            ->causedBy(request()->user())
            ->withProperties($model->toArray())
            ->log(Lyre::getModelName($model) ?? Lyre::getModelId($model));
    }

    public function restored($model): void
    {
        activity('restored')
            ->performedOn($model)
            ->causedBy(request()->user())
            ->withProperties($model->toArray())
            ->log(Lyre::getModelName($model) ?? Lyre::getModelId($model));
    }

    public function forceDeleted($model): void
    {
        activity('force deleted')
            ->performedOn($model)
            ->causedBy(request()->user())
            ->withProperties($model->toArray())
            ->log(Lyre::getModelName($model) ?? Lyre::getModelId($model));
    }
}
