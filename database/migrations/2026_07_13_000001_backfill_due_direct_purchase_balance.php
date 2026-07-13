<?php

use App\Models\BalanceTransaction;
use App\Models\DirectPurchase;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Due direct purchases now hit the employee's wallet: approval debits the cost and
 * is allowed to push the balance negative, because that negative IS the company's
 * standing debt for money the employee spent out of pocket. Purchases approved
 * before that change carry no ledger row at all, so this replays the debit for
 * them and leaves the balance reduced.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function () {
            $purchases = DirectPurchase::query()
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

                $user->update(['balance' => $balance]);
            }
        });
    }

    public function down(): void
    {
        // Data backfill — reversing it would desync the wallet from the ledger.
    }
};
