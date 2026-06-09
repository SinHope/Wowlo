<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Half-marks: tutors award partial credit (e.g. 0.5) on short answers, so the
 * marks a student earns must hold fractions. Question totals stay whole.
 *
 * Widening smallint → decimal is loss-free (every existing integer fits).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quiz_answers', function (Blueprint $table) {
            $table->decimal('marks_awarded', 6, 2)->default(0)->change();
        });

        Schema::table('quiz_attempts', function (Blueprint $table) {
            $table->decimal('obtained_marks', 6, 2)->default(0)->change();
        });
    }

    public function down(): void
    {
        Schema::table('quiz_answers', function (Blueprint $table) {
            $table->unsignedSmallInteger('marks_awarded')->default(0)->change();
        });

        Schema::table('quiz_attempts', function (Blueprint $table) {
            $table->unsignedSmallInteger('obtained_marks')->default(0)->change();
        });
    }
};
