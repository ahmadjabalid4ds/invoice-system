<?php

namespace App\Actions\Auth;

use Illuminate\Support\Facades\Auth;

class LoginUserAction
{
    public function execute(array $credentials): ?array
    {
        if (!Auth::attempt($credentials)) {
            return null;
        }

        $user = Auth::user();
        $user->tokens()->delete();
        $token = $user->createToken('auth-token')->plainTextToken;

        return [
            'user' => $user,
            'token' => $token,
        ];
    }
}
