<?php

namespace App\Http\Controllers\Api\Auth;

use App\Concerns\PasswordValidationRules;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ProfileUpdateRequest;
use App\Http\Resources\UserResource;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ProfileController extends Controller
{
    use PasswordValidationRules;

    /**
     * Update the signed-in user's name, email and phone.
     */
    public function update(ProfileUpdateRequest $request): UserResource
    {
        $user = $request->user();
        $user->fill($request->validated());

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        if ($user->wasChanged('email')) {
            $user->sendEmailVerificationNotification();
        }

        return UserResource::make($user);
    }

    /**
     * Change the signed-in user's password.
     */
    public function updatePassword(Request $request): Response
    {
        $validated = $request->validate([
            'current_password' => $this->currentPasswordRules(),
            'password' => $this->passwordRules(),
        ]);

        $request->user()->update(['password' => $validated['password']]);

        return response()->noContent();
    }

    /**
     * Delete the signed-in user's account. Their past orders are kept.
     */
    public function destroy(Request $request): Response
    {
        $request->validate([
            'password' => $this->currentPasswordRules(),
        ]);

        $user = $request->user();
        $user->tokens()->delete();
        $user->delete();

        return response()->noContent();
    }
}
