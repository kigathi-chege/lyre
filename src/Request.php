<?php

namespace Kigathi\Lyre;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Response;

class Request extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            curate_response(false, "Validation Errors", $validator->errors(), Response::HTTP_UNPROCESSABLE_ENTITY)
        );
    }
}