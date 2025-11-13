<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class RetailerProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'shop_name',
        'shop_address',
        'contact_person',
        'phone',
        'credit_balance',
        'fcm_token',
        'latitude',
        'longitude',
    ];

    /**
     * Hide sensitive fields
     */
    protected $hidden = [
        'user_id',
    ];

    /**
     * Casts for proper data types
     */
    protected $casts = [
        'credit_balance' => 'decimal:2',
        'latitude'       => 'decimal:7',
        'longitude'      => 'decimal:7',
    ];

    /**
     * A retailer profile belongs to a user
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
