<?php

namespace Kigathi\Lyre\Facades;

use Illuminate\Support\Facades\Facade;

class Lyre extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'lyre';
    }
}