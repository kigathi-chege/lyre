<?php

namespace Lyre\Strings\Controller\Concerns;

/**
 * Handles authorization for controllers.
 * 
 * This concern provides methods for handling authorization
 * including global and local authorization checks.
 * 
 * @package Lyre\Strings\Controller\Concerns
 */
trait HandlesAuthorization
{
    /**
     * Apply global authorization to the controller.
     *
     * @param array $except
     * @return void
     */
    public function globalAuthorize(array $except = []): void
    {
        $this->authorizeResource($this->modelConfig, $this->modelName, [
            'except' => empty($except) ? ['show', 'update', 'destroy'] : $except,
        ]);
    }

    /**
     * Apply local authorization for specific actions.
     *
     * @param string $ability
     * @param mixed $identifier
     * @param callable|null $findCallback
     * @return mixed
     */
    public function localAuthorize($ability, $identifier, $findCallback = null)
    {
        $modelResource = $this->modelRepository
            ->silent()
            ->find([$this->modelConfig['id'] => $identifier], $findCallback);
        $model = $modelResource->resource ?? null;
        $this->authorize($ability, $model);
        return $modelResource;
    }
}
