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
        Schema::create('product_batches', function (Blueprint $table) {
            $table->id();
            // Agency
            $table->foreignId('agency_id')->constrained('users')->cascadeOnDelete();
            // Link to product
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();

            // Batch & expiry
            $table->string('batch_no')->comment('Unique batch number');
            $table->date('expiry_date');
            // Pack info
            $table->string('pack_type')->comment('Single, 6-Pack, 12-Pack, Crate');
            $table->integer('pack_size')->default(1)->comment('Units per pack');
            $table->integer('pack_qty')->default(0)->comment('Number of packs in stock');
            $table->integer('single_qty')->default(0)->comment('Total single units available');
            $table->integer('reserved_qty')->default(0)->comment('Reserved for orders');
            $table->integer('damaged_qty')->default(0)->comment('Damaged / unsellable units');
            // Prices
            $table->decimal('cost_price', 10, 2)->comment('Cost price per unit');
            $table->decimal('price_per_unit', 10, 2)->comment('Selling price per unit');
            $table->decimal('mrp_per_unit', 10, 2)->nullable()->comment('Optional MRP per unit');
            // Location
            $table->string('warehouse')->nullable()->comment('Storage location');
            $table->timestamps();
            $table->softDeletes();

            // Indexes for faster queries
            $table->index(['product_id', 'batch_no']);
            $table->index('expiry_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_batches');
    }
};
