<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class UserController extends Controller
{
    /**
     * List users. Filters: search (name or email), role.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $users = User::query()
            ->filter($request->only(['search', 'role']))
            ->with('roles')
            ->withCount('orders')
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return UserResource::collection($users);
    }

    public function show(User $user): UserResource
    {
        return UserResource::make($user->loadCount('orders'));
    }

    /**
     * Change a user's role or deactivate their account.
     */
    public function update(UpdateUserRequest $request, User $user, UserService $users): UserResource
    {
        $users->updateAccess($user, $request->user(), $request->validated());

        return UserResource::make($user->loadCount('orders'));
    }
}
