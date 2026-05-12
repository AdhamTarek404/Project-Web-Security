<?php

namespace App\Http\Requests\Owner;

use Illuminate\Foundation\Http\FormRequest;

class MenuItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $isUpdate = $this->isMethod('PATCH') || $this->isMethod('PUT');

        return [
            'category_id' => [$isUpdate ? 'sometimes' : 'required', 'integer', 'exists:categories,id'],
            'name' => [$isUpdate ? 'sometimes' : 'required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            // Price is in cents (integer). Min 1 cent, max 1,000,000 cents (10,000 EGP).
            'base_price' => [$isUpdate ? 'sometimes' : 'required', 'integer', 'min:1', 'max:1000000'],
            'image_path' => ['nullable', 'string', 'max:255'],
            'is_available' => ['sometimes', 'boolean'],
        ];
    }
}
