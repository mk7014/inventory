<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Direct purchases no longer split into "advance" (spend the wallet) and "due"
 * (out of pocket). Every purchase now behaves the same way: on approval the cost
 * comes off the employee's balance, with nothing blocking it — if the balance runs
 * out it simply goes negative, and that negative is what the company owes back
 * until the wallet is credited again. With no branch left, the column is dead.
 */
return new class extends Migration
{
    public function up(): void
    {
        // One ledger type for every direct purchase debit now.
        DB::table('balance_transactions')
            ->where('type', 'debit_direct_purchase_due')
            ->update(['type' => 'debit_direct_purchase']);

        Schema::table('direct_purchases', function (Blueprint $table) {
            $table->dropColumn('payment_type');
        });
    }

    public function down(): void
    {
        Schema::table('direct_purchases', function (Blueprint $table) {
            $table->string('payment_type')->default('advance')->after('warehouse_id');
        });
    }
};
