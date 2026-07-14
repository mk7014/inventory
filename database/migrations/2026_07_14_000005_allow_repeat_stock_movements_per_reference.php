<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * stock_movements carried a unique key on (reference_type, reference_id, type) as an
 * idempotency guard — it assumed a reference can only ever produce one movement of a
 * given type, which held while a sale could only walk the lifecycle forwards.
 *
 * Administrators can now override a sale to any status to repair a mistake, so a sale
 * that is shipped, wrongly cancelled, then put back to shipped genuinely books twice.
 * That is real ledger history, not a double-apply, and the unique key would reject it.
 *
 * Double-application is still prevented where it actually matters: SaleStatusService
 * derives the movements from the gap between the sale's stored stock position and the
 * one its new status implies, under a row lock on the sale — so re-entering a status
 * moves no stock. The products CHECK constraints (current_stock >= 0, booked_stock <=
 * current_stock) remain as the hard backstop against corruption.
 *
 * balance_transactions keeps its unique key: nothing there replays.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stock_movements', function (Blueprint $table) {
            $table->dropUnique('stock_movements_reference_unique');
            $table->index(['reference_type', 'reference_id', 'type'], 'stock_movements_reference_index');
        });
    }

    public function down(): void
    {
        Schema::table('stock_movements', function (Blueprint $table) {
            $table->dropIndex('stock_movements_reference_index');
            $table->unique(['reference_type', 'reference_id', 'type'], 'stock_movements_reference_unique');
        });
    }
};
