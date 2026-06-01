<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('ALTER TABLE quizzes DROP CONSTRAINT IF EXISTS quizzes_exam_type_check');
        DB::statement("ALTER TABLE quizzes ADD CONSTRAINT quizzes_exam_type_check CHECK (exam_type IN ('WA1', 'MidYear', 'WA2', 'EndYear', 'Quiz', 'PeriodicAssessment', 'TopicEvaluation'))");
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('ALTER TABLE quizzes DROP CONSTRAINT IF EXISTS quizzes_exam_type_check');
        DB::statement("ALTER TABLE quizzes ADD CONSTRAINT quizzes_exam_type_check CHECK (exam_type IN ('WA1', 'MidYear', 'WA2', 'EndYear'))");
    }
};
