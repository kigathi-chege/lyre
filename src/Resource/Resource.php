<?php

namespace Lyre\Strings\Resource;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Lyre\Strings\Resource\Concerns\HandlesCollections;
use Lyre\Strings\Resource\Concerns\HandlesRelations;
use Lyre\Strings\Resource\Concerns\HandlesSerialization;

/**
 * Main Resource class for transforming model data.
 * 
 * This resource class combines multiple concerns to provide a comprehensive
 * solution for data transformation including serialization, relationship
 * handling, and collection preparation.
 * 
 * @package Lyre\Strings\Resource
 */
class Resource extends JsonResource
{
    use HandlesSerialization, HandlesRelations, HandlesCollections;

    /**
     * The request instance.
     *
     * @var Request
     */
    protected $request;

    /**
     * Create a new resource instance.
     *
     * @param mixed $resource
     */
    public function __construct($resource)
    {
        parent::__construct($resource);
    }
}
