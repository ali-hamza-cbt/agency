<?php

namespace App\Helpers;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\Crypt;

class TokenHelper
{
    public static function generateAccessToken($user): string
    {
        return $user->createToken('access', ['*'], now()->addMinutes(15))->plainTextToken;
    }
    public static function generateRefreshToken(): string
    {
        return Str::random(64);
    }
    public static function encryptToken($token): string
    {
        return Crypt::encrypt($token);
    }
    public static function decryptToken($token): string
    {
        return Crypt::decrypt($token);
    }

}
