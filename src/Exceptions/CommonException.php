<?php

namespace Lyre\Exceptions;

use Exception;

class CommonException extends Exception
{
    public static function fromCode($code, $arguments = [])
    {
        $message = trans("errors.{$code}", $arguments);
        return new static($message, $code);
    }

    public static function of($code, $arguments = [])
    {
        $message = trans("errors.{$code}", $arguments);
        return new static($message, $code);
    }

    public static function fromMessage($message, $code = 500)
    {
        return new static($message, $code);
    }
}
