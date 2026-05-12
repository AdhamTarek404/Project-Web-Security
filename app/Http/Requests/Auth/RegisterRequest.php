<?php

namespace App\Http\Requests\Auth;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

// FormRequest = validation in a dedicated class.
// Keeps the controller method tiny: just `$request->validated()`.
class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Anyone can register — no auth required to call this endpoint.
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],

            // Password::defaults() = the rules defined in AppServiceProvider
            // (typically: min 8 chars). 'confirmed' requires a matching
            // `password_confirmation` field on the request.
            'password' => ['required', 'confirmed', Password::defaults()],

            'phone' => ['nullable', 'string', 'max:30'],

            // We only allow public self-registration for customers and riders.
            // Restaurant owners and admins are created by an admin, never via the public API.
            'role' => [
                'required',
                Rule::in([User::ROLE_CUSTOMER, User::ROLE_RIDER]),
            ],
        ];
    }
}
