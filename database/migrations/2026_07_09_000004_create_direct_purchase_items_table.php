<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('direct_purchase_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('direct_purchase_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained();
            $table->string('product_name');
            $table->string('sku')->nullable();
            $table->unsignedInteger('quantity');
            $table->string('unit')->nullable();
            $table->decimal('purchase_price', 12, 2);
            $table->decimal('discount', 12, 2)->default(0);
            $table->decimal('tax', 12, 2)->default(0);
            $table->decimal('line_total', 12, 2);
            $table->timestamps();

            $table->index(['direct_purchase_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('direct_purchase_items');
    }
};
