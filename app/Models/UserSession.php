<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $user_id
 * @property string|null $device_name
 * @property string|null $browser_name
 * @property string $ip_address
 * @property string|null $country
 * @property string $refresh_token
 * @property \Illuminate\Support\Carbon $expires_at
 * @property \Illuminate\Support\Carbon|null $last_used_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserSession newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserSession newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserSession query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserSession whereBrowserName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserSession whereCountry($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserSession whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserSession whereDeviceName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserSession whereExpiresAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserSession whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserSession whereIpAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserSession whereLastUsedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserSession whereRefreshToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserSession whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserSession whereUserId($value)
 * @mixin \Eloquent
 */
class UserSession extends Model
{
    protected $fillable = [
        'user_id',
        'device_name',
        'browser_name',
        'ip_address',
        'country',
        'refresh_token',
        'expires_at',
        'last_used_at'
    ];
    protected $casts = ['expires_at' => 'datetime', 'last_used_at' => 'datetime'];
}
