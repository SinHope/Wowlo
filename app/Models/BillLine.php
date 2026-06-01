<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BillLine extends Model
{
    protected $fillable = [
        'bill_id',
        'lesson_date',
        'hours',
        'rate',
        'amount',
    ];

    protected function casts(): array
    {
        return [
            'lesson_date' => 'date',
            'hours' => 'decimal:2',
            'rate' => 'decimal:2',
            'amount' => 'decimal:2',
        ];
    }

    public function bill(): BelongsTo
    {
        return $this->belongsTo(Bill::class);
    }
}
