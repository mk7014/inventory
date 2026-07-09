<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Maps the legacy two-state model onto the new lifecycle without changing any
     * realised numbers:
     *   - 'completed' sales become 'delivered' (stock was already deducted at
     *     creation, so stock_state = delivered and delivered_quantity = quantity).
     *   - 'returned' sales keep their status; they were delivered then returned.
     * Stock-source sales carry a stock_state; new_purchase sales never touched
     * inventory so they stay 'none'.
     */
    public function up(): void
    {
        // Completed → Delivered (stock already out for stock-source sales).
        DB::table('sales')->where('status', 'completed')->update([
            'status' => 'delivered',
            'delivered_quantity' => DB::raw('quantity'),
            'stock_state' => DB::raw("CASE WHEN source = 'stock' AND product_id IS NOT NULL THEN 'delivered' ELSE 'none' END"),
        ]);

        // Returned → keep status, record it was delivered then returned.
        DB::table('sales')->where('status', 'returned')->update([
            'delivered_quantity' => DB::raw('quantity'),
            'returned_quantity' => DB::raw('quantity'),
            'stock_state' => DB::raw("CASE WHEN source = 'stock' AND product_id IS NOT NULL THEN 'returned' ELSE 'none' END"),
        ]);
    }

    public function down(): void
    {
        // Restore the legacy 'completed' label; 'returned' was never renamed.
        DB::table('sales')->where('status', 'delivered')->update(['status' => 'completed']);
    }
};
