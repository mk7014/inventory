<?php

namespace App\Services;

use App\Models\BalanceTransaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use LogicException;

class BalanceService
{
    /**
     * lockForUpdate only holds inside a transaction — under autocommit the lock is
     * released as soon as the SELECT returns, so two callers could both read the same
     * balance and one write would be lost. Fail loudly instead.
     */
    private function assertInTransaction(): void
    {
        if (DB::transactionLevel() === 0) {
            throw new LogicException('BalanceService must run inside a DB transaction; lockForUpdate() does nothing without one.');
        }
    }

    /**
     * Credit an amount to a user's balance and write an immutable ledger row.
     * Mirrors StockService::move — single choke point for every balance change,
     * row-locked for concurrency safety. Caller is responsible for the surrounding
     * DB::transaction() (all current callers already run inside one).
     */
    public function credit(User $user, float $amount, ?Model $reference = null, ?int $userId = null, string $type = 'credit', ?string $note = null): BalanceTransaction
    {
        if ($amount <= 0) {
            throw ValidationException::withMessages(['amount' => 'Credit amount must be greater than zero.']);
        }

        $this->assertInTransaction();

        $lockedUser = User::query()->whereKey($user->id)->lockForUpdate()->firstOrFail();
        $newBalance = round((float) $lockedUser->balance + $amount, 2);

        $lockedUser->forceFill(['balance' => $newBalance])->save();

        return BalanceTransaction::create([
            'user_id' => $lockedUser->id,
            'type' => $type,
            'amount' => $amount,
            'balance_after' => $newBalance,
            'reference_type' => $reference ? $reference::class : null,
            'reference_id' => $reference?->getKey(),
            'note' => $note,
            'created_by' => $userId,
        ]);
    }

    /**
     * Debit an amount from a user's balance and write an immutable ledger row
     * (stored as a negative amount so SUM(amount) still equals the balance).
     * Rejects a debit that would drive the balance below zero. Caller supplies
     * the surrounding DB::transaction().
     *
     * $allowNegative lets the balance go below zero, which is only meaningful for
     * money the employee paid out of their own pocket (a "due" direct purchase):
     * there the negative balance IS the company's debt to them, cleared when the
     * settlement payment credits it back.
     */
    public function debit(User $user, float $amount, ?Model $reference = null, ?int $userId = null, string $type = 'debit', ?string $note = null, bool $allowNegative = false): BalanceTransaction
    {
        if ($amount <= 0) {
            throw ValidationException::withMessages(['amount' => 'Debit amount must be greater than zero.']);
        }

        $this->assertInTransaction();

        $lockedUser = User::query()->whereKey($user->id)->lockForUpdate()->firstOrFail();

        // Round to the stored scale before comparing: raw float subtraction can land on
        // -1.4e-14 for a balance that is exactly zero, which would spuriously reject a
        // debit that in fact settles to 0.00.
        $newBalance = round((float) $lockedUser->balance - $amount, 2);

        if ($newBalance < 0 && ! $allowNegative) {
            throw ValidationException::withMessages(['amount' => 'Insufficient balance to record this purchase.']);
        }

        $lockedUser->forceFill(['balance' => $newBalance])->save();

        return BalanceTransaction::create([
            'user_id' => $lockedUser->id,
            'type' => $type,
            'amount' => -$amount,
            'balance_after' => $newBalance,
            'reference_type' => $reference ? $reference::class : null,
            'reference_id' => $reference?->getKey(),
            'note' => $note,
            'created_by' => $userId,
        ]);
    }
}
