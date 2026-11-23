<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * The applicant role is "Staff" (the owner, 23 Nov 2026): the person who applies, leads
 * the study as PI and reviews when GMU asks is one NIMR staff member. Only the name shown
 * changes; the role's key stays "pi", so no account moves.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('roles')->where('name', 'pi')->update([
            'display_name' => 'Staff',
            'description'  => 'NIMR staff: applies to open calls as lead applicant, and reviews when GMU assigns them',
        ]);
    }

    public function down(): void
    {
        DB::table('roles')->where('name', 'pi')->update([
            'display_name' => 'Principal Investigator',
            'description'  => 'Lead researcher on grant proposals and awards',
        ]);
    }
};
