<?php

namespace Lyre\Strings\Models;

use Illuminate\Database\Eloquent\Model;
use Lyre\Strings\Model\BaseModelTrait;

/**
 * Tenant model for multi-tenancy support.
 * 
 * @package Lyre\Strings\Models
 */
class Tenant extends Model
{
    use BaseModelTrait;

    protected $fillable = [
        'name',
        'domain',
        'database',
        'status',
    ];

    protected $casts = [
        'status' => 'string',
    ];
}
