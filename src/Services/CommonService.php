<?php

namespace Lyre\Services;

class CommonService
{
    public static function getResponseCode($response)
    {
        $staticCodes = config('response-codes');
        $dynamicCodes = generate_basic_model_response_codes();
        $responseCodes = $staticCodes + $dynamicCodes;
        $code = array_search($response, $responseCodes);
        return $code ?? $responseCodes[0000];
    }
}
