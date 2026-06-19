<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Resources — answer sheets (Slice 13).
 *
 * A lightweight "OAS / answer sheet" that pairs with a paper exam: a tutor
 * builds blank numbered rows and sends them to ONE student to fill, or a
 * student builds + fills their own to submit to their tutor. Marking is always
 * manual and per-question.
 *
 * Because a sheet belongs to exactly one student, the sheet row IS the
 * assignment + the submission (no separate attempt/assignment tables like
 * quizzes need). Money/marks are recomputed server-side; the columns here are
 * snapshots of the tutor's marking, never client-submitted totals.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('answer_sheets', function (Blueprint $table) {
            $table->id();
            // Who created the sheet (a tutor/super_admin OR a student).
            $table->foreignId('author_id')->constrained('users')->cascadeOnDelete();
            // Owning tutor — the tenancy backbone. Set server-side only.
            $table->foreignId('tutor_id')->constrained('users')->cascadeOnDelete();
            // The student who fills it in / owns the submission.
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->string('type');     // mcq | short_answer
            $table->string('title');
            $table->string('subject');
            $table->string('status')->default('sent'); // sent | submitted | marked
            $table->unsignedSmallInteger('total_marks')->default(0);
            $table->decimal('obtained_marks', 6, 2)->default(0);
            $table->text('feedback')->nullable();          // overall remarks from the tutor
            $table->timestamp('submitted_at')->nullable(); // student submitted
            $table->timestamp('marked_at')->nullable();    // tutor finished marking
            $table->timestamps();

            // Tenant + listing scopes (FK columns are not auto-indexed in Postgres).
            $table->index('tutor_id');
            $table->index(['student_id', 'status']);
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE answer_sheets ADD CONSTRAINT answer_sheets_type_check CHECK (type IN ('mcq', 'short_answer'))");
            DB::statement("ALTER TABLE answer_sheets ADD CONSTRAINT answer_sheets_status_check CHECK (status IN ('sent', 'submitted', 'marked'))");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('answer_sheets');
    }
};
