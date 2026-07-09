<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('direct_purchase_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('direct_purchase_id')->constrained()->cascadeOnDelete();
            $table->foreignId('paid_to')->constrained('users'); // employee being reimbursed
            $table->foreignId('paid_by')->constrained('users'); // admin recording the payment
            $table->decimal('amount', 12, 2);
            $table->string('payment_method');
            $table->timestamp('payment_date')->index();
            $table->string('reference')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();

            $table->index(['paid_to', 'payment_date']);
            $table->index(['direct_purchase_id', 'payment_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('direct_purchase_payments');
    }
};
