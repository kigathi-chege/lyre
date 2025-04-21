<?php

namespace Lyre\Providers;

use Illuminate\Foundation\AliasLoader;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Lyre\Console\Commands\MakeAllCommand;
use Lyre\Console\Commands\MakeRepositoryCommand;
use Lyre\Console\Commands\PublishStubsCommand;
use Lyre\Console\Commands\TruncateTableCommand;
use Lyre\Facades\Lyre;
use Lyre\Observer;
use Lyre\Services\ModelService;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;
use Illuminate\Support\Str;

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
        $this->commands(TruncateTableCommand::class);

        require_once base_path('vendor/lyre/lyre/src/helpers/helpers.php');
        $this->mergeConfigFrom(
            base_path('vendor/lyre/lyre/src/config/response-codes.php'),
            'response-codes'
        );
    }

    public function boot(): void
    {
        Gate::before(function ($user, $ability) {
            return $user->hasRole("super-admin") ? true : null;
        });

        $this->registerGlobalObserver();

        $this->publishes([
            __DIR__ . '/../config/lyre.php' => config_path('lyre.php'),
        ]);
    }

    public function registerRepositories($app)
    {
        if (! file_exists(app_path('Repositories'))) {
            File::makeDirectory(app_path('Repositories'));
        }

        if (! file_exists(app_path('Repositories/Interface'))) {
            File::makeDirectory(app_path('Repositories/Interface'));
        }

        $repositoryPath = app_path("Repositories");
        $interfacePath  = app_path("Repositories/Interface");

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($repositoryPath)
        );

        foreach ($iterator as $file) {
            if (!$file->isFile()) continue;

            $fileName = $file->getFilename();

            // TODO: Kigathi - April 21 2025 - Allow overriding the Repository.php by introducing a BaseRepository.php file at the repositoryPath
            if (!Str::endsWith($fileName, 'Repository.php') || $fileName === 'BaseRepository.php') {
                continue;
            }

            // Get relative path from the Repositories directory
            $relativePath = Str::after($file->getPathname(), $repositoryPath . DIRECTORY_SEPARATOR);
            $namespacePath = str_replace(['/', '\\'], '\\', Str::replaceLast('.php', '', $relativePath));

            // Interface path must match the same relative structure
            $interfaceNamespace = 'App\\Repositories\\Interface\\' . $namespacePath . 'Interface';
            $implementationNamespace = 'App\\Repositories\\' . $namespacePath;

            // Interface file must exist
            $interfaceFilePath = $interfacePath . DIRECTORY_SEPARATOR . Str::replaceLast('Repository.php', 'RepositoryInterface.php', $relativePath);

            if (file_exists($interfaceFilePath)) {
                $app->bind($interfaceNamespace, function ($app) use ($implementationNamespace) {
                    return $app->make($implementationNamespace);
                });
            }
        }
    }

    private static function registerGlobalObserver()
    {
        $MODELS        = collect(get_model_classes()); // ['User' => 'App\Models\User']
        $observersPath = app_path("Observers");
        $baseNamespace = "App\\Observers";

        if (file_exists($observersPath)) {
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($observersPath),
                RecursiveIteratorIterator::LEAVES_ONLY
            );

            foreach ($iterator as $file) {
                if (
                    !$file->isFile() ||
                    $file->getExtension() !== 'php' ||
                    $file->getFilename() === 'BaseObserver.php'
                ) {
                    continue;
                }

                $relativePath = str_replace($observersPath . DIRECTORY_SEPARATOR, '', $file->getPathname());
                $classPath = str_replace(
                    [DIRECTORY_SEPARATOR, '.php'],
                    ['\\', ''],
                    $relativePath
                );

                $observerClass = $baseNamespace . '\\' . $classPath;
                $observerName  = class_basename($observerClass);
                $modelName     = str_replace('Observer', '', $observerName);

                if (isset($MODELS[$modelName]) && class_exists($observerClass)) {
                    $modelClass = $MODELS[$modelName];
                    $modelClass::observe($observerClass);
                    $MODELS->forget($modelName);
                }
            }
        }

        foreach ($MODELS as $MODEL) {
            $MODEL::observe(Observer::class);
        }
    }
}
