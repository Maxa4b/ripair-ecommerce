<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StockMovementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'product_variant_id' => ['required', 'exists:product_variants,id'],
            'type' => ['required', 'in:in,out,reservation,adjustment,workshop,return'],
            'quantity' => ['required', 'integer'],
            'notes' => ['nullable', 'string', 'max:255'],
        ];
    }
}
