<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Which seed grant reminders have gone to whom, so the daily run sends each
 * one once (a key such as "review-due:12" for round 12). Reminders are "due
 * within N days and not yet sent", so a missed morning is caught up the next.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('seed_reminders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('key', 64);
            $table->timestamp('sent_at');
            $table->unique(['user_id', 'key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seed_reminders');
    }
};
