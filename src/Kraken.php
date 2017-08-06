<?php
namespace Pepijnolivier\Kraken;

use Illuminate\Support\Facades\Facade;

class Kraken extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    public static function getFacadeAccessor()
    {
        return 'kraken';
    }
}
