<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Multi-tutor tenancy backbone.
     *
     * `tutor_id` is the owning tutor for a STUDENT row (1 student → 1 tutor).
     * It is NULL for tutor / super_admin rows. Every tenant-scoped query keys
     * off this column, directly (students) or via the student (fees, bills…).
     *
     * restrictOnDelete: a tutor who still has students cannot be deleted — the
     * app surfaces a friendly "remove their students first" error so a single
     * click can never wipe a whole roster.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('tutor_id')
                ->nullable()
                ->after('role')
                ->constrained('users')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('tutor_id');
        });
    }
};
