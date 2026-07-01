<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Hangman Wheel Panda — wheels (Slice 18, see docs/hangman-wheel-panda.md).
 *
 * A wheel is a list of free-text slices spun before guessing. Two kinds:
 *  - 'standard' : authored ONLY by the super_admin; tutor_id is NULL; visible to
 *                 everyone (the shared house wheel).
 *  - 'custom'   : authored by a tutor (or the super_admin acting as a tutor);
 *                 tutor_id is the owning tutor (tenancy backbone); visible to that
 *                 tutor + their own students only.
 *
 * The game itself stores NO attempts — it's server-authoritative session play
 * (the secret word lives in the session, never the DB). Only wheels persist.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hangman_wheels', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            // 'standard' (global, admin-only) or 'custom' (tutor-owned).
            $table->string('type')->default('custom');
            // Who authored it (audit). Set server-side.
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            // The owning tutor for a custom wheel (tenancy); NULL for a standard
            // wheel. Set server-side, never from client input.
            $table->foreignId('tutor_id')->nullable()->constrained('users')->cascadeOnDelete();
            // The slice labels, e.g. ["+1 Free Guess", "Reveal a Letter", ...].
            $table->json('slices');
            $table->timestamps();

            // Listing scopes: standard wheels (global) + a tutor's own wheels.
            $table->index(['type', 'tutor_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hangman_wheels');
    }
};
