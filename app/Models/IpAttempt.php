<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $ip_address
 * @property int $failed_attempts
 * @property \Illuminate\Support\Carbon|null $lock_until
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|IpAttempt newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|IpAttempt newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|IpAttempt query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|IpAttempt whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|IpAttempt whereFailedAttempts($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|IpAttempt whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|IpAttempt whereIpAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|IpAttempt whereLockUntil($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|IpAttempt whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class IpAttempt extends Model
{
    protected $fillable = [
        'ip_address',
        'failed_attempts',
        'lock_until'
    ];

    protected $casts = [
        'lock_until' => 'datetime',
    ];
}
