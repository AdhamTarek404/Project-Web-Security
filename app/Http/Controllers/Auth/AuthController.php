<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

// Endpoints used by the customer mobile app AND the rider mobile app
// (description: "Sanctum API for customer mobile app and rider mobile app authentication").
class AuthController extends Controller
{
    /**
     * POST /api/register
     * Body: name, email, password, password_confirmation, role, device_name?, phone?
     *
     * Creates the user and immediately returns a token so the mobile app
     * doesn't have to log the user in as a second round-trip.
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $data = $request->validated();

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'], // auto-hashed by the User model's $casts
            'role' => $data['role'],
            'phone' => $data['phone'] ?? null,
        ]);

        // Sanctum: createToken returns a fresh token model + a `plainTextToken`
        // string. The plain text is shown ONCE — the DB only stores its hash.
        // The mobile app saves the plain text and sends it as
        //   Authorization: Bearer <token>
        // on every protected request.
        $token = $user->createToken($request->input('device_name', 'mobile'))->plainTextToken;

        return response()->json([
            'user' => $user,
            'token' => $token,
        ], 201);
    }

    /**
     * POST /api/login
     * Body: email, password, device_name
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $data = $request->validated();

        $user = User::where('email', $data['email'])->first();

        if (! $user || ! Hash::check($data['password'], $user->password)) {
            // ValidationException returns 422 with the same shape Laravel uses
            // everywhere else — keeps mobile error-handling consistent.
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        $token = $user->createToken($data['device_name'])->plainTextToken;

        return response()->json([
            'user' => $user,
            'token' => $token,
        ]);
    }

    /**
     * POST /api/logout — revoke the CURRENT token only.
     * Other devices of the same user keep working.
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out.']);
    }

    /**
     * GET /api/me — return the authenticated user.
     * Useful for "session restore" on mobile app boot.
     */
    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'user' => $request->user(),
        ]);
    }
}
