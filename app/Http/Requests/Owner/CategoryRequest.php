<?php

namespace App\Http\Requests\Owner;

use Illuminate\Foundation\Http\FormRequest;

// One class handles both store and update by checking the HTTP method.
// Cuts boilerplate vs separate Store/Update classes for a tiny resource.
class CategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $isUpdate = $this->isMethod('PATCH') || $this->isMethod('PUT');

        return [
            // restaurant_id only required on create; on update the row is fixed.
            'restaurant_id' => [$isUpdate ? 'sometimes' : 'required', 'integer', 'exists:restaurants,id'],
            'name' => [$isUpdate ? 'sometimes' : 'required', 'string', 'max:100'],
            'sort_order' => ['sometimes', 'integer', 'min:0'],
        ];
    }
}
