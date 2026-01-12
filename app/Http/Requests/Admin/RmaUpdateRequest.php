<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class RmaUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', 'in:received,in_review,accepted,refused,refunded,replaced'],
            'comment' => ['nullable', 'string', 'max:500'],
        ];
    }
}
