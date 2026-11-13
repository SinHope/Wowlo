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

    /**
     * IDs of this student's bills that are fully paid, derived — never stored.
     *
     * Bills have no "paid" flag (outstanding is derived from the ledger), so we
     * apply payments oldest-bill-first (FIFO by billing_month, then id): a bill
     * is paid once the running total of bills up to and including it is covered
     * by everything paid so far. Everything after the first unpaid bill stays
     * unpaid, even if a later bill is smaller. Used to turn attendance days green.
     *
     * @return array<int, int>
     */
    public static function paidBillIds(User $student): array
    {
        $paid = static::totalPaid($student);
        $running = 0.0;
        $ids = [];

        $bills = $student->bills()
            ->orderBy('billing_month')
            ->orderBy('id')
            ->get(['id', 'charges_total']);

        foreach ($bills as $bill) {
            $running = round($running + (float) $bill->charges_total, 2);

            if ($running > $paid + 0.005) {
                break;
            }

            $ids[] = $bill->id;
        }

        return $ids;
    }
}
