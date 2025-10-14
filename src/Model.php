<?php

namespace Lyre;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model as BaseModel;
use Lyre\Traits\BaseModelTrait;

class Model extends BaseModel
{
    use HasFactory, BaseModelTrait;

    protected $guarded = ['id'];

    public function __construct(array $attributes = [])
    {
        $this->table = config('lyre.table_prefix') . $this->table;

        parent::__construct($attributes);
    }
}
