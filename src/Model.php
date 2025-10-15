<?php

namespace Lyre;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model as BaseModel;
use Illuminate\Support\Str;
use Lyre\Traits\BaseModelTrait;

class Model extends BaseModel
{
    use HasFactory, BaseModelTrait;

    protected $guarded = ['id'];

    /**
     * Get the table associated with the model.
     *
     * @return string
     */
    public function getTable()
    {
        $tableName = Str::snake(Str::pluralStudly(class_basename($this)));
        $isLyreModel = Str::startsWith(static::class, 'Lyre\\');
        if ($isLyreModel) {
            return config('lyre.table_prefix') . $tableName;
        }

        return $tableName;
    }
}
