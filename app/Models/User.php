<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable([
    'name',
    'email',
    'password',
    'google_id',
    'role',
    'phone_1',
    'phone_2',
    'phone_3',
    'phone_4',
    'phone_5',
    'address',
])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Is this user a tutor/admin?
     */
    public function isTutor(): bool
    {
        return $this->role === 'tutor';
    }

    /**
     * Is this user a student/parent?
     */
    public function isStudent(): bool
    {
        return $this->role === 'student';
    }

    /**
     * Homework assigned to this user (as a student).
     */
    public function homework(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Homework::class, 'student_id');
    }

    /**
     * Homework created by this user (as a tutor).
     */
    public function homeworkCreated(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Homework::class, 'tutor_id');
    }
}
