<?php

namespace Database\Seeders;

use App\Models\HangmanWheel;
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
        // The platform owner. Super_admin teaches their own roster AND manages
        // tutor accounts + approves shared exam papers. Google login links here.
        $superAdmin = User::updateOrCreate(
            ['email' => 'nasmerfontanilla@gmail.com'],
            [
                'name' => 'Nas (Admin)',
                'password' => 'password',   // change after first login
                'role' => 'super_admin',
            ]
        );

        // A second tutor (e.g. a friend invited to test) — proves isolation:
        // this tutor must never see the super_admin's students, and vice versa.
        $friendTutor = User::updateOrCreate(
            ['email' => 'tutor@wowlo.test'],
            [
                'name' => 'Sample Tutor',
                'password' => 'password',
                'role' => 'tutor',
            ]
        );

        // Each teacher gets one student, owned via tutor_id.
        User::updateOrCreate(
            ['email' => 'student@wowlo.test'],
            [
                'name' => 'Sample Student (Nas)',
                'password' => 'password',
                'role' => 'student',
                'tutor_id' => $superAdmin->id,
                'phone_1' => '90000000',
            ]
        );

        User::updateOrCreate(
            ['email' => 'student2@wowlo.test'],
            [
                'name' => "Sample Student (Tutor)",
                'password' => 'password',
                'role' => 'student',
                'tutor_id' => $friendTutor->id,
                'phone_1' => '90000001',
            ]
        );

        // The global "standard" Hangman wheel — authored by the super_admin and
        // playable by everyone. Tutors add their own custom wheels in-app.
        HangmanWheel::updateOrCreate(
            ['type' => 'standard', 'name' => 'Wowlo Standard Wheel'],
            [
                'created_by' => $superAdmin->id,
                'tutor_id'   => null,
                'slices'     => [
                    '+1 Free Guess', 'Reveal a Letter', 'Spin Again', '+2 Free Guesses',
                    'Lose a Guess', 'Good Luck!', 'Free Vowel', 'No Help',
                    'Mystery Letter', 'Try Again',
                ],
            ]
        );
    }
}
