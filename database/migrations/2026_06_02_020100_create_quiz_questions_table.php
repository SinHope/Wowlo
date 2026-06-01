<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quiz_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quiz_id')->constrained('quizzes')->cascadeOnDelete();
            $table->text('question_text');
            $table->string('question_type')->default('mcq'); // mcq | short_answer (Phase 2)
            $table->string('option_a')->nullable();
            $table->string('option_b')->nullable();
            $table->string('option_c')->nullable();
            $table->string('option_d')->nullable();
            $table->string('correct_answer'); // A | B | C | D
            $table->unsignedSmallInteger('marks')->default(1);
            $table->unsignedSmallInteger('order')->default(0);
            $table->timestamps();

            $table->index(['quiz_id', 'order']);
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE quiz_questions ADD CONSTRAINT quiz_questions_type_check CHECK (question_type IN ('mcq', 'short_answer'))");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('quiz_questions');
    }
};
