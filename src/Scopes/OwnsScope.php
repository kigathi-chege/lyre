<?php

namespace Lyre\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Schema;

class OwnsScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $user = auth()->user();

        if (! $user) {
            return;
        }

        $superAdminRole = (string) config('lyre.super-admin', 'super-admin');

        if ($superAdminRole !== '' && method_exists($user, 'hasRole') && $user->hasRole($superAdminRole)) {
            return;
        }

        $table = $model->getTable();
        $columns = Schema::getColumnListing($table);

        foreach (['creator_id', 'user_id'] as $ownerColumn) {
            if (in_array($ownerColumn, $columns, true)) {
                $builder->where("{$table}.{$ownerColumn}", $user->getAuthIdentifier());
                return;
            }
        }
    }
}
