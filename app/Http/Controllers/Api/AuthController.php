<?php

namespace App\Http\Controllers\Api;

use App\Actions\Auth\LoginUserAction;
use App\Actions\Auth\LogoutUserAction;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Resources\UserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends BaseApiController
{
    public function login(LoginRequest $request, LoginUserAction $loginUserAction): JsonResponse
    {
        $result = $loginUserAction->execute($request->validated());

        if (!$result) {
            return $this->unauthorizedResponse('Invalid credentials');
        }

        return $this->successResponse([
            'user' => new UserResource($result['user']),
            'token' => $result['token'],
        ], 'Login successful');
    }

    public function logout(Request $request, LogoutUserAction $logoutUserAction): JsonResponse
    {
        $user = Auth::user();
        $logoutUserAction->execute($user);

        return $this->successResponse(null, 'Logged out successfully');
    }
}
