<?php

namespace Lyre\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\User;
use Illuminate\Support\Str;
use Lyre\Model;

class Tenant extends Model
{
    use HasFactory;

    public function user()
    {
        return $this->belongsTo(User::class);
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

        return $this->morphedByMany(
            $modelClass,
            'tenantable',              // same morph name
            'tenant_associations',     // pivot table
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
