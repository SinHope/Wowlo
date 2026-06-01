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

    /**
     * Messages this user has received (as a student/parent).
     */
    public function receivedMessages(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Message::class, 'receiver_id');
    }

    /**
     * Messages this user has sent (as a tutor).
     */
    public function sentMessages(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Message::class, 'sender_id');
    }

    /**
     * This student's fee structure (hourly rate). One per student.
     */
    public function tuitionFee(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(TuitionFee::class, 'student_id');
    }

    /**
     * Payments this student has made.
     */
    public function payments(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Payment::class, 'student_id');
    }

    /**
     * Bills issued to this student.
     */
    public function bills(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Bill::class, 'student_id');
    }
}
