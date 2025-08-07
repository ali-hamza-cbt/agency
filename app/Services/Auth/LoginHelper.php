<?php

namespace App\Services\Auth;

use App\Models\User;
use App\Models\IpAttempt;
use App\Models\UserSession;
use App\Helpers\TokenHelper;
use App\Models\LoginAttempt;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class LoginHelper
{
    // Generate fingerprint for device (IP + User-Agent + custom device header)
    protected function getBrowserFingerprint(Request $request): string
    {
        return hash('sha256', $request->ip() . '|' . $request->header('User-Agent') . '|' . $request->header('X-Device-Name', 'Web App'));
    }

    // Progressive lock time calculation
    protected function calculateLockTime(int $lockCount): int
    {
        return match ($lockCount) {
            0 => 1,     // 1 minute
            1 => 5,     // 5 minutes
            2 => 30,    // 30 minutes
            default => 60,  // 1 hour
        };
    }

    public function handle(Request $request, array $credentials)
    {
        $fingerprint = $this->getBrowserFingerprint($request);
        $ip = $request->ip();
        $email = $credentials['email'];

        // Find user first
        $user = User::where('email', $email)->first();

        // Validate role & status before even attempting login
        if ($user) {
            if (!in_array($user->role, ['admin', 'agency'])) {
                return ['error' => 'You are not authorized to access this system.', 'status' => 403];
            }

            if ($user->status !== 'active') {
                return ['error' => 'Your account is inactive. Please contact support.', 'status' => 403];
            }
        }

        // Get or create login attempt for device
        $attempt = LoginAttempt::firstOrCreate(
            ['browser_fingerprint' => $fingerprint],
            ['ip_address' => $ip, 'user_id' => optional($user)->id, 'attempted_emails' => []]
        );

        // Update attempted emails
        $emails = $attempt->attempted_emails ?? [];
        if (!in_array($credentials['email'], $emails)) {
            $emails[] = $credentials['email'];
            $attempt->attempted_emails = $emails;
            $attempt->save();
        }

        // Global IP tracking
        $ipAttempt = IpAttempt::firstOrCreate(['ip_address' => $ip]);

        // Check device lock
        if ($attempt->lock_until && now()->lessThan($attempt->lock_until)) {
            return ['error' => 'Too many failed attempts on this device. Try again after ' . $attempt->lock_until->diffForHumans() . '.', 'status' => 423];
        }

        // Check IP lock
        if ($ipAttempt->lock_until && now()->lessThan($ipAttempt->lock_until)) {
            return ['error' => 'Too many failed attempts from this IP. Try again after ' . $ipAttempt->lock_until->diffForHumans() . '.', 'status' => 423];
        }

        // Validate credentials
        if (!$user || !Auth::attempt($credentials)) {
            $this->incrementFailedAttempts($attempt, $ipAttempt);
            return ['error' => 'Invalid email or password.', 'status' => 401];
        }

        // Reset counters on success
        $attempt->update(['failed_attempts' => 0, 'lock_until' => null, 'lock_count' => 0]);
        $ipAttempt->update(['failed_attempts' => 0, 'lock_until' => null]);

        return $this->generateSession($request, $user);
    }

    // Increment failed attempts and apply locks
    protected function incrementFailedAttempts(LoginAttempt $attempt, IpAttempt $ipAttempt)
    {
        // Device lock
        $attempt->increment('failed_attempts');
        if ($attempt->failed_attempts >= 5) {
            $lockCount = $attempt->lock_count + 1;
            $minutes = $this->calculateLockTime($lockCount);
            $attempt->update([
                'lock_until' => now()->addMinutes($minutes),
                'failed_attempts' => 0,
                'lock_count' => $lockCount
            ]);
        }

        // IP lock
        $ipAttempt->increment('failed_attempts');
        if ($ipAttempt->failed_attempts >= 20) {
            $ipAttempt->update([
                'lock_until' => now()->addMinutes(30),
                'failed_attempts' => 0
            ]);
        }
    }

    // Create session & tokens
    protected function generateSession(Request $request, User $user)
    {
        DB::beginTransaction();
        try {
            $user->update([
                'last_login_at' => now(),
                'last_login_ip' => $request->ip(),
                'is_two_factor_verified' => false,
            ]);

            // Handle 2FA
            if ($user->two_factor_enabled) {
                $user->generateTwoFactorCode();
                DB::commit();
                return ['two_factor' => true, 'message' => '2FA verification code sent to your email.'];
            }

            $accessToken = TokenHelper::generateAccessToken($user);
            $refreshToken = TokenHelper::generateRefreshToken();

            $geoData = geoip($request->ip());
            $deviceName = $request->header('X-Device-Name', 'Web App');
            $browserName = $request->header('User-Agent');

            UserSession::create([
                'user_id'       => $user->id,
                'device_name'   => $deviceName,
                'browser_name'  => $browserName,
                'ip_address'    => app()->isLocal() ? $request->ip() : ($geoData->ip ?? $request->ip()),
                'country'       => $geoData->country ?? null,
                'refresh_token' => TokenHelper::encryptToken($refreshToken),
                'expires_at'    => now()->addDays(7),
                'last_used_at'  => now(),
            ]);

            DB::commit();
            return [
                'user' => $user,
                'accessToken' => $accessToken,
                'refreshToken' => $refreshToken,
                'postman' => stripos(trim($browserName), 'postman') !== false ? 'true' : 'false',
            ];
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Login session error', ['error' => $e->getMessage()]);
            return ['error' => 'Login failed. Please try again later.', 'status' => 500];
        }
    }
}
