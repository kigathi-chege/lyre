<?php

namespace Lyre\Strings\Traits;

/**
 * Trait for handling column inclusion and exclusion.
 * 
 * This trait provides methods for managing which columns
 * should be included or excluded from serialization.
 * 
 * @package Lyre\Strings\Traits
 */
trait CanIncludeColumns
{
    /**
     * Get included columns.
     *
     * @return array
     */
    public function getIncluded(): array
    {
        return $this->included ?? [];
    }

    /**
     * Get excluded columns.
     *
     * @return array
     */
    public function getExcluded(): array
    {
        return $this->excluded ?? [];
    }

    /**
     * Set included columns.
     *
     * @param array $columns
     * @return void
     */
    public function setIncluded(array $columns): void
    {
        $this->included = $columns;
    }

    /**
     * Set excluded columns.
     *
     * @param array $columns
     * @return void
     */
    public function setExcluded(array $columns): void
    {
        $this->excluded = $columns;
    }
}
