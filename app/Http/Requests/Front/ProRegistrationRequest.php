<?php

namespace App\Http\Requests\Front;

use Illuminate\Foundation\Http\FormRequest;

class ProRegistrationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'company_name' => ['required', 'string', 'max:120'],
            'siret' => ['required', 'string', 'size:14'],
            'vat_number' => ['nullable', 'string', 'max:20'],
            'contact_name' => ['required', 'string', 'max:80'],
            'email' => ['required', 'email'],
            'phone' => ['required', 'string', 'max:25'],
            'message' => ['nullable', 'string', 'max:500'],
        ];
    }
}
