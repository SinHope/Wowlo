<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exam_papers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tutor_id')->constrained('users')->cascadeOnDelete();
            $table->string('title');
            $table->string('subject');
            $table->unsignedSmallInteger('year');
            $table->string('file_path');
            $table->string('original_filename');
            $table->text('remarks')->nullable();
            $table->timestamps();
            $table->index(['subject', 'year']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_papers');
    }
};
