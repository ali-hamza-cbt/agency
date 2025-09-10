<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SalesmanProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'employee_code',
        'phone',
        'cnic',
        'vehicle_number',
        'vehicle_type',
        'fcm_token',
        'latitude',
        'longitude',
    ];

    // Hide user_id in API output
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

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($salesman) {
            if (!$salesman->employee_code) {
                $agencyId = $salesman->user->agency_id ?? 0; // linked agency
                $prefix   = "SAL-" . str_pad($agencyId, 2, '0', STR_PAD_LEFT); // e.g. SAL-12

                // find last code starting with this prefix
                $last = self::where('employee_code', 'like', $prefix . '%')->orderByDesc('id')->first();

                $lastNumber = ($last && preg_match('/(\d+)$/', $last->employee_code, $matches)) ? intval($matches[1]) : 0;

                $nextNumber = $lastNumber + 1;

                // format with leading zeros (change width as needed)
                $salesman->employee_code = $prefix . '-' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
            }
        });
    }

    /**
     * Relationship: A SalesmanProfile belongs to a User
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
