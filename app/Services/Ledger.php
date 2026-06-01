<?php

namespace App\Services;

use App\Models\User;

/**
 * The money ledger for a single student.
 *
 * Outstanding is never stored — it is always derived:
 *
 *     outstanding = Σ(bill charges_total) − Σ(payments amount_paid)
 *
 * A positive number means the parent owes money; a negative number means
 * they are in credit (prepaid).
 */
class Ledger
{
    /**
     * Total ever billed to this student (sum of each bill's lessons + extra charges).
     */
    public static function totalBilled(User $student): float
    {
        return round((float) $student->bills()->sum('charges_total'), 2);
    }

    /**
     * Total ever paid by this student.
     */
    public static function totalPaid(User $student): float
    {
        return round((float) $student->payments()->sum('amount_paid'), 2);
    }

    /**
     * Current outstanding balance (positive = owed, negative = credit).
     */
    public static function outstanding(User $student): float
    {
        return round(static::totalBilled($student) - static::totalPaid($student), 2);
    }
}
