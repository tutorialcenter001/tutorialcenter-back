<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('support_ticket_messages', function (Blueprint $table) {

            $table->id();

            $table->foreignId('support_ticket_id')
                ->constrained('support_tickets')
                ->cascadeOnDelete();

            /*
            |--------------------------------------------------------------------------
            | Sender
            |--------------------------------------------------------------------------
            */

            $table->morphs('sender');

            /*
            |--------------------------------------------------------------------------
            | Message
            |--------------------------------------------------------------------------
            */

            $table->longText('message');

            /*
            |--------------------------------------------------------------------------
            | Staff internal notes
            |--------------------------------------------------------------------------
            */

            $table->boolean('is_internal')
                ->default(false);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'support_ticket_messages'
        );
    }
};