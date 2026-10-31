<?php

namespace Lyre\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Opt a model into duplicate-safe find-or-create by name (see Lyre\Services\NameResolver
 * and Lyre\Controller::resolve). Override the hooks to say what makes a name unique and
 * what to do when an existing record is reused.
 */
trait ResolvesByName
{
    /**
     * The column holding the human-entered name.
     */
    public function resolveNameColumn(): string
    {
        return 'name';
    }

    /**
     * Narrow the records a name must be unique within, using the request input
     * (e.g. the subjects a topic is being created under). Default: globally unique.
     */
    public function resolutionScope(Builder $query, array $input): Builder
    {
        return $query;
    }

    /**
     * Called after the record is resolved. $created is false when an existing
     * record was reused, which is where links (pivots) to the new context belong.
     */
    public function afterResolve(Model $model, array $input, bool $created): void
    {
    }
}
