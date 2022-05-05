<?php

namespace LasseLehtinen\Assets\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \LasseLehtinen\Assets\Assets
 */
class Assets extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'woodwing-assets';
    }
}
