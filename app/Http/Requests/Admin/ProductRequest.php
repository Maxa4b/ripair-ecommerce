<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class ProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:160'],
            'slug' => ['required', 'string', 'max:160'],
            'category_id' => ['required', 'exists:categories,id'],
            'brand_id' => ['nullable', 'exists:brands,id'],
            'product_type_id' => ['nullable', 'exists:product_types,id'],
            'internal_reference' => ['required', 'string', 'max:60'],
            'supplier_reference' => ['nullable', 'string', 'max:60'],
            'short_description' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'quality' => ['required', 'in:origine,premium,standard,reconditionne'],
            'warranty_months' => ['required', 'integer', 'min:1', 'max:36'],
            'is_published' => ['boolean'],
            'is_best_seller' => ['boolean'],
        ];
    }
}
