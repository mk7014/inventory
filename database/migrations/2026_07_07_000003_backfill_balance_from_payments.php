<?php

use App\Models\Payment;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Backfill balance_transactions from payments made before balance crediting
     * moved into PaymentService, then recompute each user's cached balance.
     * Idempotent: skips any payment that already has a ledger row.
     */
    public function up(): void
    {
        DB::transaction(function () {
            $payments = DB::table('payments')->orderBy('paid_to')->orderBy('payment_date')->orderBy('id')->get();

            $running = [];
            foreach ($payments as $payment) {
                $userId = $payment->paid_to;
                $running[$userId] = ($running[$userId] ?? 0) + (float) $payment->amount;

                $exists = DB::table('balance_transactions')
                    ->where('reference_type', Payment::class)
                    ->where('reference_id', $payment->id)
                    ->exists();

                if ($exists) {
                    continue;
                }

                DB::table('balance_transactions')->insert([
                    'user_id' => $userId,
                    'type' => 'credit_payment',
                    'amount' => $payment->amount,
                    'balance_after' => $running[$userId],
                    'reference_type' => Payment::class,
                    'reference_id' => $payment->id,
                    'note' => 'Payment #'.$payment->id.' (backfilled)',
                    'created_by' => $payment->paid_by,
                    'created_at' => $payment->payment_date,
                    'updated_at' => now(),
                ]);
            }

            // Recompute cached balance from the ledger for every affected user.
            $totals = DB::table('balance_transactions')
                ->select('user_id', DB::raw('SUM(amount) as total'))
                ->groupBy('user_id')
                ->pluck('total', 'user_id');

            foreach ($totals as $userId => $total) {
                DB::table('users')->where('id', $userId)->update(['balance' => $total]);
            }
        });
    }

    public function down(): void
    {
        // Non-destructive: leave backfilled ledger rows in place.
    }
};
