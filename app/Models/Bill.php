<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Bill extends Model
{
    protected $fillable = [
        'student_id',
        'tutor_id',
        'billing_month',
        'lessons_subtotal',
        'additional_total',
        'charges_total',
        'outstanding_before',
        'grand_total',
        'currency',
    ];

    protected function casts(): array
    {
        return [
            'billing_month' => 'date',
            'lessons_subtotal' => 'decimal:2',
            'additional_total' => 'decimal:2',
            'charges_total' => 'decimal:2',
            'outstanding_before' => 'decimal:2',
            'grand_total' => 'decimal:2',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function tutor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'tutor_id');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(BillLine::class);
    }

    public function charges(): HasMany
    {
        return $this->hasMany(BillCharge::class);
    }
}
