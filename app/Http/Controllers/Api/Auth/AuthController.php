<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Create a customer account and return an API token.
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $user = User::query()->create([
            'name' => $request->string('name')->toString(),
            'email' => $request->string('email')->toString(),
            'phone' => $request->input('phone'),
            'password' => $request->string('password')->toString(),
        ]);

        $user->assignRole('customer');

        event(new Registered($user));

        return $this->tokenResponse($user->refresh(), $request, 201);
    }

    /**
     * Exchange an email and password for an API token.
     */
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
            'device_name' => ['nullable', 'string', 'max:255'],
        ]);

        $user = User::query()->where('email', $request->string('email')->toString())->first();

        if (! $user || ! Hash::check($request->string('password')->toString(), $user->password)) {
            throw ValidationException::withMessages(['email' => __('auth.failed')]);
        }

        if (! $user->is_active) {
            throw ValidationException::withMessages(['email' => __('This account has been deactivated.')]);
        }

        return $this->tokenResponse($user, $request);
    }

    /**
     * Revoke the token used for this request.
     */
    public function logout(Request $request): Response
    {
        $request->user()->currentAccessToken()->delete();

        return response()->noContent();
    }

    /**
     * The signed-in user.
     */
    public function user(Request $request): UserResource
    {
        return UserResource::make($request->user());
    }

    private function tokenResponse(User $user, Request $request, int $status = 200): JsonResponse
    {
        $token = $user->createToken($request->string('device_name', 'api')->toString());

        return response()->json([
            'token' => $token->plainTextToken,
            'user' => UserResource::make($user),
        ], $status);
    }
}
