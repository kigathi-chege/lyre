<?php

namespace Lyre\Strings\Controller\Concerns;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Lyre\Strings\Request as BaseRequest;

/**
 * Handles validation for controllers.
 * 
 * This concern provides methods for validating request data
 * and sanitizing input data.
 * 
 * @package Lyre\Strings\Controller\Concerns
 */
trait HandlesValidation
{
    /**
     * Validate request data.
     *
     * @param Request $request
     * @param string $type
     * @return array
     */
    public function validateData(Request $request, $type = "store-request"): array
    {
        if (isset($this->modelConfig[$type]) && class_exists($this->modelConfig[$type])) {
            $modelRequest = $this->modelConfig[$type];
            /**
             * NOTE: This way:
             * You don't lose file handling
             * UploadedFile instances are properly recognized
             * authorize() and prepareForValidation() get called as expected
             * You get all the features of FormRequest, safely
             */
            $modelRequestInstance = app($modelRequest);
            $modelRequestInstance->setContainer(app())->setRedirector(app('redirect'));
            $modelRequestInstance->merge(array_merge($request->post(), $request->file()));
            $modelRequestInstance->validateResolved();
            return $this->sanitizeInputData(array_merge($request->post(), $request->file()), $modelRequestInstance);
        }

        return $request->post();
    }

    /**
     * Sanitize input data.
     *
     * @param array $rawData
     * @param mixed $requestInstance
     * @return array
     */
    public function sanitizeInputData($rawData, $requestInstance): array
    {
        $validator = Validator::make($rawData, $requestInstance->rules(), $requestInstance->messages());
        if ($validator->fails()) {
            $requestInstance->failedValidation($validator);
        }
        return $validator->validated();
    }
}
