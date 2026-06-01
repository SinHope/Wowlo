<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->decimal('amount_paid', 8, 2);
            $table->date('payment_date');
            $table->string('payment_method');   // cash | bank_transfer | paynow | paypal
            $table->text('remarks')->nullable();
            $table->timestamps();

            $table->index('student_id');
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE payments ADD CONSTRAINT payments_method_check CHECK (payment_method IN ('cash', 'bank_transfer', 'paynow', 'paypal'))");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
