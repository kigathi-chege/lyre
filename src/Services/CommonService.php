<?php

namespace Lyre\Services;

class CommonService
{
    public static function getResponseCode($response)
    {
        $response_codes = config('response-codes');
        $code = array_search($response, $response_codes);
        return $code ?? $response_codes[0000];
    }
}
