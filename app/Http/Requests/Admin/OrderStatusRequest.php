<?php

namespace App\Http\Requests\Admin;

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
            'tracking_number' => ['nullable', 'string', 'max:80'],
            'carrier_name' => ['nullable', 'string', 'max:80'],
            'internal_note' => ['nullable', 'string', 'max:500'],
        ];
    }
}
