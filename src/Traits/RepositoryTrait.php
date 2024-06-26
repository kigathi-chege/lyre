<?php

namespace Lyre\Traits;

use Illuminate\Support\Facades\File;

trait RepositoryTrait
{
    protected function createRepositoryInterface($repositoryName)
    {
        $interfaceDirectory = app_path("Repositories/Interface");
        $interfacePath = "{$interfaceDirectory}/{$repositoryName}RepositoryInterface.php";
        if (!File::exists($interfaceDirectory)) {
            File::makeDirectory($interfaceDirectory, 0755, true);
        }

        $stub = file_get_contents(base_path('vendor/lyre/lyre/src/stubs/repository-interface.stub'));
        if (config('app.lyre')) {
            $stub = file_get_contents(base_path('packages/lyre/src/stubs/repository-interface.stub'));
        }

        $stub = str_replace('{{repositoryName}}', $repositoryName, $stub);
        File::put($interfacePath, $stub);
        $this->info("Repository Interface created: $interfacePath");
    }

    protected function createRepositoryClass($repositoryName)
    {
        $repositoryDirectory = app_path("Repositories");
        $repositoryPath = "{$repositoryDirectory}/{$repositoryName}Repository.php";
        if (!File::exists($repositoryDirectory)) {
            File::makeDirectory($repositoryDirectory, 0755, true);
        }

        $stub = file_get_contents(base_path('vendor/lyre/lyre/src/stubs/repository.stub'));
        if (config('app.lyre')) {
            $stub = file_get_contents(base_path('packages/lyre/src/stubs/repository.stub'));
        }

        $stub = str_replace('{{repositoryName}}', $repositoryName, $stub);
        File::put($repositoryPath, $stub);
        $this->info("Repository Class created: $repositoryPath");
    }
}
