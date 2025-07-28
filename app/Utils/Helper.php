<?php

namespace App\Utils;

use Illuminate\Support\Str;

class Helper
{
    public static function generateToken()
    {
        return Str::random(64);
    }
}