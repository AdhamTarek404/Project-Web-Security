<?php

namespace App\Http\Requests\Owner;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRestaurantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // 'sometimes' = field is validated ONLY if it's present in the request.
        // Lets the owner do a partial update (just toggle is_open, for example)
        // without re-sending every field.
        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'address' => ['sometimes', 'string', 'max:255'],
            'latitude' => ['sometimes', 'numeric', 'between:-90,90'],
            'longitude' => ['sometimes', 'numeric', 'between:-180,180'],
            'commission_rate' => ['sometimes', 'numeric', 'between:0,50'],
            'is_open' => ['sometimes', 'boolean'],
        ];
    }
}
