<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Spelling Meow — game attempts (Slice 14, see docs/spelling-game.md).
 *
 * One row per completed round. A round belongs to exactly one student, so the
 * row IS the record (no separate question table — the per-word results are a
 * JSON snapshot). Score is recomputed server-side against config/spelling-words.php;
 * the columns here are snapshots, never client-submitted totals.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('spelling_attempts', function (Blueprint $table) {
            $table->id();
            // The student who played. Owning tutor (tenancy backbone) is set
            // server-side from the student's tutor_id.
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('tutor_id')->constrained('users')->cascadeOnDelete();

            $table->string('level');
            $table->unsignedSmallInteger('total_questions')->default(0);
            $table->unsignedSmallInteger('correct_count')->default(0);
            $table->unsignedSmallInteger('score_percent')->default(0);
            // [{ shown, answer, response, is_correct }]
            $table->json('results');

            // Student's mandatory learning reflection (filled on the results page).
            $table->text('reflection')->nullable();
            // Tutor's feedback (filled later from the tutor side).
            $table->text('feedback')->nullable();
            $table->foreignId('feedback_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('feedback_at')->nullable();

            $table->timestamps();

            // Tenant + listing scopes (FK columns are not auto-indexed in Postgres).
            $table->index('tutor_id');
            $table->index(['student_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('spelling_attempts');
    }
};
