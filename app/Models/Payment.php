<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    protected $fillable = [
        'student_id',
        'amount_paid',
        'payment_date',
        'payment_method',
        'remarks',
    ];

    protected function casts(): array
    {
        return [
            'amount_paid' => 'decimal:2',
            'payment_date' => 'date',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    /**
     * Human-friendly label for the payment method.
     */
    public function methodLabel(): string
    {
        return match ($this->payment_method) {
            'cash' => 'Cash',
            'bank_transfer' => 'Bank Transfer',
            'paynow' => 'PayNow',
            'paypal' => 'PayPal',
            default => ucfirst($this->payment_method),
        };
    }
}
