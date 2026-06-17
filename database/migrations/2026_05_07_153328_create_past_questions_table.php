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
        Schema::create('past_questions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('exam_year_id')
                ->constrained('exam_years')
                ->cascadeOnDelete();

            $table->foreignId('past_question_group_id')
                ->nullable()
                ->constrained('past_question_groups')
                ->nullOnDelete();

            $table->integer('question_number')->nullable();

            $table->longText('question');

            $table->string('question_type')->default('multiple_choice');
            // multiple_choice, true_false, short_answer, essay

            $table->integer('marks')->default(1);

            $table->longText('explanation')->nullable();

            $table->string('status')->default('active');

            $table->softDeletes();
            $table->timestamps();

            $table->unique([
                'exam_year_id',
                'question_number'
            ], 'past_question_number_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('past_questions');
    }
};
