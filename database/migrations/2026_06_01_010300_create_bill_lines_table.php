<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bill_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bill_id')->constrained('bills')->cascadeOnDelete();
            $table->date('lesson_date');
            $table->decimal('hours', 5, 2);     // actual hours for this lesson (e.g. 1.50)
            $table->decimal('rate', 8, 2);      // rate snapshot at billing time
            $table->decimal('amount', 10, 2);   // hours × rate
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bill_lines');
    }
};
