<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Multiplication Rabbit — game attempts (Slice 17, see docs/multiplication-game.md).
 *
 * One row per completed round, mirroring spelling_attempts. The per-question
 * results are a JSON snapshot ([{a, b, answer, response, is_correct}]). Score is
 * recomputed server-side (factors are range-validated against
 * config/multiplication-levels.php); the columns here are snapshots, never
 * client-submitted totals.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('multiplication_attempts', function (Blueprint $table) {
            $table->id();
            // The student who played. Owning tutor (tenancy backbone) is set
            // server-side from the student's tutor_id.
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('tutor_id')->constrained('users')->cascadeOnDelete();

            $table->string('level');
            $table->unsignedSmallInteger('total_questions')->default(0);
            $table->unsignedSmallInteger('correct_count')->default(0);
            $table->unsignedSmallInteger('score_percent')->default(0);
            // [{ a, b, answer, response, is_correct }]
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
        Schema::dropIfExists('multiplication_attempts');
    }
};
