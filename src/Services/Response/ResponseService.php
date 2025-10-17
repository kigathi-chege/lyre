<?php

namespace Lyre\Strings\Services\Response;

use Symfony\Component\HttpFoundation\Response;

/**
 * Service class for response handling.
 * 
 * This service provides methods for creating standardized API responses
 * with proper formatting and error handling.
 * 
 * @package Lyre\Strings\Services\Response
 */
class ResponseService
{
    /**
     * Create a standardized response.
     *
     * @param bool $status
     * @param string $message
     * @param mixed $result
     * @param int $code
     * @param mixed $trace
     * @param array $extra
     * @param array $headers
     * @param bool $forgetGuestUuid
     * @return \Illuminate\Http\JsonResponse
     */
    public function create(
        $status,
        $message,
        $result,
        $code = 200,
        $trace = false,
        array $extra = [],
        array $headers = [],
        bool $forgetGuestUuid = false
    ): \Illuminate\Http\JsonResponse {
        $responseData = array_merge([
            "status" => $status,
            "message" => $message,
            "result" => $result,
            "code" => $code,
        ], $extra);

        if ($trace !== false && env("APP_DEBUG", false)) {
            $responseData['trace'] = $trace;
        }

        $httpCode = $status
            ? 200
            : (isset(Response::$statusTexts[$code])
                ? $code
                : ($code == 0
                    ? Response::HTTP_INTERNAL_SERVER_ERROR
                    : Response::HTTP_EXPECTATION_FAILED));

        $jsonResponse = response()->json($responseData, $httpCode, $headers);

        if ($forgetGuestUuid) {
            $jsonResponse->withCookie(\Illuminate\Support\Facades\Cookie::forget('guest_uuid'));
        }

        return $jsonResponse;
    }

    /**
     * Parse validation error response.
     *
     * @param mixed $errors
     * @return \Illuminate\Http\JsonResponse
     */
    public function parseValidationErrorResponse($errors): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            "status" => false,
            "message" => "Validation errors",
            "response" => $errors,
        ]);
    }

    /**
     * Generate basic model response codes.
     *
     * @return array
     */
    public function generateBasicModelResponseCodes(): array
    {
        $responseCodes = [];
        $responseCode = 10001;
        $modelClasses = app(\Lyre\Strings\Services\Model\ModelService::class)->getModelClasses();

        foreach ($modelClasses as $modelClass) {
            if (method_exists($modelClass, 'generateConfig')) {
                $config = $modelClass::generateConfig();
                $pluralName = $config['table'];
                $name = \Illuminate\Support\Pluralizer::singular($pluralName);
                $responseCodes += [
                    $responseCode++ => "get-{$pluralName}",
                    $responseCode++ => "find-{$name}",
                    $responseCode++ => "create-{$name}",
                    $responseCode++ => "update-{$name}",
                    $responseCode++ => "destroy-{$name}",
                    $responseCode++ => "restore-{$name}",
                ];
            }
        }
        return $responseCodes;
    }

    /**
     * Get response code for a given response type.
     *
     * @param string $response
     * @return int
     */
    public function getResponseCode($response): int
    {
        $staticCodes = config('response-codes');
        $dynamicCodes = $this->generateBasicModelResponseCodes();
        $responseCodes = $staticCodes + $dynamicCodes;
        $code = array_search($response, $responseCodes);
        return $code ?? $responseCodes[0000];
    }
}
