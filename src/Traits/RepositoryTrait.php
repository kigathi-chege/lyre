<?php

namespace Lyre\Traits;

use Illuminate\Support\Facades\File;

trait RepositoryTrait
{
    /**
     * TODO: Kigathi - April 16 2025
     * Introduced changes to handle nested repositories
     * We have an issue with importing the nested repositories
     * inside the controller stubs:
     * /Users/chegekigathi/Projects/lyre/src/stubs/controller.model.api.stub
     * We also have issues with nested resources and model imports
     * 
     * 
     * Issue with repository generation like:
     * 
     * <?php
     *
     *  namespace App\Repositories;
     *
     *  use Lyre\Repository;
     *  use App\Models\Content/Button;
     *  use App\Repositories\Interface\Content/ButtonRepositoryInterface;
     *
     *  class Content/ButtonRepository extends Repository implements Content/ButtonRepositoryInterface
     *  {
     *      protected $model;
     *
     *      public function __construct(Content/Button $model)
     *      {
     *          parent::__construct($model);
     *      }
     *  }
     *
     * Issue namespacing the repository interfaces like:
     * 
     * <?php
     *
     *  namespace App\Repositories\Interface;
     *
     *  use Lyre\Interface\RepositoryInterface;
     *
     *  interface Content/ButtonRepositoryInterface extends RepositoryInterface
     *  {
     *      // Define interface methods here
     *  }
     * 
     * 
     * 
     */

    protected function createRepositoryInterface($repositoryName)
    {
        $relativePath = str_replace('\\', '/', $repositoryName);

        $pathParts = explode('/', $relativePath);
        $repositoryClassName = array_pop($pathParts);
        $subDirectory = implode('/', $pathParts);

        $interfaceDirectory = app_path('Repositories/Interface' . ($subDirectory ? "/{$subDirectory}" : ''));
        $interfacePath = "{$interfaceDirectory}/{$repositoryClassName}RepositoryInterface.php";

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
        $relativePath = str_replace('\\', '/', $repositoryName);

        $pathParts = explode('/', $relativePath);
        $className = array_pop($pathParts);
        $subDirectory = implode('/', $pathParts);

        $repositoryDirectory = app_path('Repositories' . ($subDirectory ? "/{$subDirectory}" : ''));
        $repositoryPath = "{$repositoryDirectory}/{$className}Repository.php";

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
