<?php

namespace Lyre;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class Policy
{
    use HandlesAuthorization;

    protected $model, $table;

    public function __construct($model)
    {
        $this->model = $model;
        $this->table = $this->model->getTable();
    }

    public function viewAny(?User $user): bool
    {
        return $user ? $user->can("view-any-{$this->table}") : false;
    }

    public function view(?User $user, $model): bool
    {
        return $user ? $user->can("view-{$this->table}") : false;
    }

    public function create(?User $user): bool
    {
        return $user ? $user->can("create-{$this->table}") : false;
    }

    public function update(User $user, $model): bool
    {
        return $user->can("update-{$this->table}");
    }

    public function bulkUpdate(User $user): bool
    {
        return $user->can("bulk-update-{$this->table}");
    }

    public function delete(User $user, $model): bool
    {
        return $user->can("delete-{$this->table}");
    }

    public function restore(User $user, $model): bool
    {
        return $user->can("restore-{$this->table}");
    }

    public function forceDelete(User $user, $model): bool
    {
        return $user->can("force-delete-{$this->table}");
    }
}
