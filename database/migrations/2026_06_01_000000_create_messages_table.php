<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sender_id')->constrained('users')->cascadeOnDelete();    // tutor
            $table->foreignId('receiver_id')->constrained('users')->cascadeOnDelete();  // student/parent
            $table->string('subject');
            $table->text('body');
            $table->boolean('is_read')->default(false);
            $table->timestamps();

            // Inbox queries filter by receiver + unread, newest first.
            $table->index(['receiver_id', 'is_read']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};
