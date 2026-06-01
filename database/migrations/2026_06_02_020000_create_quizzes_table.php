<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quizzes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tutor_id')->constrained('users')->cascadeOnDelete();
            $table->string('title');
            $table->string('subject');
            $table->string('level');
            $table->string('topic')->nullable();
            $table->string('exam_type'); // WA1 | MidYear | WA2 | EndYear
            $table->timestamps();

            $table->index(['level', 'subject']);
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE quizzes ADD CONSTRAINT quizzes_exam_type_check CHECK (exam_type IN ('WA1', 'MidYear', 'WA2', 'EndYear'))");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('quizzes');
    }
};
