<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductBatch extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'agency_id',
        'product_id',
        'batch_no',
        'expiry_date',
        'pack_type',
        'pack_size',
        'pack_qty',
        'single_qty',
        'reserved_qty',
        'damaged_qty',
        'cost_price',
        'price_per_unit',
        'mrp_per_unit',
        'warehouse',
    ];
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($batch) {
            if (!$batch->batch_no) {
                $productCode = strtoupper(substr($batch->product->name, 0, 3));
                $dateCode = now()->format('ymd');
                $authId = currentAccount() ? currentAccount()->id : 0;

                do {
                    $randomNumber = rand(100, 999);
                    $batchNo = $productCode . '-' . $dateCode . '-' . $randomNumber . '-' . $authId;
                } while (self::where('batch_no', $batchNo)->exists());

                $batch->batch_no = $batchNo;
            }
        });
    }

    // Relationships
    public function agency()
    {
        return $this->belongsTo(User::class, 'agency_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
