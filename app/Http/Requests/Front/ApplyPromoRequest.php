<?php

namespace App\Http\Requests\Front;

use Illuminate\Foundation\Http\FormRequest;

class ApplyPromoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:30'],
        ];
    }

    public function messages(): array
    {
        return [
            'code.required' => 'Le code promo est requis.',
            'code.string' => 'Le code promo doit être une chaîne de caractères.',
            'code.max' => 'Le code promo ne peut pas dépasser :max caractères.',
        ];
    }
}
