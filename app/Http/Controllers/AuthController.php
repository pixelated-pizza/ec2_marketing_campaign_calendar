<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginUserRequest;
use App\Services\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use App\Services\FirebaseService;

class AuthController extends Controller
{
    protected UserService $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    public function login(LoginUserRequest $request): JsonResponse
    {
        try {
            $user = $this->userService->login(
                $request->email,
                $request->password,
                $request->boolean('remember')
            );

            if (!$user) {
                return response()->json(['message' => 'Invalid credentials'], 401);
            }

            $user->load('role'); // ← add this
            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'message' => 'Login successful',
                'token'   => $token,
                'user'    => [
                    'name'      => $user->name,
                    'email'     => $user->email,
                    'role_name' => $user->role?->role_name,
                ],
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => $e->errors()['email'][0] ?? 'Invalid email'
            ], 422);
        }
    }

    public function firebaseLogin(Request $request): JsonResponse
    {
        $request->validate(['idToken' => 'required|string']);

        try {
            $user = $this->userService->loginWithFirebase($request->idToken);

            if (!$user) {
                return response()->json(['message' => 'Invalid Firebase token'], 401);
            }

            $user->load('role'); // ← add this
            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'message' => 'Login successful via Firebase',
                'token'   => $token,
                'user'    => [
                    'name'      => $user->name,
                    'email'     => $user->email,
                    'role_name' => $user->role?->role_name,
                ],
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => $e->errors()['email'][0] ?? 'Invalid email'
            ], 422);
        }
    }


    public function logout(UserService $userService)
    {
        $userService->logout();
        return response()->json(['message' => 'Logged out successfully']);
    }

    public function getUser(UserService $userService)
    {
        $user = $userService->current();

        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role_id' => $user->role_id,
            'role_name' => $user->role?->role_name,
        ]);
    }
}
