<?php

namespace JeffersonGoncalves\LgpdMinors;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Blade;
use JeffersonGoncalves\LgpdMinors\Commands\MinorsCommand;
use JeffersonGoncalves\LgpdMinors\Http\Middleware\BlockSuspended;
use JeffersonGoncalves\LgpdMinors\Http\Middleware\EnsureVerifiedAdult;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class LgpdMinorsServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('lgpd-minors')
            ->hasConfigFile()
            ->hasMigrations(['create_lgpd_minors_tables'])
            ->hasCommand(MinorsCommand::class);
    }

    public function packageRegistered(): void
    {
        $this->app->singleton(MinorsManager::class);
    }

    public function packageBooted(): void
    {
        $router = $this->app->make(Router::class);
        $router->aliasMiddleware('lgpd.adult', EnsureVerifiedAdult::class);
        $router->aliasMiddleware('lgpd.suspended', BlockSuspended::class);

        Blade::if('lgpdProfiling', function (): bool {
            $user = auth()->user();

            return $this->app->make(MinorsManager::class)->allowsProfiling($user instanceof Model ? $user : null);
        });
    }
}
