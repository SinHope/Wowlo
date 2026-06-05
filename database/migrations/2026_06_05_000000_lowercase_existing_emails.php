<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * One-time data fix: lowercase every existing email.
     *
     * Accounts created before the lowercase normalisation (e.g. a student saved
     * as "Keean.leegt@gmail.com") couldn't log in with the lowercase form,
     * because Postgres string matching is case-sensitive. From now on emails are
     * stored lowercased (User model mutator) and every lookup is normalised; this
     * brings the existing rows in line.
     *
     * Idempotent — running LOWER() on an already-lowercase value is a no-op.
     */
    public function up(): void
    {
        DB::table('users')->update(['email' => DB::raw('LOWER(email)')]);
    }

    public function down(): void
    {
        // Original casing is not recoverable — nothing to revert.
    }
};
