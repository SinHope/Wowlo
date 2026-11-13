<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Re-show the welcome tour once, so everyone sees the new "Attendance" card
     * (Slice 20). The tour auto-opens on the dashboard whenever
     * users.onboarded_at is NULL; it re-stamps itself when they finish or skip.
     *
     * Data-only: no schema change, nothing is dropped. It runs once per database
     * (Render runs `migrate --force` on boot), so it fires exactly when the
     * Attendance code ships — never before, never again on later deploys.
     *
     * Not repeatable by design: to nudge everyone again after a future tour
     * change, add a NEW migration like this one rather than editing this file.
     */
    public function up(): void
    {
        DB::table('users')->whereNotNull('onboarded_at')->update(['onboarded_at' => null]);
    }

    /**
     * Intentionally a no-op: the original per-user timestamps aren't kept, and a
     * stale "already onboarded" stamp has no value to restore. Rolling this back
     * leaves the flag NULL, which only means the tour shows once more.
     */
    public function down(): void
    {
        //
    }
};
