<?php

namespace App\Services;

use App\Models\Expense;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ExpenseService
{
    public function __construct(
        private BalanceService $balanceService,
        private AuditService $auditService,
    ) {
    }

    /**
     * Record a personal expense and deduct it from the user's balance in a single
     * atomic step. BalanceService::debit is the choke point — it row-locks the
     * user and rejects a debit that would push the balance below zero (surfaced
     * as a validation error on `amount`, prompting the user to request funds).
     */
    public function record(User $user, array $data, User $actor): Expense
    {
        return DB::transaction(function () use ($user, $data, $actor) {
            $expense = Expense::create([
                'user_id'      => $user->id,
                'category'     => $data['category'],
                'description'  => $data['description'],
                'amount'       => $data['amount'],
                'expense_date' => $data['expense_date'],
                'note'         => $data['note'] ?? null,
                'created_by'   => $actor->id,
            ]);

            $this->balanceService->debit(
                $user,
                (float) $expense->amount,
                $expense,
                $actor->id,
                'debit_expense',
                $expense->category.' — '.$expense->description,
            );

            $this->auditService->record('expense.created', $expense, null, $expense->toArray());

            return $expense;
        }, 3);
    }

    /**
     * Delete an expense and refund its amount back to the owner's balance.
     *
     * The row is re-fetched under lockForUpdate so two concurrent deletes cannot both
     * refund it: the second request blocks on the lock, then finds the row already
     * gone and returns without crediting. The delete IS the idempotency token.
     */
    public function remove(Expense $expense, User $actor): void
    {
        DB::transaction(function () use ($expense, $actor) {
            $locked = Expense::query()->whereKey($expense->id)->lockForUpdate()->first();

            if ($locked === null) {
                return; // Already refunded and deleted by a concurrent request.
            }

            $this->balanceService->credit(
                $locked->user,
                (float) $locked->amount,
                $locked,
                $actor->id,
                'credit_expense_refund',
                'Refund — '.$locked->category.' — '.$locked->description,
            );

            $this->auditService->record('expense.deleted', $locked, $locked->toArray(), null);

            $locked->delete();
        }, 3);
    }
}
