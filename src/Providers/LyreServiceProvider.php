<?php

namespace Lyre\Providers;

use Illuminate\Foundation\AliasLoader;
use Illuminate\Support\ServiceProvider;
use Lyre\Console\Commands\MakeAllCommand;
use Lyre\Console\Commands\MakeRepositoryCommand;
use Lyre\Console\Commands\PublishStubsCommand;
use Lyre\Facades\Lyre;
use Lyre\Observer;
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

        $this->registerRepositories($this->app);

        $this->registerGlobalObserver();

        $this->commands(MakeAllCommand::class);
        $this->commands(MakeRepositoryCommand::class);
        $this->commands(PublishStubsCommand::class);

        if (config('app.lyre')) {
            require_once base_path('packages/lyre/src/helpers/helpers.php');
            $this->mergeConfigFrom(
                base_path('packages/lyre/src/config/response-codes.php'), 'response-codes'
            );
        } else {
            require_once base_path('vendor/lyre/lyre/src/helpers/helpers.php');
            $this->mergeConfigFrom(
                base_path('vendor/lyre/lyre/src/config/response-codes.php'), 'response-codes'
            );
        }
    }

    public function boot(): void
    {}

    public function registerRepositories($app)
    {
        $repositoryPath = app_path("Repositories");
        $interfacePath = app_path("Repositories/Interface");

        $repositories = scandir($repositoryPath);
        $interfaces = scandir($interfacePath);

        foreach ($repositories as $repository) {
            if ($repository === '.' || $repository === '..' || $repository === 'BaseRepository.php') {
                continue;
            }
            $interfaceFile = str_replace('Repository.php', 'RepositoryInterface.php', $repository);
            if (in_array($interfaceFile, $interfaces)) {
                $interface = 'App\Repositories\Interface\\' . str_replace('.php', '', $interfaceFile);
                $implementation = 'App\Repositories\\' . str_replace('.php', '', $repository);

                $app->bind($interface, function ($app) use ($implementation) {
                    return $app->make($implementation);
                });
            }
        }
    }

    private static function registerGlobalObserver()
    {
        /** @var \Illuminate\Database\Eloquent\Model[] $MODELS */
        $MODELS = collect(Lyre::getModelClasses());

        // TODO: Kigathi - April 15 2024 - Let your service provider offer a way to exempt all or some models from the global observer

        foreach ($MODELS as $MODEL) {
            $MODEL::observe(Observer::class);
        }
    }
}
