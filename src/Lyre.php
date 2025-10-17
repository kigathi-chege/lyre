<?php

namespace Lyre;

use Lyre\Facades\Lyre as LyreFacade;

/**
 * Main Lyre class for backward compatibility.
 * 
 * This class provides backward compatibility while delegating
 * to the new Lyre facade for all functionality.
 * 
 * @package Lyre
 * @deprecated Use Lyre\Facades\Lyre instead
 */
class Lyre
{
    /**
     * Get model classes.
     *
     * @param string|null $baseNamespace
     * @return array
     */
    public function modelClasses($baseNamespace = null): array
    {
        return LyreFacade::getModelClasses($baseNamespace);
    }

    /**
     * Get model resource.
     *
     * @param mixed $model
     * @return string
     */
    public function getModelResource($model): string
    {
        return LyreFacade::getModelResource($model);
    }
}
