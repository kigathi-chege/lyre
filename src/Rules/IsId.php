<?php

namespace Lyre\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class IsId implements ValidationRule
{
    public string $modelClass;

    /**
     * Fluent entry point
     */
    public static function make(string $modelClass): self
    {
        if (! is_subclass_of($modelClass, Model::class)) {
            throw new InvalidArgumentException(
                "{$modelClass} must extend " . Model::class
            );
        }

        $rule = new self();
        $rule->modelClass = $modelClass;

        return $rule;
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === null) {
            return;
        }

        $configuredIdColumn = $this->modelClass::ID_COLUMN ?? 'id';

        $existsQuery = $this->modelClass::query();

        if ($this->isNumericId($value)) {
            $existsQuery->where('id', $value);
        } else {
            $existsQuery->where($configuredIdColumn, $value);
        }

        $exists = $existsQuery->exists();

        if (! $exists && $this->isNumericId($value)) {
            $exists = $this->modelClass::where($configuredIdColumn, $value)->exists();
        }

        if (! $exists) {
            $fail('validation.exists')->translate();
        }
    }

    private function isNumericId(mixed $value): bool
    {
        return is_int($value)
            || (is_string($value) && preg_match('/^\d+$/', $value));
    }
}
