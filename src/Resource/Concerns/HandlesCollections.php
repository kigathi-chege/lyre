<?php

namespace Lyre\Strings\Resource\Concerns;

/**
 * Handles collection preparation and pagination for resources.
 * 
 * This concern provides methods for preparing collections of resources
 * with proper pagination and transformation.
 * 
 * @package Lyre\Strings\Resource\Concerns
 */
trait HandlesCollections
{
    /**
     * Prepare a collection of resources with optional pagination.
     *
     * @param mixed $collection
     * @param string $resource
     * @param bool $paginate
     * @return array|\Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public static function prepareCollection($collection, $resource, $paginate = true)
    {
        if (!$paginate) {
            return $resource::collection($collection);
        }

        $paginationData = [
            'current_page' => $collection->currentPage(),
            'last_page' => $collection->lastPage(),
            'per_page' => $collection->perPage(),
            'total' => $collection->total(),
            'links' => $collection->links()->elements,
        ];

        return ['data' => $resource::collection($collection), 'meta' => $paginationData];
    }
}
