<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property-read \App\Models\User|null $agency
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Brand newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Brand newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Brand onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Brand query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Brand withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Brand withoutTrashed()
 * @mixin \Eloquent
 */
class Brand extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'agency_id',
        'name',
        'logo',
        'description',
        'status',
    ];

    public function agency()
    {
        return $this->belongsTo(User::class, 'agency_id');
    }
    public function categories()
    {
        return $this->hasMany(Category::class, 'brand_id');
    }
}
