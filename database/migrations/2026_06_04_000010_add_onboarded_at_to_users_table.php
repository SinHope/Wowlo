<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Onboarding tour state.
     *
     * NULL  = the user has never finished (or skipped) the welcome tour, so it
     *         auto-shows the next time they reach the dashboard.
     * set   = timestamp when they finished/skipped — never auto-shows again.
     *
     * Server-side (not device-local) on purpose: the tour shows once per ACCOUNT
     * across every device, and survives a reinstall or domain move. Additive +
     * nullable, so every existing user is simply "not onboarded yet" — safe.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('onboarded_at')->nullable()->after('remember_token');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('onboarded_at');
        });
    }
};
