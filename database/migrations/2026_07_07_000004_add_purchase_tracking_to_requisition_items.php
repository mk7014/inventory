<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('requisition_items', function (Blueprint $table) {
            $table->timestamp('purchased_at')->nullable()->after('subtotal');
            $table->foreignId('purchased_by')->nullable()->after('purchased_at')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('requisition_items', function (Blueprint $table) {
            $table->dropForeign(['purchased_by']);
            $table->dropColumn(['purchased_at', 'purchased_by']);
        });
    }
};
