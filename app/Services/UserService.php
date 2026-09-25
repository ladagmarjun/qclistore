<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UserService
{
    /**
     * Change a user's role or active status on behalf of an admin.
     *
     * @param  array{role?: string, is_active?: bool}  $changes
     *
     * @throws ValidationException
     */
    public function updateAccess(User $user, User $admin, array $changes): User
    {
        if ($user->is($admin)) {
            throw ValidationException::withMessages(['user' => __('You can\'t change your own role or status.')]);
        }

        if (isset($changes['role'])) {
            $user->syncRoles($changes['role']);
        }

        if (isset($changes['is_active'])) {
            $user->forceFill(['is_active' => $changes['is_active']])->save();
        }

        // Sign a deactivated user out everywhere: API tokens and browser sessions.
        if (! $user->is_active) {
            $user->tokens()->delete();

            if (config('session.driver') === 'database') {
                DB::table(config('session.table', 'sessions'))->where('user_id', $user->id)->delete();
            }
        }

        return $user;
    }
}
