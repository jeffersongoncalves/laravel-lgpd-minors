<?php

namespace JeffersonGoncalves\LgpdMinors;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class LgpdMinorsServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('laravel-lgpd-minors')
            ->hasConfigFile()
            ->hasViews()
            ->hasMigrations();
    }
}
