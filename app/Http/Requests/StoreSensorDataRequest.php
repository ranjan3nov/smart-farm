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
            'moisture' => ['required', 'integer', 'min:0', 'max:4095'],
            'rain' => ['required', 'integer', 'min:0', 'max:4095'],
            'temp' => ['required', 'numeric'],
            'humidity' => ['required', 'numeric', 'min:0', 'max:100'],
            'water_dist' => ['required', 'numeric', 'min:0'],
            'tank_status' => ['required', 'in:OK,EMPTY'],
        ];
    }
}
