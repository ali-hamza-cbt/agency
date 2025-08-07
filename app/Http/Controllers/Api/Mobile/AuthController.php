<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Models\User;
use App\Models\UserSession;
use App\Helpers\TokenHelper;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;


// app/Http/Controllers/Api/Mobile/AuthController.php (Flutter Mobile)
// (Same as Web, but set device_name as Mobile App for tracking.)


class AuthController extends Controller
{
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);

        User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        return response()->json([
            'message' => 'Registration successful',
        ], 201);
    }
    public function login(Request $request)
    {
        $request->validate(['email' => 'required|email', 'password' => 'required']);
        if (!Auth::attempt($request->only('email', 'password'))) {
            throw ValidationException::withMessages(['email' => ['Invalid credentials']]);
        }
        $user = Auth::user();

        // Limit sessions (max 3 active)
        if (UserSession::where('user_id', $user->id)->count() >= 3) {
            UserSession::where('user_id', $user->id)->oldest()->first()->delete();
        }

        $accessToken = TokenHelper::generateAccessToken($user);
        $refreshToken = TokenHelper::generateRefreshToken();

        UserSession::create([
            'user_id' => $user->id,
            'device_name' => $request->header('X-Device-Name', 'Web App'),
            'browser_name' => $request->header('User-Agent'),
            'ip_address' => $request->ip(),
            'country' => geoip($request->ip())->country ?? null,
            'refresh_token' => TokenHelper::encryptToken($refreshToken),
            'expires_at' => now()->addDays(7),
            'last_used_at' => now(),
        ]);

        return response()->json([
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken,
            'expires_in' => 900
        ]);
    }
}
