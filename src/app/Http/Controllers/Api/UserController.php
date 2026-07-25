<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AssignRoleRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\DB;
use OpenApi\Attributes as OA;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    #[OA\Get(
        path: '/api/users',
        operationId: 'getUsersList',
        description: 'Returns list of users registered in system. Requires Admin role.',
        summary: 'List all users (Admin only)',
        security: [['bearerAuth' => []]],
        tags: ['User Management'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Successful operation',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(type: 'object')),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden - Admin role required'),
        ]
    )]
    public function index()
    {
        return UserResource::collection(User::all());
    }

    #[OA\Patch(
        path: '/api/users/{user}/role',
        operationId: 'assignUserRole',
        description: "Updates the specified user's role. Requires Admin role.",
        summary: 'Assign role to user (Admin only)',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['role'],
                properties: [
                    new OA\Property(property: 'role', type: 'string', example: 'Staff'),
                ]
            )
        ),
        tags: ['User Management'],
        parameters: [
            new OA\Parameter(
                name: 'user',
                description: 'User ID',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Role assigned successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'object'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden - Admin role required'),
            new OA\Response(response: 422, description: 'Validation Error'),
        ]
    )]
    public function assignRole(AssignRoleRequest $request, User $user)
    {
        DB::beginTransaction();
        try {
            $user->syncRoles([$request->role]);

            DB::commit();

            return new UserResource($user);
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    #[OA\Get(
        path: '/api/roles',
        operationId: 'getRolesList',
        description: 'Returns list of available system roles.',
        summary: 'List all role names (Admin only)',
        security: [['bearerAuth' => []]],
        tags: ['User Management'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Successful operation',
                content: new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(type: 'string', example: 'Staff')
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden - Admin role required'),
        ]
    )]
    public function roles()
    {
        return response()->json(Role::pluck('name'));
    }
}