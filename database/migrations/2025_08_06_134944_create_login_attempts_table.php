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
        Schema::create('login_attempts', function (Blueprint $table) {
            $table->id();
            $table->string('browser_fingerprint')->index(); // Device identifier
            $table->string('ip_address', 45)->nullable();
            $table->unsignedBigInteger('user_id')->nullable(); // If any valid user was matched
            $table->json('attempted_emails')->nullable(); // Store all attempted emails
            $table->integer('failed_attempts')->default(0);
            $table->integer('lock_count')->default(0); // Progressive lock counter
            $table->timestamp('lock_until')->nullable();
            $table->timestamps();
        });

        Schema::create('ip_attempts', function (Blueprint $table) {
            $table->id();
            $table->string('ip_address', 45)->unique();
            $table->integer('failed_attempts')->default(0);
            $table->timestamp('lock_until')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('login_attempts');
        Schema::dropIfExists('ip_attempts');
    }
};
