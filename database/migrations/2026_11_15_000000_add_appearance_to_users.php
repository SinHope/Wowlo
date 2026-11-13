<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Per-user appearance settings. Purely additive: `theme` defaults to
     * 'system' (follow the device) and `app_background` is NULL (no image),
     * so every existing user looks exactly as before until they change it.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('theme', 10)->default('system');
            // Relative path under public/app-background, e.g. "Soft/clouds.jpg".
            $table->string('app_background')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['theme', 'app_background']);
        });
    }
};
