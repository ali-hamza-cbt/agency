<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $browser_fingerprint
 * @property string|null $ip_address
 * @property int|null $user_id
 * @property array<array-key, mixed>|null $attempted_emails
 * @property int $failed_attempts
 * @property int $lock_count
 * @property \Illuminate\Support\Carbon|null $lock_until
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LoginAttempt newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LoginAttempt newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LoginAttempt query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LoginAttempt whereAttemptedEmails($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LoginAttempt whereBrowserFingerprint($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LoginAttempt whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LoginAttempt whereFailedAttempts($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LoginAttempt whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LoginAttempt whereIpAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LoginAttempt whereLockCount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LoginAttempt whereLockUntil($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LoginAttempt whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LoginAttempt whereUserId($value)
 * @mixin \Eloquent
 */
class LoginAttempt extends Model
{
    protected $fillable = [
        'browser_fingerprint',
        'ip_address',
        'user_id',
        'attempted_emails',
        'failed_attempts',
        'lock_count',
        'lock_until'
    ];

    protected $casts = [
        'attempted_emails' => 'array',
        'lock_until' => 'datetime',
    ];
    protected $dates = ['lock_until'];
}
