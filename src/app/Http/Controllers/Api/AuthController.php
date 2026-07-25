<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use OpenApi\Attributes as OA;

class AuthController extends Controller
{
    #[OA\Post(
        path: '/api/register',
        operationId: 'registerUser',
        description: 'Registers a user and returns user info with a bearer token.',
        summary: 'Register a new user',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name', 'email', 'password', 'password_confirmation'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', example: 'John Doe'),
                    new OA\Property(property: 'email', type: 'string', format: 'email', example: 'john@example.com'),
                    new OA\Property(property: 'password', type: 'string', format: 'password', example: 'password123'),
                    new OA\Property(property: 'password_confirmation', type: 'string', format: 'password', example: 'password123'),
                ]
            )
        ),
        tags: ['Authentication'],
        responses: [
            new OA\Response(
                response: 201,
                description: 'User registered successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'user', type: 'object'),
                        new OA\Property(property: 'token', type: 'string', example: '1|plainTextTokenString'),
                    ]
                )
            ),
            new OA\Response(response: 422, description: 'Validation Error'),
        ]
    )]
    public function register(RegisterRequest $request)
    {
        DB::beginTransaction();
        try {
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
            ]);

            $user->assignRole('User'); // default role

            $token = $user->createToken('auth_token')->plainTextToken;

            DB::commit();

            return response()->json([
                'user' => new UserResource($user),
                'token' => $token,
            ], 201);
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    #[OA\Post(
        path: '/api/login',
        operationId: 'loginUser',
        description: 'Logs in a user with valid credentials.',
        summary: 'Authenticate user and get bearer token',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['email', 'password'],
                properties: [
                    new OA\Property(property: 'email', type: 'string', format: 'email', example: 'john@example.com'),
                    new OA\Property(property: 'password', type: 'string', format: 'password', example: 'password123'),
                ]
            )
        ),
        tags: ['Authentication'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Successful login',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'user', type: 'object'),
                        new OA\Property(property: 'token', type: 'string', example: '2|plainTextTokenString'),
                    ]
                )
            ),
            new OA\Response(response: 422, description: 'Invalid Credentials / Validation Error'),
        ]
    )]
    public function login(LoginRequest $request)
    {
        DB::beginTransaction();
        try {
            $user = User::where('email', $request->email)->first();

            if (! $user || ! Hash::check($request->password, $user->password)) {
                throw ValidationException::withMessages([
                    'email' => ['The provided credentials are incorrect.'],
                ]);
            }

            $token = $user->createToken('auth_token')->plainTextToken;

            DB::commit();

            return response()->json([
                'user' => new UserResource($user),
                'token' => $token,
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    #[OA\Post(
        path: '/api/logout',
        operationId: 'logoutUser',
        description: 'Revokes the current access token.',
        summary: 'Logout current user session',
        security: [['bearerAuth' => []]],
        tags: ['Authentication'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Logged out successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Logged out'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ]
    )]
    public function logout(Request $request)
    {
        DB::beginTransaction();
        try {
            $request->user()->currentAccessToken()->delete();

            DB::commit();

            return response()->json(['message' => 'Logged out']);
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    #[OA\Get(
        path: '/api/me',
        operationId: 'getAuthenticatedUser',
        description: 'Returns profile details of currently authenticated user.',
        summary: 'Get authenticated user details',
        security: [['bearerAuth' => []]],
        tags: ['Authentication'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Successful operation',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'object'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ]
    )]
    public function me(Request $request)
    {
        return new UserResource($request->user());
    }
}