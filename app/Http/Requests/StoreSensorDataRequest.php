<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSensorDataRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<string>> */
    public function rules(): array
    {
        return [
            'moisture' => ['nullable', 'integer', 'min:0', 'max:4095'],
            'rain' => ['nullable', 'integer', 'min:0', 'max:4095'],
            'temp' => ['nullable', 'numeric'],
            'humidity' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'water_dist' => ['nullable', 'numeric', 'min:0'],
            'tank_status' => ['nullable', 'in:OK,EMPTY'],
        ];
    }
}
