<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Rider;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

// Browser (session) auth: form-based login + logout.
// The API still uses Sanctum tokens via AuthController; this is the human-friendly
// version for browser demos.
class WebAuthController extends Controller
{
    public function showLogin(Request $request)
    {
        // When an API client (Sanctum token expired etc.) gets redirected here
        // because of the global "login" named route, give them JSON 401 instead
        // of the HTML form. Browser clients see the form.
        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        return view('auth.login');
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (! Auth::attempt($data, true)) {
            throw ValidationException::withMessages([
                'email' => 'Wrong email or password.',
            ]);
        }

        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home')->with('status', 'Logged out.');
    }

    public function showRegister()
    {
        return view('auth.register');
    }

    public function register(Request $request)
    {
        // Same rules as the API RegisterRequest — only customers and riders may
        // self-register; owners/admins are created by an admin.
        $data = $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'phone'    => ['nullable', 'string', 'max:30'],
            'role'     => ['required', Rule::in([User::ROLE_CUSTOMER, User::ROLE_RIDER])],
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'phone' => $data['phone'] ?? null,
            'role' => $data['role'],
        ]);

        // Riders need a Rider profile row so they can go on duty + receive dispatches.
        if ($user->isRider()) {
            Rider::create([
                'user_id' => $user->id,
                'vehicle_type' => 'bike',
                'is_on_duty' => false,
                'is_available' => false,
            ]);
        }

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('dashboard')->with('status', "Welcome, {$user->name}!");
    }
}
