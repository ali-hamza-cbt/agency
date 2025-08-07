<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $agency_id
 * @property string $company_name
 * @property string|null $company_logo
 * @property string|null $address
 * @property string|null $city
 * @property string|null $state
 * @property string|null $country
 * @property string|null $phone
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User $agency
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AgencyDetail newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AgencyDetail newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AgencyDetail query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AgencyDetail whereAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AgencyDetail whereAgencyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AgencyDetail whereCity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AgencyDetail whereCompanyLogo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AgencyDetail whereCompanyName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AgencyDetail whereCountry($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AgencyDetail whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AgencyDetail whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AgencyDetail wherePhone($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AgencyDetail whereState($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AgencyDetail whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class AgencyDetail extends Model
{
    protected $fillable = [
        'agency_id',
        'company_name',
        'company_logo',
        'address',
        'city',
        'state',
        'country',
        'phone',
    ];

    /**
     * Get the user who owns this agency detail (agency user).
     */
    public function agency()
    {
        return $this->belongsTo(User::class, 'agency_id');
    }
}
