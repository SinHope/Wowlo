<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tuition_fees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->decimal('fee_rate_per_hour', 8, 2);   // hourly rate, e.g. 50.00
            $table->string('currency', 3)->default('SGD');
            $table->text('remarks')->nullable();
            $table->timestamps();

            // One fee structure per student.
            $table->unique('student_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tuition_fees');
    }
};
