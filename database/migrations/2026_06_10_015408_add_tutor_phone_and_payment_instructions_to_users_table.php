<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per-tutor contact + payment details (multi-tutor). Each tutor sets their own
 * payment instructions, which flow into their WhatsApp bills — replacing the
 * single global PAYNOW_NUMBER. Both nullable & additive (safe on live data).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // The tutor's own contact number (distinct from phone_1..5, which are
            // a student's/parents' numbers).
            $table->string('phone_number')->nullable()->after('email');
            // Free-text payment line shown at the bottom of every bill, e.g.
            // "PayNow: 9123 4567" or "Bank transfer DBS 123-456-789". Blank = omitted.
            $table->text('payment_instructions')->nullable()->after('phone_number');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['phone_number', 'payment_instructions']);
        });
    }
};
