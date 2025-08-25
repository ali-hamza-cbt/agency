<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Category extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'agency_id',
        'brand_id',
        'name',
        'logo',
        'description',
        'status',
    ];
    public function agency()
    {
        return $this->belongsTo(User::class, 'agency_id');
    }

    public function brand()
    {
        return $this->belongsTo(Brand::class, 'brand_id');
    }
}
