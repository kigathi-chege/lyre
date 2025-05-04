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
        $permission = get_model_permission_by_prefix(get_class($this->model), 'view-any');
        return $this->usingSpatieRoles ? ($user ? $user->can($permission) : false) : true;
    }

    public function view(?User $user, $model): bool
    {
        $permission = get_model_permission_by_prefix(get_class($this->model), 'view');
        return $this->usingSpatieRoles ? ($user ? $user->can($permission) : false) : true;
    }

    public function create(?User $user): bool
    {
        $permission = get_model_permission_by_prefix(get_class($this->model), 'create');
        return $this->usingSpatieRoles ? ($user ? $user->can($permission) : false) : true;
    }

    public function update(User $user, $model): bool
    {
        $permission = get_model_permission_by_prefix(get_class($this->model), 'update');
        return $this->usingSpatieRoles ? ($user->can($permission)) : true;
    }

    public function bulkUpdate(User $user): bool
    {
        $permission = get_model_permission_by_prefix(get_class($this->model), 'update');
        return $this->usingSpatieRoles ? ($user->can($permission)) : true;
    }

    public function delete(User $user, $model): bool
    {
        $permission = get_model_permission_by_prefix(get_class($this->model), 'delete');
        return $this->usingSpatieRoles ? ($user->can($permission)) : true;
    }

    public function restore(User $user, $model): bool
    {
        $permission = get_model_permission_by_prefix(get_class($this->model), 'restore');
        return $this->usingSpatieRoles ? ($user->can($permission)) : true;
    }

    public function forceDelete(User $user, $model): bool
    {
        $permission = get_model_permission_by_prefix(get_class($this->model), 'force-delete');
        return $this->usingSpatieRoles ? ($user->can($permission)) : true;
    }
}
