<?php

namespace App\Actions\Auth;

use App\Models\User;

class LogoutUserAction
{
    public function execute(User $user): bool
    {
        $result = $user->tokens()->delete();

        return $result;
    }
}
