<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');

            // Wowlo additions
            $table->string('google_id')->nullable()->unique();   // set when user logs in via Google
            $table->string('role')->default('student');          // 'student' | 'tutor' (see CHECK below)
            $table->string('phone_1')->nullable();               // student's own number
            $table->string('phone_2')->nullable();               // father
            $table->string('phone_3')->nullable();               // mother
            $table->string('phone_4')->nullable();               // next of kin
            $table->string('phone_5')->nullable();               // home (optional)
            $table->text('address')->nullable();

            $table->rememberToken();
            $table->timestamps();
        });

        // Enforce valid roles at the DB level (string + CHECK instead of a native enum).
        // Postgres only — SQLite (used in tests) doesn't support ALTER TABLE ADD CONSTRAINT;
        // role values are also validated at the application layer regardless.
        if (DB::getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE users ADD CONSTRAINT users_role_check CHECK (role IN ('student', 'tutor'))");
        }

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
