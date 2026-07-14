<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Voiding a user is the reversible alternative to deleting them: the records stay
 * on disk for audit, but every financial calculation ignores them — their sales
 * leave revenue, their purchases leave the cost basis, their expenses leave opex,
 * and their wallet leaves the fund/spend totals.
 *
 * Deliberately separate from `status`: an *inactive* user simply cannot log in but
 * still counts in the books. A *voided* user counts nowhere.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('voided_at')->nullable()->after('status')->index();
            $table->foreignId('voided_by')->nullable()->after('voided_at')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('voided_by');
            $table->dropColumn('voided_at');
        });
    }
};
