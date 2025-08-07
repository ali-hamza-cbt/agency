<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpFoundation\Response;

class ValidateSanctumExpiry
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken() ?? $request->cookie('access_token');
        Log::info('Token - ' . $token);

        if (!$token) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }
        $accessToken = PersonalAccessToken::findToken($token);

        Log::info('accessToken - ' . ($accessToken ? $accessToken->id : 'null'));

        if (!$accessToken || $accessToken->expires_at->isPast()) {
            return response()->json(['message' => 'Token expired'], 401);
        }

        return $next($request);
    }
}
