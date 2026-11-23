<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * When a call was set to Open, so the applicant's "time left to send a concept
 * note" bar can run from that day to the deadline. Calls already open (or since
 * closed) take the time they were last changed.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('seed_calls', function (Blueprint $table) {
            $table->timestamp('opened_at')->nullable()->after('status');
        });

        DB::table('seed_calls')->where('status', '!=', 'draft')->whereNull('opened_at')->update(['opened_at' => DB::raw('updated_at')]);
    }

    public function down(): void
    {
        Schema::table('seed_calls', function (Blueprint $table) {
            $table->dropColumn('opened_at');
        });
    }
};
