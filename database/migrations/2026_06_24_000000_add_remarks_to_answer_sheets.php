<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Resources — tutor "remarks" (special instructions), shown to the student.
 *
 * Additive + nullable (see docs/DATABASE.md §1): a sheet-level remark for
 * instructions across the whole sheet, and a per-question remark for a note on
 * one specific question. Distinct from the marking `feedback` / `tutor_feedback`
 * columns, which the tutor writes AFTER the student submits.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('answer_sheets', function (Blueprint $table) {
            $table->text('remarks')->nullable();
        });

        Schema::table('answer_sheet_questions', function (Blueprint $table) {
            $table->text('remarks')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('answer_sheets', function (Blueprint $table) {
            $table->dropColumn('remarks');
        });

        Schema::table('answer_sheet_questions', function (Blueprint $table) {
            $table->dropColumn('remarks');
        });
    }
};
