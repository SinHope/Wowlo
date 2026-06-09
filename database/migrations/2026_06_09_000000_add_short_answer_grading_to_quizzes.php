<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Slice 12 — short-answer questions + manual grading.
 *
 * Everything here is additive or a widening: new nullable columns, and two
 * columns relaxed to allow shorter/optional data. No data loss — existing
 * MCQ quizzes keep working unchanged. See docs/short-answer-quizzes.md §4.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Short answers have no correct option, so the column must allow NULL.
        Schema::table('quiz_questions', function (Blueprint $table) {
            $table->string('correct_answer')->nullable()->change();
        });

        // Free-text answers can be longer than a single MCQ letter.
        Schema::table('quiz_answers', function (Blueprint $table) {
            $table->text('student_answer')->nullable()->change();

            // Tutor-set result for short answers; mirrors MCQ result for uniform
            // "needs correction" logic. NULL until the tutor marks it.
            $table->string('grade')->nullable()->after('marks_awarded');

            // Per-question tutor feedback (text + optional drawing on R2).
            $table->text('tutor_feedback')->nullable()->after('grade');
            $table->string('tutor_feedback_image_path')->nullable()->after('tutor_feedback');
            $table->string('tutor_feedback_image_name')->nullable()->after('tutor_feedback_image_path');
        });

        // graded_at: NULL = awaiting tutor marking; set = fully graded.
        Schema::table('quiz_attempts', function (Blueprint $table) {
            $table->timestamp('graded_at')->nullable()->after('completed_at');
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE quiz_answers ADD CONSTRAINT quiz_answers_grade_check CHECK (grade IN ('correct', 'partial', 'wrong'))");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE quiz_answers DROP CONSTRAINT IF EXISTS quiz_answers_grade_check');
        }

        Schema::table('quiz_attempts', function (Blueprint $table) {
            $table->dropColumn('graded_at');
        });

        Schema::table('quiz_answers', function (Blueprint $table) {
            $table->dropColumn(['grade', 'tutor_feedback', 'tutor_feedback_image_path', 'tutor_feedback_image_name']);
            $table->string('student_answer')->nullable()->change();
        });

        Schema::table('quiz_questions', function (Blueprint $table) {
            $table->string('correct_answer')->nullable(false)->change();
        });
    }
};
