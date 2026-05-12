<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],

            // The mobile app sends its device name (e.g. "iPhone 15 of Ahmed").
            // Sanctum stores it on the token so we can show "this device
            // logged in at..." or revoke a single device.
            'device_name' => ['required', 'string', 'max:100'],
        ];
    }
}
