<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Scalability: index the tenant-scoping foreign keys.
     *
     * Postgres does NOT auto-index FK columns (only the PK they reference). Every
     * tutor-facing list filters on one of these columns, so without an index each
     * load is a full table scan. Harmless at 2 tutors, painful at thousands.
     *
     * Added BEFORE launch on purpose: indexing a small/empty table is instant,
     * but indexing a large LIVE table later needs CREATE INDEX CONCURRENTLY to
     * avoid locking writes. Cheap insurance taken early.
     *
     * Only the GAPS are added here — columns already covered by a unique
     * constraint or a composite index whose leading column matches the filter
     * (e.g. homeworks(student_id,status), messages(receiver_id,is_read)) are
     * left alone.
     */
    public function up(): void
    {
        // The tenancy backbone — ->students() and every roster lookup keys off this.
        Schema::table('users', function (Blueprint $table) {
            $table->index('tutor_id');
        });

        Schema::table('homeworks', function (Blueprint $table) {
            $table->index('tutor_id');   // tutor's "all homework" list
        });

        Schema::table('messages', function (Blueprint $table) {
            $table->index('sender_id');  // tutor's "sent" list
        });

        Schema::table('bills', function (Blueprint $table) {
            $table->index('tutor_id');   // tutor's bills list
        });

        Schema::table('quizzes', function (Blueprint $table) {
            $table->index('tutor_id');   // tutor's quizzes list
        });

        Schema::table('exam_papers', function (Blueprint $table) {
            $table->index('tutor_id');   // a tutor's own uploads
            $table->index('status');     // super_admin moderation queue (status = 'pending')
        });
    }

    public function down(): void
    {
        Schema::table('users', fn (Blueprint $t) => $t->dropIndex(['tutor_id']));
        Schema::table('homeworks', fn (Blueprint $t) => $t->dropIndex(['tutor_id']));
        Schema::table('messages', fn (Blueprint $t) => $t->dropIndex(['sender_id']));
        Schema::table('bills', fn (Blueprint $t) => $t->dropIndex(['tutor_id']));
        Schema::table('quizzes', fn (Blueprint $t) => $t->dropIndex(['tutor_id']));
        Schema::table('exam_papers', function (Blueprint $t) {
            $t->dropIndex(['tutor_id']);
            $t->dropIndex(['status']);
        });
    }
};
