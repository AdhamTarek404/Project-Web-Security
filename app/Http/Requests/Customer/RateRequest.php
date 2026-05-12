<?php

namespace App\Http\Requests\Customer;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RateRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            // 'restaurant' or 'rider' — the customer picks which entity
            // they're rating from this order. The controller validates
            // that the target actually belongs to the order.
            'target' => ['required', Rule::in(['restaurant', 'rider'])],
            'stars' => ['required', 'integer', 'min:1', 'max:5'],
            'comment' => ['nullable', 'string', 'max:500'],
        ];
    }
}
