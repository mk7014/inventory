<?php

namespace App\Services;

use App\Models\DirectPurchase;
use App\Models\DirectPurchasePayment;
use App\Models\User;
use App\Notifications\SystemNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DirectPurchasePaymentService
{
    public function __construct(private AuditService $auditService)
    {
    }

    /**
     * Record a reimbursement against a "due" direct purchase — the company
     * settling money the employee paid out of pocket. Supports partial and full
     * payments; the running paid_amount / payment_status are kept in sync.
     *
     * This does NOT touch the employee's advance wallet: a due settlement returns
     * money the employee already spent, it does not grant new spendable advance.
     */
    public function create(DirectPurchase $purchase, array $data, User $admin): DirectPurchasePayment
    {
        return DB::transaction(function () use ($purchase, $data, $admin) {
            $locked = DirectPurchase::query()->whereKey($purchase->id)->lockForUpdate()->firstOrFail();

            if (! $locked->isDue()) {
                throw ValidationException::withMessages(['direct_purchase_id' => 'Only due (out-of-pocket) purchases can receive a payment.']);
            }

            if ($locked->status !== 'approved') {
                throw ValidationException::withMessages(['direct_purchase_id' => 'Only approved direct purchases can be settled.']);
            }

            $amount    = round((float) $data['amount'], 2);
            $remaining = $locked->dueAmount();

            if ($amount <= 0) {
                throw ValidationException::withMessages(['amount' => 'Payment amount must be greater than zero.']);
            }

            if ($amount > $remaining + 0.001) {
                throw ValidationException::withMessages(['amount' => 'Payment exceeds the outstanding due of ৳'.number_format($remaining, 2).'.']);
            }

            $payment = DirectPurchasePayment::create([
                'direct_purchase_id' => $locked->id,
                'paid_to'            => $locked->employee_id,
                'paid_by'            => $admin->id,
                'amount'             => $amount,
                'payment_method'     => $data['payment_method'],
                'payment_date'       => $data['payment_date'],
                'reference'          => $data['reference'] ?? null,
                'note'               => $data['note'] ?? null,
            ]);

            $newPaid = round((float) $locked->paid_amount + $amount, 2);
            $locked->update([
                'paid_amount'    => $newPaid,
                'payment_status' => $newPaid + 0.001 >= (float) $locked->grand_total ? 'paid' : 'partial',
            ]);

            $this->auditService->record('direct_purchase.payment', $payment, null, $payment->toArray());
            $locked->employee->notify(new SystemNotification('Direct purchase payment recorded', route('direct-purchases.show', $locked), 'direct_purchase'));

            return $payment;
        });
    }
}
