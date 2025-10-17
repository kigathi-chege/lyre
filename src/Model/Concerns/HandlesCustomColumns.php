<?php

namespace Lyre\Strings\Model\Concerns;

/**
 * Handles custom columns for models.
 * 
 * This concern provides methods for managing custom columns
 * and global custom column settings.
 * 
 * @package Lyre\Strings\Model\Concerns
 */
trait HandlesCustomColumns
{
    /**
     * Custom columns for this instance.
     *
     * @var array
     */
    protected $customColumns = [];

    /**
     * Global custom columns for all instances.
     *
     * @var array
     */
    protected static $globalCustomColumns = [];

    /**
     * Set global custom columns.
     *
     * @param array $columns
     * @return void
     */
    public static function setGlobalCustomColumns(array $columns): void
    {
        static::$globalCustomColumns = $columns;
    }

    /**
     * Resolve custom columns for this instance.
     *
     * @return array
     */
    public function resolveCustomColumns(): array
    {
        return $this->customColumns ?: static::$globalCustomColumns;
    }

    /**
     * Set custom columns for this instance.
     *
     * @param array $columns
     * @return void
     */
    public function setCustomColumns(array $columns): void
    {
        $this->customColumns = $columns;
    }
}
