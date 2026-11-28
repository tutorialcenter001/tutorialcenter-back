<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bootcamp_participants', function (Blueprint $table) {
            $table->id();
            $table->string('phone_normalized', 32)->unique();
            $table->string('phone_raw', 32);
            $table->string('full_name', 255);
            $table->string('pin_hash');
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();
        });

        Schema::create('bootcamp_cbts', function (Blueprint $table) {
            $table->id();
            $table->string('title', 255);
            $table->string('subject_track', 64)->index(); // jamb_english, olevel_english, jamb_maths, olevel_maths
            $table->date('week_start_date')->index(); // Monday date of target week (Africa/Lagos)
            $table->string('access_token', 64)->unique();
            $table->unsignedInteger('duration_minutes')->default(20);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->unsignedBigInteger('created_by_staff_id')->nullable();
            $table->timestamps();

            $table->index(['subject_track', 'week_start_date']);
        });

        Schema::create('bootcamp_cbt_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bootcamp_cbt_id')->constrained('bootcamp_cbts')->cascadeOnDelete();
            $table->text('passage_text')->nullable();
            $table->text('question_text');
            $table->string('image_url', 500)->nullable();
            $table->json('options'); // [{ key: "A", text: "..." }, ...]
            $table->string('correct_option', 10);
            $table->text('explanation')->nullable();
            $table->unsignedInteger('order_index')->default(0);
            $table->timestamps();

            $table->index(['bootcamp_cbt_id', 'order_index']);
        });

        Schema::create('bootcamp_cbt_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bootcamp_cbt_id')->constrained('bootcamp_cbts')->cascadeOnDelete();
            $table->foreignId('participant_id')->constrained('bootcamp_participants')->cascadeOnDelete();
            $table->json('answers')->nullable();
            $table->unsignedInteger('score')->default(0);
            $table->unsignedInteger('total_questions')->default(0);
            $table->unsignedInteger('time_taken_seconds')->default(0);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('submitted_at')->nullable()->index();
            $table->timestamps();

            $table->unique(['bootcamp_cbt_id', 'participant_id']);
            $table->index(['bootcamp_cbt_id', 'score', 'time_taken_seconds']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bootcamp_cbt_attempts');
        Schema::dropIfExists('bootcamp_cbt_questions');
        Schema::dropIfExists('bootcamp_cbts');
        Schema::dropIfExists('bootcamp_participants');
    }
};
