<?php

namespace JeffersonGoncalves\LgpdMinors\Tests;

use JeffersonGoncalves\LgpdMinors\LgpdMinorsServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            LgpdMinorsServiceProvider::class,
        ];
    }
}
