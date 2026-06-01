<?php

namespace App\Services;

use App\Models\Bill;

/**
 * Builds the WhatsApp billing message for a saved bill. This is the single
 * source of truth for the message format (the create page mirrors it live).
 */
class BillMessage
{
    public static function for(Bill $bill): string
    {
        $bill->loadMissing(['student', 'lines', 'charges']);

        $cur = $bill->currency;
        $money = fn ($n) => $cur.' '.number_format((float) $n, 2);
        $month = $bill->billing_month->format('F Y');

        $lines = [];
        $lines[] = "Hi! Here is the tuition fee for {$bill->student->name} — {$month}.";
        $lines[] = '';
        $lines[] = 'Lessons:';

        foreach ($bill->lines as $line) {
            $date = $line->lesson_date->format('d M');
            $hours = rtrim(rtrim(number_format((float) $line->hours, 2), '0'), '.');
            $lines[] = "- {$date}: {$hours}h × {$money($line->rate)} = {$money($line->amount)}";
        }

        $lines[] = "Lessons subtotal: {$money($bill->lessons_subtotal)}";

        if ($bill->charges->isNotEmpty()) {
            $lines[] = '';
            $lines[] = 'Additional charges:';
            foreach ($bill->charges as $charge) {
                $lines[] = "- {$charge->description}: {$money($charge->amount)}";
            }
        }

        if (abs((float) $bill->outstanding_before) > 0.001) {
            $lines[] = '';
            $lines[] = "Outstanding balance (carried over): {$money($bill->outstanding_before)}";
        }

        $lines[] = '';
        $lines[] = "*Grand total due: {$money($bill->grand_total)}*";

        if ($paynow = config('wowlo.paynow_number')) {
            $lines[] = '';
            $lines[] = "PayNow: {$paynow}";
        }

        $lines[] = 'Thank you!';

        return implode("\n", $lines);
    }
}
