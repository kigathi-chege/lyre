<?php

namespace Lyre;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class Policy
{
    use HandlesAuthorization;

    protected $usingSpatieRoles, $model, $table;

    public function __construct($model)
    {
        $this->model            = $model;
        $this->table            = $this->model->getTable();
        $this->usingSpatieRoles = in_array(\Spatie\Permission\Traits\HasRoles::class, class_uses(\App\Models\User::class));
    }

    public function viewAny(?User $user): bool
    {
        return $this->usingSpatieRoles ? ($user ? $user->can("view-any-{$this->table}") : false) : true;
    }

    public function view(?User $user, $model): bool
    {
        return $this->usingSpatieRoles ? ($user ? $user->can("view-{$this->table}") : false) : true;
    }

    public function create(?User $user): bool
    {
        return $this->usingSpatieRoles ? ($user ? $user->can("create-{$this->table}") : false) : true;
    }

    public function update(User $user, $model): bool
    {
        return $this->usingSpatieRoles ? ($user->can("update-{$this->table}")) : true;
    }

    public function bulkUpdate(User $user): bool
    {
        return $this->usingSpatieRoles ? ($user->can("bulk-update-{$this->table}")) : true;
    }

    public function delete(User $user, $model): bool
    {
        return $this->usingSpatieRoles ? ($user->can("delete-{$this->table}")) : true;
    }

    public function restore(User $user, $model): bool
    {
        return $this->usingSpatieRoles ? ($user->can("restore-{$this->table}")) : true;
    }

    public function forceDelete(User $user, $model): bool
    {
        return $this->usingSpatieRoles ? ($user->can("force-delete-{$this->table}")) : true;
    }
}
