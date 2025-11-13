<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class DeliveryBoyProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'vehicle_number',
        'vehicle_type',
        'driving_license',
        'phone',
        'cnic',
        'fcm_token',
        'latitude',
        'longitude',
    ];

    /**
     * Hide internal keys from API responses
     */
    protected $hidden = [
        'user_id',
    ];

    /**
     * Casts for proper data types
     */
    protected $casts = [
        'latitude'  => 'decimal:7',
        'longitude' => 'decimal:7',
    ];

    /**
     * Relationships
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
