<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AssignRoleRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function index()
    {
        return UserResource::collection(User::all());
    }

    public function assignRole(AssignRoleRequest $request, User $user)
    {
        $user->syncRoles([$request->role]);

        return new UserResource($user);
    }

    public function roles()
    {
        return response()->json(Role::pluck('name'));
    }
}