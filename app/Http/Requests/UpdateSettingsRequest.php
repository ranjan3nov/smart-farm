<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<mixed>> */
    public function rules(): array
    {
        return [
            'farm_name' => ['required', 'string', 'max:100'],
            'plant_name' => ['nullable', 'string', 'max:100'],
            'latitude' => ['nullable', 'numeric', 'min:-90', 'max:90'],
            'longitude' => ['nullable', 'numeric', 'min:-180', 'max:180'],
            'tank_height_cm' => ['required', 'integer', 'min:10', 'max:1000'],
            'moisture_threshold' => ['required', 'integer', 'min:0', 'max:100'],
            'moisture_max' => ['required', 'integer', 'min:0', 'max:100', 'gt:moisture_threshold'],
            'ai_decision_interval' => ['required', 'integer', 'min:1', 'max:60'],
            'send_interval' => ['required', 'integer', 'min:10', 'max:3600'],
        ];
    }
}
