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
        Schema::disableForeignKeyConstraints();

        Schema::create('staffs', function (Blueprint $table) {
            $table->id();
            $table->string('staff_id')->unique()->comment('FORMAT(TCYYMM0000) The format is designed like TY26012453');
            $table->string('firstname');
            $table->string('middlename')->nullable();
            $table->string('surname');
            $table->string('email')->unique();
            $table->string('tel')->unique();
            $table->string('password');
            $table->enum('gender', ["male","female","others"]);
            $table->string('profile_picture');
            $table->date('date_of_birth')->nullable();
            $table->timestamp('email_verified_at')->nullable()->comment('When a new staff verifies their email that time is stored here');
            $table->timestamp('tel_verified_at')->nullable()->comment('When a new staff verifies their telephone number that time is stored here');
            $table->string('location')->comment('This should be the persons country and state');
            $table->string('address');
            $table->string('role')->comment('This should be either admin, tutor, advisor, or moderator');
            $table->bigInteger('inducted_by')->nullable();
            $table->softDeletes()->comment('Use Laravel softDelete module');
            $table->timestamps();
        });

        Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('staffs');
    }
};
