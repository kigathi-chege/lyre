<?php

namespace Lyre\Strings\Model\Concerns;

/**
 * Handles model configuration and metadata.
 * 
 * This concern provides methods for generating and managing
 * model configuration including resource, repository, and request mappings.
 * 
 * @package Lyre\Strings\Model\Concerns
 */
trait HandlesConfiguration
{
    /**
     * Generate complete model configuration.
     *
     * @return array
     */
    public static function generateConfig(): array
    {
        $config = [];
        $config['model'] = static::getModelNameConfig();
        $config['resource'] = static::getResourceConfig();
        $config['repository'] = static::getRepositoryConfig();
        $config['repository-interface'] = static::getRepositoryInterfaceConfig();
        $config['store-request'] = static::getStoreRequestConfig();
        $config['update-request'] = static::getUpdateRequestConfig();
        $config['order-column'] = static::ORDER_COLUMN;
        $config['order-direction'] = static::ORDER_DIRECTION;
        $config['status'] = static::STATUS_CONFIG;
        $config['table'] = (new static())->getTable();
        $config['name'] = static::NAME_COLUMN;
        $config['id'] = static::ID_COLUMN;

        return $config;
    }

    /**
     * Get model name configuration.
     *
     * @return string
     */
    public static function getModelNameConfig(): string
    {
        return static::class;
    }

    /**
     * Get resource configuration.
     *
     * @return string|null
     */
    public static function getResourceConfig(): ?string
    {
        return self::resolveNamespacedClass(config('lyre.path.resource'));
    }

    /**
     * Get repository configuration.
     *
     * @return string|null
     */
    public static function getRepositoryConfig(): ?string
    {
        return self::resolveNamespacedClass(
            baseNamespace: config('lyre.path.repository'),
            suffix: 'Repository'
        );
    }

    /**
     * Get repository interface configuration.
     *
     * @return string|null
     */
    public static function getRepositoryInterfaceConfig(): ?string
    {
        return self::resolveNamespacedClass(
            baseNamespace: config('lyre.path.contracts'),
            suffix: 'RepositoryInterface',
            checkInterface: true
        );
    }

    /**
     * Get store request configuration.
     *
     * @return string|null
     */
    public static function getStoreRequestConfig(): ?string
    {
        return self::resolveNamespacedClass(
            baseNamespace: config('lyre.path.request'),
            prefix: 'Store',
            suffix: 'Request'
        );
    }

    /**
     * Get update request configuration.
     *
     * @return string|null
     */
    public static function getUpdateRequestConfig(): ?string
    {
        return self::resolveNamespacedClass(
            baseNamespace: config('lyre.path.request'),
            prefix: 'Update',
            suffix: 'Request'
        );
    }

    /**
     * Resolve namespaced class based on configuration.
     *
     * @param string|array $baseNamespace
     * @param string $prefix
     * @param string $suffix
     * @param bool $checkInterface
     * @return string|null
     */
    protected static function resolveNamespacedClass(string|array $baseNamespace, string $prefix = '', string $suffix = '', bool $checkInterface = false): ?string
    {
        $class = self::getClassName();
        $relativeNamespace = self::getRelativeNamespace();

        if (is_array($baseNamespace)) {
            foreach ($baseNamespace as $namespace) {
                $fullClass = self::retrieveNamespacedClass($namespace, $relativeNamespace, $class, $prefix, $suffix, $checkInterface);
                if ($fullClass) break;
            }

            return $fullClass;
        }

        return self::retrieveNamespacedClass($baseNamespace, $relativeNamespace, $class, $prefix, $suffix, $checkInterface);
    }

    /**
     * Retrieve namespaced class.
     *
     * @param string|array $baseNamespace
     * @param string $relativeNamespace
     * @param string $class
     * @param string $prefix
     * @param string $suffix
     * @param bool $checkInterface
     * @return string|null
     */
    public static function retrieveNamespacedClass(string|array $baseNamespace, string $relativeNamespace = '', string $class = '', string $prefix = '', string $suffix = '', bool $checkInterface = false): ?string
    {
        $fullClass = "\\" . trim($baseNamespace, "\\") . ($relativeNamespace ? "\\{$relativeNamespace}" : "") . "\\{$prefix}{$class}{$suffix}";

        if ($checkInterface && interface_exists($fullClass)) {
            return $fullClass;
        }

        if (!$checkInterface && class_exists($fullClass)) {
            return $fullClass;
        }

        return null;
    }
}
