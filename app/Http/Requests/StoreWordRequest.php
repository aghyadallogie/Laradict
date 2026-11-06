<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreWordRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'text_to_translate' => ['required', 'string', 'min:1', 'max:500'],
        ];
    }
}
