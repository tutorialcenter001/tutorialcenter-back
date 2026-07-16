<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('feedbacks', function (Blueprint $table) {

            $table->id();

            /*
            |--------------------------------------------------------------------------
            | Who gave the feedback
            |--------------------------------------------------------------------------
            */
            $table->morphs('feedbacker');

            /*
            |--------------------------------------------------------------------------
            | What is being reviewed
            |--------------------------------------------------------------------------
            */
            $table->morphs('feedbackable');

            /*
            |--------------------------------------------------------------------------
            | Feedback
            |--------------------------------------------------------------------------
            */
            $table->unsignedTinyInteger('rating');

            $table->string('title')->nullable();

            $table->longText('comment')->nullable();

            $table->json('ratings')->nullable();

            $table->boolean('would_recommend')
                ->default(true);

            $table->boolean('is_anonymous')
                ->default(false);

            $table->enum('status', [
                'published',
                'hidden',
            ])->default('published');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('feedbacks');
    }
};