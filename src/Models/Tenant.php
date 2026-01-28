<?php

namespace Lyre\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Str;
use Lyre\File\Concerns\HasFile;
use Lyre\Model;

class Tenant extends Model
{
    use HasFactory, HasFile;

    /**
     * Get the table associated with the model.
     *
     * @return string
     */
    public function getTable()
    {
        // NOTE: Kigathi - October 15 2025 - This is necessary because the Tenant model is sometimes overridden in the app.
        return config('lyre.table_prefix') . Str::snake(Str::pluralStudly(class_basename($this)));
    }

    public function user()
    {
        return $this->belongsTo(get_user_model());
    }

    public function getRouteKeyName(): string
    {
        return $this::ID_COLUMN;
    }

    /**
     * Retrieve all models that are "tenanted" by this tenant.
     *
     * This is for calling from the Tenant model side.
     *
     * @param string|null $modelClass The model class to fetch (optional).
     * @return \Illuminate\Database\Eloquent\Relations\MorphedByMany
     */
    // public function tenantedModels(?string $modelClass = null): MorphedByMany
    public function tenantedModels(?string $modelClass = null)
    {
        if (! $modelClass) {
            throw new \InvalidArgumentException('Please provide the model class you want to retrieve.');
        }

        $prefix = config('lyre.table_prefix');

        return $this->morphedByMany(
            $modelClass,
            'tenantable',              // same morph name
            $prefix . 'tenant_associations',     // pivot table
            'tenant_id',               // FK to Tenant
            'tenantable_id'             // FK to the model
        )
            ->using(app(\Lyre\Models\TenantAssociation::class))
            ->withTimestamps();
    }

    /**
     * Handle dynamic relationship calls like $tenant->tenantedMcpTools()
     * for any model that also uses this trait.
     */
    public function __call($method, $parameters)
    {
        if (preg_match('/^tenanted(.+)$/', $method, $matches)) {
            $modelName = $matches[1];
            $modelName = Str::studly(Str::singular($modelName));
            $modelClasses = get_model_classes();

            if (isset($modelClasses[$modelName])) {
                return $this->tenantedModels($modelClasses[$modelName]);
            }

            $modelNamePlural = Str::studly($matches[1]);
            if (isset($modelClasses[$modelNamePlural])) {
                return $this->tenantedModels($modelClasses[$modelNamePlural]);
            }

            logger()->warning("Model class not found for dynamic tenant relation: {$modelName}");
        }

        return parent::__call($method, $parameters);
    }
}
