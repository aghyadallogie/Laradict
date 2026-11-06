<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateLanguagesRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'languages' => ['nullable', 'array'],
            'languages.*' => ['string', Rule::in(array_keys(config('languages')))],
        ];
    }
}
