<?php

namespace Lyre\Traits;

trait CanIncludeColumns
{
    protected static $excludedSerializableColumns = [];
    protected static $includedSerializableColumns = [];
    protected array $excluded = [];
    protected array $included = [];

    public function setExluded($columns = [])
    {
        $this->excluded = $columns;
    }

    public function setIncluded($columns = [])
    {
        $this->included = $columns;
    }

    public function getIncluded()
    {
        return array_merge($this->included, $this->getIncludedSerializableColumns(), $this->getVisible());
    }

    public function getExcluded()
    {
        return array_merge($this->excluded, $this->getExcludedSerializableColumns(), $this->getHidden());
    }

    public static function setExcludedSerializableColumns($columns = [])
    {
        static::$excludedSerializableColumns = array_merge(static::$excludedSerializableColumns, [static::class => $columns]);
    }

    public function getExcludedSerializableColumns()
    {
        $filtered = array_filter(static::$excludedSerializableColumns, function ($_, $key) {
            return !is_int($key);
        }, ARRAY_FILTER_USE_BOTH);

        $currentExclusions = [];

        if (count($filtered) > 0) {
            $currentExclusions = collect($filtered)->filter(fn($_, $key) =>  $key == $this::class)->flatten()->values()->toArray();
        }

        return array_merge($currentExclusions, $this->getHidden());
    }

    public static function setIncludedSerializableColumns($columns = [])
    {
        static::$includedSerializableColumns = array_merge(static::$includedSerializableColumns, [static::class => $columns]);
    }

    public function getIncludedSerializableColumns()
    {
        $filtered = array_filter(static::$includedSerializableColumns, function ($_, $key) {
            return !is_int($key);
        }, ARRAY_FILTER_USE_BOTH);

        $currentInclusions = [];

        if (count($filtered) > 0) {
            $currentInclusions = collect($filtered)->filter(fn($_, $key) =>  $key == $this::class)->flatten()->values()->toArray();
        }

        return array_merge($currentInclusions, $this->getVisible());
    }
}
