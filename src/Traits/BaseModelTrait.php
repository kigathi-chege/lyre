<?php

namespace Lyre\Traits;

trait BaseModelTrait
{
    const ID_COLUMN = 'id';
    const NAME_COLUMN = 'name';

    public function getFillableAttributes()
    {
        return $this->fillable;
    }

    public function getClassName()
    {
        $className = static::class;
        $classNameParts = explode('\\', $className);
        return end($classNameParts);
    }

    public function generateConfig()
    {
        $config = [];
        $class = $this->getClassName();

        $config['model'] = static::class;

        $resourceClass = "\\App\\Http\\Resources\\{$class}";
        $config['resource'] = class_exists($resourceClass) ? $resourceClass : null;

        $repositoryClass = "\\App\\Repositories\\{$class}Repository";
        $config['repository'] = class_exists($repositoryClass) ? $repositoryClass : null;

        $repositoryInterfaceClass = "\\App\\Repositories\\Interface\\{$class}RepositoryInterface";
        $config['repository-interface'] = interface_exists($repositoryInterfaceClass) ? $repositoryInterfaceClass : null;

        $storeRequestClass = "\\App\\Http\\Requests\\Store{$class}Request";
        $config['store-request'] = class_exists($storeRequestClass) ? $storeRequestClass : null;

        $updateRequestClass = "\\App\\Http\\Requests\\Update{$class}Request";
        $config['update-request'] = class_exists($updateRequestClass) ? $updateRequestClass : null;

        $config['table'] = $this->getTable();

        $config['name'] = $this::NAME_COLUMN;

        $config['id'] = $this::ID_COLUMN;

        return $config;
    }

    public function searcheableRelations()
    {
        return [];
    }
}
