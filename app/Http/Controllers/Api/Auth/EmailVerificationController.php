<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class EmailVerificationController extends Controller
{
    /**
     * Send another verification email.
     */
    public function send(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            return response()->json(['message' => __('Your email address is already verified.')]);
        }

        $user->sendEmailVerificationNotification();

        return response()->json(['message' => __('A new verification link has been sent to your email address.')]);
    }

    /**
     * Handle the link from the verification email, then send the user back to the frontend.
     */
    public function verify(string $id, string $hash): RedirectResponse
    {
        $user = User::query()->whereKey($id)->firstOrFail();

        abort_unless(hash_equals(sha1($user->getEmailForVerification()), $hash), 403);

        if (! $user->hasVerifiedEmail() && $user->markEmailAsVerified()) {
            event(new Verified($user));
        }

        return redirect()->away(rtrim((string) config('app.frontend_url'), '/').'/?verified=1');
    }
}
