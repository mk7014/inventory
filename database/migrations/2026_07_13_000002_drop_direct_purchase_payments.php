<?php

use App\Models\BalanceTransaction;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Settling a due direct purchase is gone. A due purchase is money the employee
 * spent from their own pocket: approval debits it from their balance and it simply
 * stays deducted (possibly negative) until the company credits their balance again.
 * There is no per-purchase "paid / outstanding" tracking any more.
 *
 * So: undo the settlement credits that briefly existed (re-deducting them and
 * repairing the running balance_after chain), then drop the payments table and the
 * now-meaningless paid_amount / payment_status columns.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function () {
            $userIds = BalanceTransaction::query()
                ->where('type', 'credit_direct_purchase_settlement')
                ->distinct()
                ->pluck('user_id');

            BalanceTransaction::query()->where('type', 'credit_direct_purchase_settlement')->delete();

            // Deleting mid-ledger rows invalidates every later balance_after, so
            // replay each affected user's ledger and re-cache users.balance.
            foreach ($userIds as $userId) {
                $user = User::query()->whereKey($userId)->lockForUpdate()->first();

                if (! $user) {
                    continue;
                }

                $running = 0.0;

                foreach (BalanceTransaction::query()->where('user_id', $userId)->orderBy('id')->get() as $tx) {
                    $running += (float) $tx->amount;
                    $tx->update(['balance_after' => $running]);
                }

                $user->update(['balance' => $running]);
            }
        });

        Schema::dropIfExists('direct_purchase_payments');

        Schema::table('direct_purchases', function (Blueprint $table) {
            $table->dropColumn(['paid_amount', 'payment_status']);
        });
    }

    public function down(): void
    {
        Schema::table('direct_purchases', function (Blueprint $table) {
            $table->decimal('paid_amount', 12, 2)->default(0)->after('grand_total');
            $table->string('payment_status')->default('due')->after('status');
        });

        Schema::create('direct_purchase_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('direct_purchase_id')->constrained()->cascadeOnDelete();
            $table->foreignId('paid_to')->constrained('users');
            $table->foreignId('paid_by')->constrained('users');
            $table->decimal('amount', 12, 2);
            $table->string('payment_method');
            $table->dateTime('payment_date');
            $table->string('reference')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();
        });
    }
};
