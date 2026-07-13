<?php

use App\Models\BalanceTransaction;
use App\Models\DirectPurchase;
use App\Models\DirectPurchasePayment;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Due direct purchases now hit the employee's wallet: approval debits the cost
 * (allowed to go negative — that negative IS the company's debt) and each
 * settlement payment credits it back. Purchases approved before that change carry
 * no ledger rows, so a later payment would credit a debit that never happened.
 * This replays both sides for them, in the order they originally occurred.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function () {
            $purchases = DirectPurchase::query()
                ->with('payments')
                ->where('status', 'approved')
                ->where('payment_type', 'due')
                ->get();

            foreach ($purchases as $purchase) {
                $alreadyLedgered = BalanceTransaction::query()
                    ->where('reference_type', DirectPurchase::class)
                    ->where('reference_id', $purchase->id)
                    ->exists();

                if ($alreadyLedgered) {
                    continue;
                }

                $user = User::query()->whereKey($purchase->employee_id)->lockForUpdate()->first();

                if (! $user) {
                    continue;
                }

                $balance = (float) $user->balance - (float) $purchase->grand_total;

                BalanceTransaction::create([
                    'user_id'        => $user->id,
                    'type'           => 'debit_direct_purchase_due',
                    'amount'         => -(float) $purchase->grand_total,
                    'balance_after'  => $balance,
                    'reference_type' => DirectPurchase::class,
                    'reference_id'   => $purchase->id,
                    'note'           => 'Direct purchase '.$purchase->purchase_number.' (out of pocket)',
                    'created_by'     => $purchase->approved_by,
                ]);

                foreach ($purchase->payments->sortBy('created_at') as $payment) {
                    $balance += (float) $payment->amount;

                    BalanceTransaction::create([
                        'user_id'        => $user->id,
                        'type'           => 'credit_direct_purchase_settlement',
                        'amount'         => (float) $payment->amount,
                        'balance_after'  => $balance,
                        'reference_type' => DirectPurchasePayment::class,
                        'reference_id'   => $payment->id,
                        'note'           => 'Settlement for direct purchase '.$purchase->purchase_number,
                        'created_by'     => $payment->paid_by,
                    ]);
                }

                $user->update(['balance' => $balance]);
            }
        });
    }

    public function down(): void
    {
        // Data backfill — reversing it would desync the wallet from the ledger.
    }
};
