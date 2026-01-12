<?php

namespace App\Http\Requests\Front;

use Illuminate\Foundation\Http\FormRequest;

class CheckoutPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'payment.provider' => ['required', 'in:stripe,paypal,bank_transfer,dev'],
            'payment.method' => ['required', 'in:card,bank_transfer,paypal,dev'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }
}
