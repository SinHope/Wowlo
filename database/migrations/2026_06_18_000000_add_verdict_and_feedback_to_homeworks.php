<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('homeworks', function (Blueprint $table) {
            // A single editable tutor note, shown to the student/parent.
            $table->text('feedback')->nullable()->after('completed_at');
        });

        // Widen the verdict set: the tutor now owns done/not_done; the student
        // can only claim 'submitted' (awaiting check). 'pending' stays the
        // default. Overdue is derived from due_date, never stored.
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE homeworks DROP CONSTRAINT IF EXISTS homeworks_status_check');
            DB::statement("ALTER TABLE homeworks ADD CONSTRAINT homeworks_status_check CHECK (status IN ('pending', 'submitted', 'done', 'not_done'))");
        }
    }

    public function down(): void
    {
        // Reset any new states to 'pending' so the original 2-value CHECK holds.
        DB::table('homeworks')->whereIn('status', ['submitted', 'not_done'])->update(['status' => 'pending']);

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE homeworks DROP CONSTRAINT IF EXISTS homeworks_status_check');
            DB::statement("ALTER TABLE homeworks ADD CONSTRAINT homeworks_status_check CHECK (status IN ('pending', 'done'))");
        }

        Schema::table('homeworks', function (Blueprint $table) {
            $table->dropColumn('feedback');
        });
    }
};
