<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * One numbered row per question on an answer sheet.
 *
 * The row holds both the STRUCTURE (order, marks, how many MCQ options) and the
 * student's ANSWER (choice / answer_text) plus the tutor's MARKING
 * (grade, marks_awarded, tutor_feedback). Folding answer + marking into the row
 * is safe here because each sheet has exactly one student, so each question has
 * exactly one answer — no need for a separate responses table.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('answer_sheet_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('answer_sheet_id')->constrained('answer_sheets')->cascadeOnDelete();
            $table->unsignedSmallInteger('order')->default(0);
            $table->unsignedTinyInteger('num_options')->default(4); // MCQ only (e.g. 1–4)
            $table->unsignedSmallInteger('marks')->default(1);

            // Student's answer.
            $table->unsignedTinyInteger('choice')->nullable(); // MCQ: 1..num_options
            $table->text('answer_text')->nullable();           // short answer

            // Tutor's marking.
            $table->string('grade')->nullable();               // correct | partial | wrong
            $table->decimal('marks_awarded', 6, 2)->default(0);
            $table->text('tutor_feedback')->nullable();

            $table->timestamps();

            $table->index('answer_sheet_id');
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE answer_sheet_questions ADD CONSTRAINT answer_sheet_questions_grade_check CHECK (grade IN ('correct', 'partial', 'wrong'))");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('answer_sheet_questions');
    }
};
