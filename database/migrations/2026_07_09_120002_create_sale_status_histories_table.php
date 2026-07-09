<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Immutable audit trail of every sale status transition and the inventory
     * movement it triggered (Book / Stock Out / Return / Release / none).
     */
    public function up(): void
    {
        Schema::create('sale_status_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_id')->constrained('sales')->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained();
            $table->string('previous_status')->nullable();
            $table->string('new_status');
            $table->string('movement_type')->nullable();   // book | stock_out | return | release | null
            $table->unsignedInteger('quantity')->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->timestamps();

            $table->index(['sale_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sale_status_histories');
    }
};
