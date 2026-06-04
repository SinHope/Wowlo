<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use NotificationChannels\WebPush\HasPushSubscriptions;

#[Fillable([
    'name',
    'email',
    'password',
    'google_id',
    'role',
    // Always set server-side (auth()->id() or null). Request rules never include
    // it, so validated() strips any client-supplied value before create/update.
    'tutor_id',
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
    use HasFactory, HasPushSubscriptions, Notifiable;

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
            'onboarded_at' => 'datetime',
        ];
    }

    /**
     * Has this user never finished/skipped the welcome tour? Drives whether the
     * onboarding modal auto-shows. Set server-side via OnboardingController.
     */
    public function needsOnboarding(): bool
    {
        return is_null($this->onboarded_at);
    }

    /**
     * Is this user strictly a tutor (not the super_admin)?
     */
    public function isTutor(): bool
    {
        return $this->role === 'tutor';
    }

    /**
     * Is this user the super_admin (the platform owner)?
     */
    public function isSuperAdmin(): bool
    {
        return $this->role === 'super_admin';
    }

    /**
     * Can this user act in the tutor workspace? The super_admin teaches their
     * own roster too, so they get every tutor capability plus admin extras.
     * Use this (not isTutor) for "is this a teaching account?" checks.
     */
    public function actsAsTutor(): bool
    {
        return in_array($this->role, ['tutor', 'super_admin'], true);
    }

    /**
     * Is this user a student/parent?
     */
    public function isStudent(): bool
    {
        return $this->role === 'student';
    }

    /**
     * The tutor who owns this student account (1 student → 1 tutor).
     * Null for tutor / super_admin rows.
     */
    public function tutor(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'tutor_id');
    }

    /**
     * The students owned by this tutor (their roster). The tenancy backbone:
     * every tutor-facing list scopes off this.
     */
    public function students(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(User::class, 'tutor_id')->where('role', 'student');
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

    /**
     * Quizzes created by this user (as a tutor).
     */
    public function quizzesCreated(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Quiz::class, 'tutor_id');
    }

    /**
     * Quizzes assigned to this user (as a student).
     */
    public function quizAssignments(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(QuizAssignment::class, 'student_id');
    }

    /**
     * This student's quiz attempts.
     */
    public function quizAttempts(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(QuizAttempt::class, 'student_id');
    }
}
