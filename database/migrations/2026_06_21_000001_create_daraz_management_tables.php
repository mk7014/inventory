<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('daraz_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('account_name');
            $table->string('shop_name');
            $table->string('status')->default('active')->index();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('sku')->nullable()->unique();
            $table->decimal('default_purchase_price', 12, 2)->nullable();
            $table->integer('current_stock')->default(0);
            $table->timestamps();

            $table->index(['name']);
            $table->index(['current_stock']);
        });

        Schema::create('requisitions', function (Blueprint $table) {
            $table->id();
            $table->string('requisition_number')->unique();
            $table->foreignId('employee_id')->constrained('users');
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->decimal('approved_amount', 12, 2)->nullable();
            $table->string('status')->default('pending')->index();
            $table->text('admin_note')->nullable();
            $table->timestamp('requested_at')->useCurrent()->index();
            $table->timestamp('reviewed_at')->nullable()->index();
            $table->foreignId('reviewed_by')->nullable()->constrained('users');
            $table->timestamps();

            $table->index(['employee_id', 'status']);
            $table->index(['status', 'requested_at']);
        });

        Schema::create('requisition_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('requisition_id')->constrained()->cascadeOnDelete();
            $table->foreignId('daraz_account_id')->constrained();
            $table->foreignId('product_id')->constrained();
            $table->string('product_name');
            $table->string('order_id_daraz')->nullable();
            $table->unsignedInteger('quantity');
            $table->decimal('purchase_price', 12, 2);
            $table->decimal('subtotal', 12, 2);
            $table->timestamps();

            $table->index(['daraz_account_id', 'product_id']);
            $table->index(['requisition_id', 'daraz_account_id']);
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('requisition_id')->constrained()->cascadeOnDelete();
            $table->foreignId('paid_to')->constrained('users');
            $table->foreignId('paid_by')->constrained('users');
            $table->decimal('amount', 12, 2);
            $table->string('payment_method');
            $table->timestamp('payment_date')->index();
            $table->string('reference')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();

            $table->index(['paid_to', 'payment_date']);
            $table->index(['paid_by', 'payment_date']);
        });

        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('daraz_account_id')->constrained();
            $table->foreignId('product_id')->nullable()->constrained();
            $table->string('product_name');
            $table->decimal('selling_price', 12, 2);
            $table->unsignedInteger('quantity');
            $table->string('source');
            $table->string('status')->default('completed')->index();
            $table->date('sold_date')->index();
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->timestamps();

            $table->index(['daraz_account_id', 'sold_date']);
            $table->index(['product_id', 'status']);
        });

        Schema::create('returns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_id')->constrained('sales')->cascadeOnDelete();
            $table->foreignId('daraz_account_id')->constrained();
            $table->foreignId('product_id')->nullable()->constrained();
            $table->string('product_name');
            $table->unsignedInteger('quantity');
            $table->string('condition');
            $table->date('return_date')->index();
            $table->text('reason')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->timestamps();

            $table->index(['condition', 'return_date']);
            $table->index(['daraz_account_id', 'return_date']);
        });

        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->nullable()->constrained();
            $table->string('product_name');
            $table->string('type')->index();
            $table->integer('quantity');
            $table->string('reference_type');
            $table->unsignedBigInteger('reference_id');
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->timestamps();

            $table->index(['product_id', 'created_at']);
            $table->index(['reference_type', 'reference_id']);
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users');
            $table->string('action')->index();
            $table->string('auditable_type');
            $table->unsignedBigInteger('auditable_id');
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();

            $table->index(['auditable_type', 'auditable_id']);
            $table->index(['created_at']);
        });

        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->morphs('notifiable');
            $table->text('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('stock_movements');
        Schema::dropIfExists('returns');
        Schema::dropIfExists('sales');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('requisition_items');
        Schema::dropIfExists('requisitions');
        Schema::dropIfExists('products');
        Schema::dropIfExists('daraz_accounts');
    }
};
