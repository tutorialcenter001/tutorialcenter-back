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
        Schema::create('exam_attempts', function (Blueprint $table) {
            $table->id();

            $table->foreignId('student_id')->constrained()->cascadeOnDelete();

            $table->foreignId('exam_year_id')->constrained()->cascadeOnDelete();

            $table->integer('score')->default(0);

            $table->integer('total_questions');

            $table->integer('correct_answers')->default(0);

            $table->integer('wrong_answers')->default(0);

            $table->integer('unanswered')->default(0);

            $table->decimal('percentage', 5, 2)->default(0);

            $table->timestamp('started_at')->nullable();

            $table->timestamp('submitted_at')->nullable();

            $table->enum('status', [
                'in_progress',
                'completed',
                'abandoned',
            ])->default('in_progress');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exam_attempts');
    }
};
