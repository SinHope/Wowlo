<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TuitionFee extends Model
{
    protected $fillable = [
        'student_id',
        'fee_rate_per_hour',
        'currency',
        'remarks',
    ];

    protected function casts(): array
    {
        return [
            'fee_rate_per_hour' => 'decimal:2',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }
}
