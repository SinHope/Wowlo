<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Exam papers become a SHARED, MODERATED library. Approved papers are
     * visible to every tutor and student; a non-admin tutor's upload is a
     * `pending` submission until the super_admin approves it.
     *
     * Existing rows default to 'approved' so nothing already in the library
     * disappears. New uploads set the status explicitly in the controller.
     */
    public function up(): void
    {
        Schema::table('exam_papers', function (Blueprint $table) {
            $table->string('status')->default('approved')->after('remarks');
            $table->foreignId('approved_by')->nullable()->after('status')->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable()->after('approved_by');
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE exam_papers ADD CONSTRAINT exam_papers_status_check CHECK (status IN ('pending', 'approved'))");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE exam_papers DROP CONSTRAINT IF EXISTS exam_papers_status_check');
        }

        Schema::table('exam_papers', function (Blueprint $table) {
            $table->dropConstrainedForeignId('approved_by');
            $table->dropColumn(['status', 'approved_at']);
        });
    }
};
