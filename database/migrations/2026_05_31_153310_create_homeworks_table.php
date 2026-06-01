<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('homeworks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tutor_id')->constrained('users')->cascadeOnDelete();    // creator
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();  // assignee
            $table->string('title');
            $table->string('subject');
            $table->text('description');
            $table->date('start_date')->nullable();
            $table->date('due_date');

            // Status folded onto the homework row (no separate homework_statuses table — see v2.1 #4)
            $table->string('status')->default('pending');   // 'pending' | 'done'
            $table->timestamp('completed_at')->nullable();

            // Attachment stored on Cloudflare R2 (private). Column holds the R2 object key.
            $table->string('attachment_path')->nullable();
            $table->string('attachment_name')->nullable();  // original filename for display

            $table->timestamps();

            $table->index(['student_id', 'status']);
        });

        // Postgres-only CHECK (SQLite in tests can't ALTER ADD CONSTRAINT). Also app-validated.
        if (DB::getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE homeworks ADD CONSTRAINT homeworks_status_check CHECK (status IN ('pending', 'done'))");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('homeworks');
    }
};
