<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('support_tickets', function (Blueprint $table) {

            $table->id();

            /*
            |--------------------------------------------------------------------------
            | Ticket Number
            |--------------------------------------------------------------------------
            */
            $table->string('ticket_number')
                ->unique();

            /*
            |--------------------------------------------------------------------------
            | Who created the ticket
            |--------------------------------------------------------------------------
            */

            $table->morphs('requester');

            /*
            |--------------------------------------------------------------------------
            | Assigned Staff
            |--------------------------------------------------------------------------
            */

            $table->foreignId('assigned_staff_id')
                ->nullable()
                ->constrained('staffs')
                ->nullOnDelete();

            /*
            |--------------------------------------------------------------------------
            | Ticket Information
            |--------------------------------------------------------------------------
            */

            // $table->string('category');
            $table->foreignId('support_category_id')
                ->constrained('support_categories')
                ->restrictOnDelete();

            $table->string('subject');

            $table->longText('description');

            $table->enum('priority', [
                'low',
                'medium',
                'high',
                'critical',
            ])->default('medium');

            $table->enum('status', [
                'open',
                'pending',
                'in_progress',
                'resolved',
                'closed',
            ])->default('open');

            /*
            |--------------------------------------------------------------------------
            | Dates
            |--------------------------------------------------------------------------
            */

            $table->timestamp('last_reply_at')
                ->nullable();

            $table->timestamp('resolved_at')
                ->nullable();

            $table->timestamp('closed_at')
                ->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('support_tickets');
    }
};
