<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class TransportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'transporter_id' => ['nullable', 'exists:transporters,id'],
            'name' => ['required', 'string', 'max:120'],
            'code' => ['required', 'string', 'max:40'],
            'type' => ['required', 'in:home,relay,workshop_pickup'],
            'supports_tracking' => ['boolean'],
            'base_price' => ['required', 'numeric', 'min:0'],
            'min_delay_days' => ['required', 'integer', 'min:0'],
            'max_delay_days' => ['required', 'integer', 'min:0'],
        ];
    }
}
