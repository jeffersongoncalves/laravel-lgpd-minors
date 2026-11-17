<?php

namespace JeffersonGoncalves\LgpdMinors\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \JeffersonGoncalves\LgpdMinors\LgpdMinors
 */
class LgpdMinors extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'laravel-lgpd-minors';
    }
}
