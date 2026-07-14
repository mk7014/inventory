<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Defence-in-depth at the DB level. Every stock/money invariant in this app used to
 * live only in PHP, so anything bypassing the services (raw SQL, tinker, a future code
 * path) could corrupt stock silently. These constraints make a violation fail loudly.
 *
 * Verified against production data before writing: no rows violate any of them.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Stock can never go negative, and a reservation can never exceed what is on hand.
        // SQLite (the test connection) only accepts CHECK at table-creation time, so these
        // are applied on MySQL/MariaDB — where production runs — and skipped elsewhere.
        if (in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            DB::statement('ALTER TABLE products ADD CONSTRAINT chk_products_stock_non_negative CHECK (current_stock >= 0)');
            DB::statement('ALTER TABLE products ADD CONSTRAINT chk_products_booked_within_stock CHECK (booked_stock <= current_stock)');
        }

        // Idempotency keys: the same reference can never be applied twice for the same
        // movement type. Today double-application is prevented only by mutable status
        // flags (sales.stock_state, requisition_items.purchased_at) — this makes a
        // double-apply an error instead of a silent stock/balance corruption.
        Schema::table('stock_movements', function (Blueprint $table) {
            $table->unique(['reference_type', 'reference_id', 'type'], 'stock_movements_reference_unique');
        });

        Schema::table('balance_transactions', function (Blueprint $table) {
            $table->unique(['reference_type', 'reference_id', 'type'], 'balance_transactions_reference_unique');
        });

        // created_at was unindexed, so the old whereDate()->lockForUpdate() number
        // generator full-scanned under FOR UPDATE and gap-locked the whole table.
        Schema::table('requisitions', fn (Blueprint $table) => $table->index('created_at'));
        Schema::table('direct_purchases', fn (Blueprint $table) => $table->index('created_at'));
    }

    public function down(): void
    {
        if (in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            DB::statement('ALTER TABLE products DROP CONSTRAINT chk_products_stock_non_negative');
            DB::statement('ALTER TABLE products DROP CONSTRAINT chk_products_booked_within_stock');
        }

        Schema::table('stock_movements', fn (Blueprint $table) => $table->dropUnique('stock_movements_reference_unique'));
        Schema::table('balance_transactions', fn (Blueprint $table) => $table->dropUnique('balance_transactions_reference_unique'));
        Schema::table('requisitions', fn (Blueprint $table) => $table->dropIndex(['created_at']));
        Schema::table('direct_purchases', fn (Blueprint $table) => $table->dropIndex(['created_at']));
    }
};
