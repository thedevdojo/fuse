<?php

namespace Devdojo\Fuse;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Devdojo\Fuse\Skeleton\SkeletonClass
 */
class FuseFacade extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'fuse';
    }
}
