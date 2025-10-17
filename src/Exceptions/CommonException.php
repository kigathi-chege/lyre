<?php

namespace Lyre\Strings\Exceptions;

use Exception;

/**
 * Common exception class for the Strings package.
 * 
 * This exception class provides standardized exception handling
 * with support for error codes and messages.
 * 
 * @package Lyre\Strings\Exceptions
 */
class CommonException extends Exception
{
    /**
     * Create a new exception from a message.
     *
     * @param string $message
     * @param int $code
     * @param \Throwable|null $previous
     * @return static
     */
    public static function fromMessage(string $message, int $code = 0, \Throwable $previous = null): static
    {
        return new static($message, $code, $previous);
    }

    /**
     * Create a new exception from an error code.
     *
     * @param int $code
     * @param array $context
     * @return static
     */
    public static function fromCode(int $code, array $context = []): static
    {
        $message = "Error code: {$code}";
        if (!empty($context)) {
            $message .= " - Context: " . json_encode($context);
        }

        return new static($message, $code);
    }
}
