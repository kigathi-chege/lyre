<?php

namespace Lyre\Providers;

use Illuminate\Foundation\AliasLoader;
use Illuminate\Support\Facades\File;
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

        $this->commands(MakeAllCommand::class);
        $this->commands(MakeRepositoryCommand::class);
        $this->commands(PublishStubsCommand::class);

        require_once base_path('vendor/lyre/lyre/src/helpers/helpers.php');
        $this->mergeConfigFrom(
            base_path('vendor/lyre/lyre/src/config/response-codes.php'), 'response-codes'
        );
    }

    public function boot(): void
    {
        $this->registerGlobalObserver();

        $this->publishes([
            __DIR__ . '/../config/lyre.php' => config_path('lyre.php'),
        ]);
    }

    public function registerRepositories($app)
    {
        if (!file_exists(app_path('Repositories'))) {
            File::makeDirectory(app_path('Repositories'));
        }

        if (!file_exists(app_path('Repositories/Interface'))) {
            File::makeDirectory(app_path('Repositories/Interface'));
        }

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
        $MODELS = collect(Lyre::getModelClasses());

        $observersPath = app_path("Observers");
        $observers = scandir($observersPath);

        foreach ($observers as $observer) {
            if ($observer === '.' || $observer === '..' || $observer === 'BaseObserver.php') {
                continue;
            }
            $observerName = str_replace('.php', '', $observer);
            $observerClass = "App\Observers\\{$observerName}";
            $modelName = str_replace('Observer.php', '', $observer);
            $modelClass = "App\Models\\{$modelName}";
            $modelClass::observe($observerClass);
            $MODELS->forget($modelName);
        }

        foreach ($MODELS as $MODEL) {
            $MODEL::observe(Observer::class);
        }
    }
}
