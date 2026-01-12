<?php

namespace App\Http\Requests\Front;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CheckoutAddressRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'billing_same' => ['nullable', 'boolean'],
            'billing.first_name' => ['required_unless:billing_same,1', 'nullable', 'string', 'max:80'],
            'billing.last_name' => ['required_unless:billing_same,1', 'nullable', 'string', 'max:80'],
            'billing.line1' => ['required_unless:billing_same,1', 'nullable', 'string', 'max:120'],
            'billing.postal_code' => ['required_unless:billing_same,1', 'nullable', 'string', 'max:12'],
            'billing.city' => ['required_unless:billing_same,1', 'nullable', 'string', 'max:80'],
            'billing.country_code' => ['required_unless:billing_same,1', 'nullable', Rule::in(['FR', 'BE', 'LU', 'DE', 'ES', 'IT', 'PT'])],
            'billing.phone' => ['nullable', 'string', 'max:25'],
            'shipping.first_name' => ['required', 'string', 'max:80'],
            'shipping.last_name' => ['required', 'string', 'max:80'],
            'shipping.line1' => ['required', 'string', 'max:120'],
            'shipping.postal_code' => ['required', 'string', 'max:12'],
            'shipping.city' => ['required', 'string', 'max:80'],
            'shipping.country_code' => ['required', Rule::in(['FR', 'BE', 'LU', 'DE', 'ES', 'IT', 'PT'])],
            'shipping.phone' => ['nullable', 'string', 'max:25'],
        ];
    }
}
