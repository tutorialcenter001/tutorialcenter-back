<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_advisors', function (Blueprint $table) {

            $table->id();

            $table->foreignId('student_id')
                ->constrained('students')
                ->cascadeOnDelete();

            $table->foreignId('staff_id')
                ->constrained('staffs')
                ->cascadeOnDelete();

            $table->string('role')->default('advisor');

            $table->timestamp('assigned_at')->nullable();

            $table->softDeletes()->comment('Use Laravel softDelete module');

            $table->timestamps();

            $table->unique(['student_id', 'staff_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_advisors');
    }
};