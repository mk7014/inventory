<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\Requisition;
use App\Models\User;
use App\Notifications\SystemNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PaymentService
{
    public function __construct(private AuditService $auditService, private BalanceService $balanceService)
    {
    }

    public function create(Requisition $requisition, array $data, User $admin): Payment
    {
        return DB::transaction(function () use ($requisition, $data, $admin) {
            $locked = Requisition::query()->with('payments')->whereKey($requisition->id)->lockForUpdate()->firstOrFail();

            if ($locked->status !== 'approved') {
                throw ValidationException::withMessages(['requisition_id' => 'Only approved requisitions can receive payment.']);
            }

            $amount = round((float) $data['amount'], 2);

            // `payments` is loaded inside the row lock above, so this is the paid total
            // before the row we are about to insert.
            $paidBefore = (float) $locked->payments->sum('amount');
            $excess = round(($paidBefore + $amount) - (float) $locked->approved_amount, 2);

            $payment = Payment::create([
                'requisition_id' => $locked->id,
                'paid_to' => $locked->employee_id,
                'paid_by' => $admin->id,
                'amount' => $amount,
                'payment_method' => $data['payment_method'],
                'payment_date' => $data['payment_date'],
                'reference' => $data['reference'] ?? null,
                'note' => $data['note'] ?? null,
            ]);

            // Credit the full paid amount to the employee's balance (single source of
            // truth = money actually handed over). Paying past the approved amount is
            // allowed on purpose: the surplus just stays in the wallet as spendable
            // balance instead of being rejected.
            $this->balanceService->credit(
                $locked->employee,
                $amount,
                $payment,
                $admin->id,
                'credit_payment',
                'Payment for requisition '.$locked->requisition_number
                    .($excess > 0 ? ' (includes '.number_format($excess, 2).' above approved, kept as balance)' : ''),
            );

            $this->auditService->record('payment.created', $payment, null, $payment->toArray());
            $locked->employee->notify(new SystemNotification('Payment recorded', route('requisitions.show', $locked), 'payment'));

            return $payment;
        }, 3);
    }
}
