<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSensorDataRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        // Devices (e.g. Python/MicroPython) may send the string "nan" when a
        // sensor read fails. Treat those as missing values rather than letting
        // them fail the `numeric` rule and reject the whole request.
        $this->merge(array_map(
            fn ($v) => (is_string($v) && strtolower($v) === 'nan') ? null : $v,
            $this->all()
        ));
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
