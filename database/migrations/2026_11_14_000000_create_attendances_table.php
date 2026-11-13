<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Attendance Tracking (Slice 20). A row exists <=> the student attended
     * that day. Purely additive: a new table, no existing table is touched.
     */
    public function up(): void
    {
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tutor_id')->constrained('users')->cascadeOnDelete();   // server-side only
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->date('lesson_date');
            // Set when a bill charges this lesson; nulled again if the bill is deleted.
            $table->foreignId('bill_id')->nullable()->constrained('bills')->nullOnDelete();
            $table->timestamps();

            $table->unique(['student_id', 'lesson_date']);   // one attendance per student per day (also serves student lookups)
            $table->index('tutor_id');
            $table->index('bill_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};
