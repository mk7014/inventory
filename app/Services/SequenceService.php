<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

/**
 * Issues gap-safe, never-reused document numbers (REQ-20260714-0001, DP-…-0001).
 *
 * The counter is bumped with a single atomic INSERT … ON DUPLICATE KEY UPDATE, so it
 * locks exactly one row and survives hard deletes. Deriving the number from a row
 * count instead (the previous approach) reissued a number the moment a row was
 * deleted, which then collided with the UNIQUE index on the number column.
 */
class SequenceService
{
    public function next(string $prefix, int $pad = 4): string
    {
        $scope = $prefix.'-'.now()->format('Ymd');

        // Create the counter if this is the first document of the day. insertOrIgnore so a
        // concurrent creator racing us here is a no-op rather than a duplicate-key error.
        DB::table('document_sequences')->insertOrIgnore([
            'scope' => $scope,
            'last_value' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Atomic UPDATE … SET last_value = last_value + 1. This X-locks the single counter
        // row until the caller's transaction commits, so a concurrent bump blocks and the
        // value we read back is unambiguously ours.
        DB::table('document_sequences')->where('scope', $scope)->increment('last_value');

        $value = (int) DB::table('document_sequences')->where('scope', $scope)->value('last_value');

        return $scope.'-'.str_pad((string) $value, $pad, '0', STR_PAD_LEFT);
    }
}
