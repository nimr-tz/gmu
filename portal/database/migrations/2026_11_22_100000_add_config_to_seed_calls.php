<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Each call's switched-on features and their settings (see App\Support\CallForm).
 * Empty means "the seed grant defaults", so existing calls keep working as they were.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('seed_calls', function (Blueprint $table) {
            $table->json('config')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('seed_calls', function (Blueprint $table) {
            $table->dropColumn('config');
        });
    }
};
