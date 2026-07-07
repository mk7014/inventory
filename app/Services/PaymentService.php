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

            $payment = Payment::create([
                'requisition_id' => $locked->id,
                'paid_to' => $locked->employee_id,
                'paid_by' => $admin->id,
                'amount' => $data['amount'],
                'payment_method' => $data['payment_method'],
                'payment_date' => $data['payment_date'],
                'reference' => $data['reference'] ?? null,
                'note' => $data['note'] ?? null,
            ]);

            // Credit the paid amount to the employee's balance (single source of
            // truth = money actually paid out). Mirrors StockService usage.
            $this->balanceService->credit(
                $locked->employee,
                (float) $data['amount'],
                $payment,
                $admin->id,
                'credit_payment',
                'Payment for requisition '.$locked->requisition_number,
            );

            $this->auditService->record('payment.created', $payment, null, $payment->toArray());
            $locked->employee->notify(new SystemNotification('Payment recorded', route('requisitions.show', $locked), 'payment'));

            return $payment;
        });
    }
}
