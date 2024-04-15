<?php

namespace Kigathi\Lyre;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model as BaseModel;
use Kigathi\Lyre\Traits\BaseModelTrait;

class Model extends BaseModel
{
    use HasFactory, BaseModelTrait;
}
