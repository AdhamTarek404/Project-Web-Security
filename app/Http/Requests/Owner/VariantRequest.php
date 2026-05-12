<?php

namespace App\Http\Requests\Owner;

use Illuminate\Foundation\Http\FormRequest;

class VariantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $isUpdate = $this->isMethod('PATCH') || $this->isMethod('PUT');

        return [
            'menu_item_id' => [$isUpdate ? 'sometimes' : 'required', 'integer', 'exists:menu_items,id'],
            'name' => [$isUpdate ? 'sometimes' : 'required', 'string', 'max:50'],
            // SIGNED so a variant can also discount (negative modifier).
            'price_modifier' => [$isUpdate ? 'sometimes' : 'required', 'integer', 'between:-1000000,1000000'],
            'is_default' => ['sometimes', 'boolean'],
        ];
    }
}
