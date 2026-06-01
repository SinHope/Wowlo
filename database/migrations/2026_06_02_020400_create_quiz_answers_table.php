<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quiz_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attempt_id')->constrained('quiz_attempts')->cascadeOnDelete();
            $table->foreignId('question_id')->constrained('quiz_questions')->cascadeOnDelete();
            $table->string('student_answer')->nullable(); // A | B | C | D
            $table->boolean('is_correct')->default(false);
            $table->unsignedSmallInteger('marks_awarded')->default(0);
            $table->text('ai_feedback')->nullable();  // Phase 2 only
            $table->text('correction')->nullable();   // student-written correction for wrong answers
            $table->timestamps();

            $table->index('attempt_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quiz_answers');
    }
};
