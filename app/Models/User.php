<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Models\UserSession;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Notifications\TwoFactorCodeNotification;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class   User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'agency_id',
        'name',
        'email',
        'password',
        'profile_image',
        'role',
        'status',
        'email_verified_at',
        'last_login_at',
        'last_login_ip',
        'failed_login_attempts',
        'locked_until',
        'two_factor_enabled',
        'is_two_factor_verified',
        'two_factor_secret',
        'two_factor_code',
        'two_factor_expires_at',
        'two_factor_recovery_codes',
        'timezone',
        'language',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_code',
        'two_factor_recovery_codes',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts()
    {
        return [
            'email_verified_at'        => 'datetime',
            'last_login_at'            => 'datetime',
            'locked_until'             => 'datetime',
            'two_factor_expires_at'    => 'datetime',
            'two_factor_enabled'       => 'boolean',
            'is_two_factor_verified'   => 'boolean',
            'failed_login_attempts'    => 'integer',
            'password'                 => 'hashed',
        ];
    }

    /**
     * Update Password
     * @return void
     */
    protected static function booted()
    {
        static::updated(function ($user) {
            if ($user->isDirty('password')) {
                $user->tokens()->delete();
                UserSession::where('user_id', $user->id)->delete();
            }
        });
    }

    /**
     * Generate & hash recovery codes.
     */
    public static function generateRecoveryCodes($count = 8)
    {
        $codes = [];
        for ($i = 0; $i < $count; $i++) {

            $plain = strtoupper(Str::random(6));
            $codes[] = [
                'plain' => $plain, // Show this to the user once
                'encrypted' => Crypt::encryptString($plain), // Store encrypted
            ];
        }
        return $codes;
    }

    /**
     * Verify a recovery code.
     */
    public function verifyRecoveryCode($code): bool
    {
        $codes = json_decode($this->two_factor_recovery_codes, true) ?? [];

        foreach ($codes as $index => $encrypted) {
            try {
                $decrypted = Crypt::decryptString($encrypted);
                if (hash_equals($decrypted, strtoupper($code))) {
                    // Remove used code
                    unset($codes[$index]);
                    $this->two_factor_recovery_codes = json_encode(array_values($codes));
                    $this->save();
                    return true;
                }
            } catch (\Exception $e) {
                continue; // skip tampered data
            }
        }

        return false;
    }


    /**
     * Generate a temporary 2FA code (for email).
     */
    public function generateTwoFactorCode()
    {

        $plainCode = strtoupper(Str::random(6));

        $this->two_factor_code = Hash::make($plainCode);
        $this->two_factor_expires_at = now()->addMinutes(10);
        $this->save();

        // Send the plain code to the user
        $this->notify(new TwoFactorCodeNotification($plainCode));
    }

    /**
     * Reset 2FA code after verification.
     */
    public function resetTwoFactorCode()
    {
        $this->two_factor_code = null;
        $this->two_factor_expires_at = null;
        $this->is_two_factor_verified = true;
        $this->save();
    }

    /**
     * Check if 2FA code is valid.
     */
    public function verifyTwoFactorCode($code)
    {
        return $this->two_factor_code && $this->two_factor_expires_at && now()->lessThanOrEqualTo($this->two_factor_expires_at) && Hash::check($code, $this->two_factor_code);
    }

    /**
     * Helper: Check if account is locked.
     */
    public function isLocked()
    {
        return $this->locked_until && now()->lessThan($this->locked_until);
    }

    /**
     * Accessor for full profile image URL.
     */
    public function getProfileImageUrlAttribute()
    {
        return $this->profile_image ? asset("storage/{$this->profile_image}") : null;
    }

    /**
     * Relationships.
     */

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function deletedBy()
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }

    /**
     * The agency this user belongs to (e.g. an admin assigned to an agency).
     */
    public function agency()
    {
        return $this->belongsTo(User::class, 'agency_id')->where('role', 'agency');
    }

    /**
     * The admin users assigned to this agency.
     */
    public function assignedAdmins()
    {
        return $this->hasMany(User::class, 'agency_id')->where('role', 'admin');
    }

    /**
     * *Other Relations
     */
    public function brands()
    {
        return $this->hasMany(Brand::class, 'agency_id');
    }

    public function categories()
    {
        return $this->hasMany(Category::class, 'agency_id');
    }
    // An agency can have multiple products
    public function products()
    {
        return $this->hasMany(Product::class, 'agency_id');
    }

    // An agency can have multiple product batches (inventory)
    public function productBatches()
    {
        return $this->hasMany(ProductBatch::class, 'agency_id');
    }
}
