<?php

namespace App\Http\Controllers\Api\Web;

use App\Models\User;
use App\Models\UserSession;
use App\Helpers\ApiResponse;
use App\Helpers\TokenHelper;
use App\Models\LoginAttempt;
use Illuminate\Http\Request;
use App\Events\UserRegistered;
use App\Services\Auth\LoginHelper;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $data = $request->only(['name', 'email', 'password']);

        $validator = Validator::make($data, [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
        ]);

        if ($validator->fails()) {
            return ApiResponse::validationError($validator->errors(), 'Please correct the highlighted errors.');
        }

        DB::beginTransaction();

        try {
            // Create user
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'role' => 'agency',
            ]);

            // Generate and save encrypted recovery codes
            $codes = User::generateRecoveryCodes();
            $user->two_factor_recovery_codes = json_encode(array_map(fn($c) => $c['encrypted'], $codes));
            $user->save();

            // Fire registration event (with plain codes to send to user)
            event(new UserRegistered($user, collect($codes)->pluck('plain')->toArray()));

            DB::commit();

            return ApiResponse::success([], 'Registration completed successfully.');
        } catch (\Throwable $e) {
            DB::rollBack();
            // Optionally log error for debugging
            Log::error('Registration failed', ['error' => $e->getMessage()]);
            return ApiResponse::error('Registration failed. Please try again.');
        }
    }


    public function login(Request $request, LoginHelper $loginHelper)
    {
        $data = $request->only(['email', 'password']);
        $validator = Validator::make($data, [
            'email' => 'required|email',
            'password' => 'required|string',
        ]);
        if ($validator->fails()) {
            return ApiResponse::validationError($validator->errors(), 'Please correct the highlighted errors.');
        }

        $result = $loginHelper->handle($request, $data);

        if (isset($result['error'])) {
            return ApiResponse::error($result['error'], $result['status']);
        }

        if (isset($result['two_factor'])) {
            return ApiResponse::success([
                'two_factor_required' => true,
                'message' => $result['message']
            ], $result['message']);
        }
        $isPostman = $result['postman'];
        $responseData = ['user' => $result['user']] + ($isPostman ? ['token' => $result['accessToken']] : []);
        $response = ApiResponse::success($responseData, 'Login successful.');

        return $response->cookie('access_token', $result['accessToken'], 15, '/', env('SESSION_DOMAIN'), true, true, false, 'Strict')->cookie('refresh_token', $result['refreshToken'], 60 * 24 * 7, '/', env('SESSION_DOMAIN'), true, true, false, 'Strict');
    }

    public function refresh(Request $request)
    {
        $refreshToken = $request->cookie('refresh_token');

        // Validate refresh token
        if (!$refreshToken) {
            return ApiResponse::error('Refresh token is missing.', 401);
        }

        // Find valid session with matching decrypted token
        $session = UserSession::where('expires_at', '>', now())
            ->get()
            ->first(fn($s) => TokenHelper::decryptToken($s->refresh_token) === $refreshToken);

        if (!$session) {
            return ApiResponse::error('Invalid or expired refresh token.', 401);
        }

        $user = User::find($session->user_id);
        if (!$user) {
            return ApiResponse::error('User not found for this session.', 404);
        }

        // Issue new tokens
        $accessToken = TokenHelper::generateAccessToken($user);
        $newRefreshToken = TokenHelper::generateRefreshToken();

        // Update session
        $session->update(['refresh_token' => TokenHelper::encryptToken($newRefreshToken), 'last_used_at' => now()]);

        // Send response with new tokens
        return ApiResponse::success([], 'Token refreshed successfully.')
            ->cookie(
                'access_token',
                $accessToken,
                15, // 15 mins
                '/',
                env('SESSION_DOMAIN'),
                true,
                true,
                false,
                'Strict'
            )->cookie(
                'refresh_token',
                $newRefreshToken,
                60 * 24 * 7, // 7 days
                '/',
                env('SESSION_DOMAIN'),
                true,
                true,
                false,
                'Strict'
            );
    }


    public function logout(Request $request)
    {
        $refreshToken = $request->cookie('refresh_token');

        if ($refreshToken) {
            $encrypted = TokenHelper::encryptToken($refreshToken);
            UserSession::where('refresh_token', $encrypted)->delete();
        }

        // Delete only the current Sanctum token
        if ($request->user() && $request->user()->currentAccessToken()) {
            $request->user()->currentAccessToken()->delete();
        }

        // Clear cookies
        return ApiResponse::success([], 'Logged out successfully.')
            ->cookie('access_token', '', -1, '/', env('SESSION_DOMAIN'), true, true, false, 'Strict')
            ->cookie('refresh_token', '', -1, '/', env('SESSION_DOMAIN'), true, true, false, 'Strict');
    }
    public function logoutAllDevices(Request $request)
    {
        $user = Auth::user();

        if (!$user) {
            return ApiResponse::error('User not authenticated.', 401);
        }

        // Delete all user sessions
        UserSession::where('user_id', $user->id)->delete();

        // Delete all Sanctum tokens (logs out from all devices)
        $user->tokens()->delete();

        // Clear cookies
        return ApiResponse::success([], 'Logged out from all devices successfully.')
            ->cookie('access_token', '', -1, '/', env('SESSION_DOMAIN'), true, true, false, 'Strict')
            ->cookie('refresh_token', '', -1, '/', env('SESSION_DOMAIN'), true, true, false, 'Strict');
    }

    /**
     *Browser FingerPrint
     * @param \Illuminate\Http\Request $request
     * @return string
     */
    protected function getBrowserFingerprint(Request $request): string
    {
        return hash(
            'sha256',
            $request->ip() . '|' .
                $request->header('User-Agent') . '|' .
                $request->header('X-Device-Name', 'Web App')
        );
    }
    protected function calculateLockTime(int $lockCount): int
    {
        return match ($lockCount) {
            0 => 1,   // 1 min
            1 => 5,   // 5 mins
            2 => 30,  // 30 mins
            default => 60, // 1 hour
        };
    }
    
}
