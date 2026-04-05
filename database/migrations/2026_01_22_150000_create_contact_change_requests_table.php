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
        Schema::create('contact_change_requests', function (Blueprint $table) {
            $table->id();
            
            // Polymorphic relationship - can be Student, Guardian, Staff, etc.
            $table->morphs('requestable'); // creates requestable_type and requestable_id
            
            // Type of change: 'phone' or 'email'
            $table->enum('change_type', ['phone', 'email']);
            
            // New contact value
            $table->string('new_value');
            
            // Make new_value unique per change_type to avoid duplicates
            $table->unique(['change_type', 'new_value']);
            
            // OTP verification
            $table->string('otp_code');
            $table->timestamp('otp_expires_at');
            $table->boolean('is_verified')->default(false);
            $table->timestamp('verified_at')->nullable();
            
            // Timestamps
            $table->timestamps();
            
            // Indexes for faster queries
            // $table->index(['requestable_type', 'requestable_id']);
            // $table->index('change_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contact_change_requests');
    }
};
