<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Adds the inventory + lifecycle bookkeeping required by the sales status
     * flow. `products.booked_stock` tracks reserved (shipped, not yet delivered)
     * quantity so available stock = current_stock − booked_stock. The per-sale
     * columns record how a sale has moved through the lifecycle and guard against
     * duplicate stock movements.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->unsignedInteger('booked_stock')->default(0)->after('current_stock');
        });

        Schema::table('sales', function (Blueprint $table) {
            // Inventory state of THIS sale: none | booked | delivered | returned | released.
            $table->string('stock_state')->default('none')->after('status')->index();
            $table->unsignedInteger('booked_quantity')->default(0)->after('stock_state');
            $table->unsignedInteger('delivered_quantity')->default(0)->after('booked_quantity');
            $table->unsignedInteger('returned_quantity')->default(0)->after('delivered_quantity');
            $table->timestamp('status_updated_at')->nullable()->after('returned_quantity');
            $table->foreignId('status_updated_by')->nullable()->after('status_updated_at')->constrained('users');
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropConstrainedForeignId('status_updated_by');
            $table->dropColumn(['stock_state', 'booked_quantity', 'delivered_quantity', 'returned_quantity', 'status_updated_at']);
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('booked_stock');
        });
    }
};
