<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Requisition;
use App\Models\User;
use App\Notifications\SystemNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

class RequisitionService
{
    public function __construct(private AuditService $auditService)
    {
    }

    public function create(array $data, User $employee): Requisition
    {
        return DB::transaction(function () use ($data, $employee) {
            $requisition = Requisition::create([
                'requisition_number' => $this->nextNumber(),
                'employee_id' => $employee->id,
                'total_amount' => 0,
                'status' => 'pending',
                'requested_at' => now(),
            ]);

            $total = 0;
            foreach ($data['items'] as $item) {
                if (($item['item_type'] ?? 'product') === 'cost') {
                    $subtotal = (float) $item['amount'];
                    $total += $subtotal;

                    $requisition->items()->create([
                        'item_type'   => 'cost',
                        'description' => $item['description'],
                        'subtotal'    => $subtotal,
                    ]);
                } else {
                    $product = Product::findOrFail($item['product_id']);
                    $subtotal = (int) $item['quantity'] * (float) $item['purchase_price'];
                    $total += $subtotal;

                    $requisition->items()->create([
                        'item_type'        => 'product',
                        'daraz_account_id' => $item['daraz_account_id'],
                        'product_id'       => $product->id,
                        'product_name'     => $product->name,
                        'order_id_daraz'   => $item['order_id_daraz'] ?? null,
                        'quantity'         => $item['quantity'],
                        'purchase_price'   => $item['purchase_price'],
                        'subtotal'         => $subtotal,
                    ]);
                }
            }

            $requisition->update(['total_amount' => $total]);
            $this->auditService->record('requisition.created', $requisition, null, $requisition->fresh()->toArray());

            $admins = User::query()->whereRelation('role', 'slug', 'admin')->where('status', 'active')->get();
            Notification::send($admins, new SystemNotification('New requisition submitted', route('requisitions.show', $requisition), 'requisition'));

            return $requisition->load('items.product', 'items.account');
        });
    }

    public function review(Requisition $requisition, string $status, ?float $approvedAmount, ?string $note, User $admin): Requisition
    {
        return DB::transaction(function () use ($requisition, $status, $approvedAmount, $note, $admin) {
            $locked = Requisition::query()->whereKey($requisition->id)->lockForUpdate()->firstOrFail();

            if (! in_array($locked->status, ['pending', 'hold'], true)) {
                throw ValidationException::withMessages(['status' => 'Only pending or hold requisitions can be reviewed.']);
            }

            if ($status === 'rejected' && blank($note)) {
                throw ValidationException::withMessages(['admin_note' => 'Reject reason is required.']);
            }

            $old = $locked->only(['status', 'approved_amount', 'admin_note']);
            $locked->update([
                'status' => $status,
                'approved_amount' => $status === 'approved' ? ($approvedAmount ?: $locked->total_amount) : null,
                'admin_note' => $note,
                'reviewed_at' => now(),
                'reviewed_by' => $admin->id,
            ]);

            // Note: the employee's balance is credited when a payment is actually
            // recorded (see PaymentService), not on approval — so it reflects the
            // real money paid out.
            $this->auditService->record('requisition.reviewed', $locked, $old, $locked->fresh()->only(['status', 'approved_amount', 'admin_note']), $note);
            $locked->employee->notify(new SystemNotification('Requisition '.$status, route('requisitions.show', $locked), 'requisition'));

            return $locked->fresh(['items.product', 'items.account', 'employee']);
        });
    }

    private function nextNumber(): string
    {
        $date = now()->format('Ymd');
        $count = Requisition::query()->whereDate('created_at', today())->lockForUpdate()->count() + 1;

        return 'REQ-'.$date.'-'.str_pad((string) $count, 4, '0', STR_PAD_LEFT);
    }
}
