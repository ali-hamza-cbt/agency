<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'agency_id',
        'brand_id',
        'category_id',
        'name',
        'sku',
        'container_type',
        'size_ml',
        'description',
        'images',
        'reorder_level',
        'barcode',
        'status',
    ];
    // Auto-generate SKU on product creation
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($product) {
            if (!$product->sku) {
                // Example: Brand code + Size + Container code + Random 3 digits
                $brandCode = strtoupper(substr($product->brand->name, 0, 3)); // e.g., COCA
                $sizeCode = $product->size_ml; // e.g., 500
                $containerCode = strtoupper(substr(str_replace(' ', '', $product->container_type), 0, 3)); // PET -> PET
                $authId = currentAccount() ? currentAccount()->id : 0;

                // Ensure uniqueness
                do {
                    $randomNumber = rand(100, 999);
                    $sku = $brandCode.$sizeCode.$containerCode.$randomNumber.'-'.$authId;
                } while (self::where('sku', $sku)->exists());

                $product->sku = $sku;
            }
        });
    }
    // Relationships
    public function agency()
    {
        return $this->belongsTo(User::class, 'agency_id');
    }

    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function batches()
    {
        return $this->hasMany(ProductBatch::class);
    }
}
