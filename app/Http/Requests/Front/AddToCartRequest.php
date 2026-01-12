<?php

namespace App\Http\Requests\Front;

use Illuminate\Foundation\Http\FormRequest;

class AddToCartRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'variant_id' => ['required_without:repair_id', 'nullable', 'integer', 'exists:product_variants,id'],
            'repair_id' => ['required_without:variant_id', 'nullable', 'integer', 'exists:repairs,id'],
            'quantity' => ['required', 'integer', 'min:1', 'max:10'],
        ];
    }
}
