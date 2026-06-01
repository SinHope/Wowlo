<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bills', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('tutor_id')->constrained('users')->cascadeOnDelete();
            $table->date('billing_month');   // first day of the billed month

            // Snapshots taken at generation time (history must not change if rates change later).
            $table->decimal('lessons_subtotal', 10, 2)->default(0);    // Σ lesson lines
            $table->decimal('additional_total', 10, 2)->default(0);    // Σ extra charges
            $table->decimal('charges_total', 10, 2)->default(0);       // lessons + additional → added to ledger
            $table->decimal('outstanding_before', 10, 2)->default(0);  // carried-over balance shown on the bill
            $table->decimal('grand_total', 10, 2)->default(0);         // charges_total + outstanding_before
            $table->string('currency', 3)->default('SGD');
            $table->timestamps();

            $table->index(['student_id', 'billing_month']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bills');
    }
};
