<?php

namespace Lyre\Providers;

use Illuminate\Foundation\AliasLoader;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Lyre\Console\Commands\MakeAllCommand;
use Lyre\Console\Commands\CacheModelRelationships;
use Lyre\Console\Commands\MakeRepositoryCommand;
use Lyre\Console\Commands\PublishStubsCommand;
use Lyre\Console\Commands\TruncateTableCommand;
use Lyre\Facades\Lyre;
use Lyre\Services\ModelService;

class LyreServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $loader = AliasLoader::getInstance();
        $loader->alias('Lyre', Lyre::class);

        $this->app->singleton('lyre', function ($app) {
            return new ModelService();
        });

        register_repositories($this->app, 'App\\Repositories', 'App\\Repositories\\Interface');

        $this->commands([MakeAllCommand::class, MakeRepositoryCommand::class, PublishStubsCommand::class, TruncateTableCommand::class, CacheModelRelationships::class]);

        require_once base_path('vendor/lyre/lyre/src/helpers/helpers.php');
        $this->mergeConfigFrom(
            base_path('vendor/lyre/lyre/src/config/response-codes.php'),
            'response-codes'
        );
    }

    public function boot(): void
    {
        /**
         * TODO: Kigathi - April 25 2025
         * For the content package, we might need this:
         * https://laravel.com/docs/12.x/authorization#manually-registering-policies
         */

        $usingSpatieRoles = in_array(\Spatie\Permission\Traits\HasRoles::class, class_uses(\App\Models\User::class));

        if ($usingSpatieRoles) {
            Gate::before(function ($user, $ability) {
                return $user->hasRole(config('lyre.super-admin') ?? 'super-admin') ? true : null;
            });
        }

        register_global_observers("App\\Models");

        $this->publishes([
            __DIR__ . '/../config/lyre.php' => config_path('lyre.php'),
        ]);
    }
}
