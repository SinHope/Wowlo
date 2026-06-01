<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // The first tutor/admin account. Google login will link to this on first use.
        User::updateOrCreate(
            ['email' => 'nasmerfontanilla@gmail.com'],
            [
                'name' => 'Nas (Tutor)',
                'password' => 'password',   // change after first login
                'role' => 'tutor',
            ]
        );

        // A sample student account for testing the student side later.
        User::updateOrCreate(
            ['email' => 'student@wowlo.test'],
            [
                'name' => 'Sample Student',
                'password' => 'password',
                'role' => 'student',
                'phone_1' => '90000000',
            ]
        );
    }
}
