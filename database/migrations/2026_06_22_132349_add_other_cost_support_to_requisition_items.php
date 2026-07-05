<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('requisition_items', function (Blueprint $table) {
            $table->string('item_type')->default('product')->after('requisition_id');
            $table->string('description')->nullable()->after('item_type');

            $table->dropForeign(['daraz_account_id']);
            $table->dropForeign(['product_id']);

            $table->unsignedBigInteger('daraz_account_id')->nullable()->change();
            $table->unsignedBigInteger('product_id')->nullable()->change();
            $table->unsignedInteger('quantity')->nullable()->change();
            $table->decimal('purchase_price', 12, 2)->nullable()->change();

            $table->foreign('daraz_account_id')->references('id')->on('daraz_accounts')->nullOnDelete();
            $table->foreign('product_id')->references('id')->on('products')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('requisition_items', function (Blueprint $table) {
            $table->dropForeign(['daraz_account_id']);
            $table->dropForeign(['product_id']);

            $table->dropColumn(['item_type', 'description']);

            $table->unsignedBigInteger('daraz_account_id')->nullable(false)->change();
            $table->unsignedBigInteger('product_id')->nullable(false)->change();
            $table->unsignedInteger('quantity')->nullable(false)->change();
            $table->decimal('purchase_price', 12, 2)->nullable(false)->change();

            $table->foreign('daraz_account_id')->references('id')->on('daraz_accounts');
            $table->foreign('product_id')->references('id')->on('products');
        });
    }
};
