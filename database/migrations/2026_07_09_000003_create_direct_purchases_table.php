<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('direct_purchases', function (Blueprint $table) {
            $table->id();
            $table->string('purchase_number')->unique();
            $table->foreignId('employee_id')->constrained('users');
            $table->foreignId('supplier_id')->nullable()->constrained('suppliers')->nullOnDelete();
            $table->foreignId('warehouse_id')->nullable()->constrained('warehouses')->nullOnDelete();

            // 'advance' — cost is debited from the employee's advance wallet on approval.
            // 'due'     — the employee paid out of pocket; the company owes them (settled by payments).
            $table->string('payment_type')->default('advance');

            // Lifecycle: pending -> approved (stock in) | cancelled.
            $table->string('status')->default('pending')->index();

            // Money state: 'paid' (advance, or a fully-settled due), 'due', 'partial'.
            $table->string('payment_status')->default('due')->index();

            $table->date('purchase_date')->index();
            $table->string('invoice_number')->nullable();
            $table->string('reference_number')->nullable();
            $table->text('remarks')->nullable();

            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('discount_total', 12, 2)->default(0);
            $table->decimal('tax_total', 12, 2)->default(0);
            $table->decimal('grand_total', 12, 2)->default(0);
            $table->decimal('paid_amount', 12, 2)->default(0);

            $table->timestamp('approved_at')->nullable()->index();
            $table->foreignId('approved_by')->nullable()->constrained('users');
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->timestamps();

            $table->index(['employee_id', 'status']);
            $table->index(['status', 'purchase_date']);
            $table->index(['supplier_id', 'purchase_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('direct_purchases');
    }
};
