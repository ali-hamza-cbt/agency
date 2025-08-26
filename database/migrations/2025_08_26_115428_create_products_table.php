<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agency_id')->constrained('users')->cascadeOnDelete();

            // Brand & Category
            $table->foreignId('brand_id')->constrained('brands')->cascadeOnDelete();
            $table->foreignId('category_id')->constrained('categories')->cascadeOnDelete();

            // Product details
            $table->string('name');
            $table->string('sku')->unique();
            $table->enum('container_type', [
                'PET Plastic Bottle',
                'Returnable Glass Bottle',
                'Aluminum Beverage Can',
                'Tetra Pak Carton',
                'Plastic Beverage Pouch',
                'HDPE Water Gallon',
                'Glass Jar',
            ])->comment('Type of container');

            // Product size in milliliters (e.g., 500 for 500ml, 1500 for 1.5L)
            $table->integer('size_ml')->comment('Size in milliliters');
            $table->text('description')->nullable();
            $table->json('images')->nullable();
            $table->integer('reorder_level')->default(0)->comment('Minimum stock alert level');
            $table->string('barcode')->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
