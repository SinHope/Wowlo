<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exam_papers', function (Blueprint $table) {
            // Nullable so existing rows survive; new uploads always set it (validated).
            $table->string('level')->nullable()->after('tutor_id');
            $table->index(['level', 'subject', 'year']);
        });
    }

    public function down(): void
    {
        Schema::table('exam_papers', function (Blueprint $table) {
            $table->dropIndex(['level', 'subject', 'year']);
            $table->dropColumn('level');
        });
    }
};
