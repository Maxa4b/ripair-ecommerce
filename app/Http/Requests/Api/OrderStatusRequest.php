<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class OrderStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', 'in:pending_payment,paid,preparing,shipped,delivered,cancelled'],
            'comment' => ['nullable', 'string', 'max:500'],
        ];
    }
}
