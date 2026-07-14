<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Gap-safe counters for human-facing document numbers (REQ-…, DP-…).
 *
 * These used to be derived from `count()+1` of today's rows, which reused a number
 * as soon as a row was hard-deleted (colliding with the UNIQUE index) and took a
 * table-wide gap lock to do it. One row per scope, bumped atomically, fixes both.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_sequences', function (Blueprint $table) {
            $table->string('scope', 64)->primary(); // e.g. REQ-20260714
            $table->unsignedBigInteger('last_value')->default(0);
            $table->timestamps();
        });

        // Seed each existing scope with the highest number already issued, so the
        // next document continues the series instead of colliding with it.
        $this->seedFrom('requisitions', 'requisition_number');
        $this->seedFrom('direct_purchases', 'purchase_number');
    }

    /** Parse PREFIX-YYYYMMDD-NNNN and record the max NNNN per PREFIX-YYYYMMDD scope. */
    private function seedFrom(string $table, string $column): void
    {
        $rows = DB::table($table)->pluck($column)->filter();
        $maxByScope = [];

        foreach ($rows as $number) {
            if (! preg_match('/^(.+)-(\d+)$/', (string) $number, $m)) {
                continue;
            }

            $scope = $m[1];
            $value = (int) $m[2];
            $maxByScope[$scope] = max($maxByScope[$scope] ?? 0, $value);
        }

        foreach ($maxByScope as $scope => $lastValue) {
            DB::table('document_sequences')->updateOrInsert(
                ['scope' => $scope],
                ['last_value' => $lastValue, 'created_at' => now(), 'updated_at' => now()],
            );
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('document_sequences');
    }
};
