<?php

namespace Lyre\Strings;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Base request class for the Strings package.
 * 
 * This class extends Laravel's FormRequest to provide
 * additional functionality for the Strings package.
 * 
 * @package Lyre\Strings
 */
class Request extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [];
    }
}
