<?php

namespace Lyre\Strings\Services\Validation;

use Lyre\Strings\Exceptions\CommonException;

/**
 * Service class for validation operations.
 * 
 * This service provides methods for validating data and
 * handling validation-related operations.
 * 
 * @package Lyre\Strings\Services\Validation
 */
class ValidationService
{
    /**
     * Get status code for a given status and model.
     *
     * @param string $status
     * @param mixed $model
     * @return mixed
     * @throws CommonException
     */
    public function getStatusCode($status, $model)
    {
        $configPath = config("models.{$model->getTable()}.status") ?? 'constant.status';
        $config = config($configPath);
        if (!$config) {
            throw CommonException::fromMessage("Status config not found for model {$model->getTable()}");
        }
        if (!is_array($config)) {
            throw CommonException::fromMessage("Status config must be an array");
        }
        if ($this->isArrayAssociative($config)) {
            $code = ($config[$status] ?? throw CommonException::fromMessage("Status `{$status}` not found for model {$model->getTable()}"));
        } else {
            $code = in_array($status, $config) ? $status : throw CommonException::fromMessage("Status `{$status}` not found for model {$model->getTable()}");
        }
        return $code;
    }

    /**
     * Check if an array is associative.
     *
     * @param array $array
     * @return bool
     * @throws CommonException
     */
    public function isArrayAssociative($array): bool
    {
        if (!is_array($array)) {
            throw CommonException::fromMessage("Argument must be an array");
        }
        $keys = array_keys($array);
        return array_keys($keys) !== $keys;
    }

    /**
     * Check if a value is not a number.
     *
     * @param mixed $value
     * @return bool
     */
    public function isNotNumeric($value): bool
    {
        return !is_numeric($value) || !ctype_digit((string) $value);
    }

    /**
     * Check if a string is valid JSON.
     *
     * @param mixed $string
     * @return bool
     */
    public function isJson($string): bool
    {
        if (!is_string($string)) {
            return false;
        }
        json_decode($string);
        return json_last_error() === JSON_ERROR_NONE;
    }
}
