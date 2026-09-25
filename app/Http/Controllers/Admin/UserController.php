<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    /**
     * List accounts. Filters: search (name or email), role.
     */
    public function index(Request $request): Response
    {
        $filters = array_filter($request->only(['search', 'role']));

        return Inertia::render('admin/users/index', [
            'users' => UserResource::collection(
                User::query()->filter($filters)->with('roles')->withCount('orders')->latest()->paginate(20)->withQueryString(),
            ),
            'filters' => $filters,
        ]);
    }

    /**
     * Change a user's role or deactivate their account.
     */
    public function update(UpdateUserRequest $request, User $user, UserService $users): RedirectResponse
    {
        $validated = $request->validated();

        $users->updateAccess($user, $request->user(), $validated);

        Inertia::flash('success', match (true) {
            isset($validated['role']) => __('Role updated.'),
            $user->is_active => __('Account reactivated.'),
            default => __('Account deactivated.'),
        });

        return back();
    }
}
