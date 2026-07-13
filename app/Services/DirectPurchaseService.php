<?php

namespace App\Services;

use App\Models\DirectPurchase;
use App\Models\Product;
use App\Models\User;
use App\Notifications\SystemNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

class DirectPurchaseService
{
    public function __construct(
        private StockService $stockService,
        private BalanceService $balanceService,
        private AuditService $auditService,
    ) {
    }

    /**
     * Draft a new direct purchase (status = pending). No stock or money moves
     * yet — that happens on approval. Line totals are recomputed server-side so
     * the client can never dictate the amounts.
     *
     * @param  User  $employee  the employee the goods/cost belong to
     * @param  User  $creator   the authenticated user submitting the form
     */
    public function create(array $data, User $employee, User $creator): DirectPurchase
    {
        return DB::transaction(function () use ($data, $employee, $creator) {
            $purchase = DirectPurchase::create([
                'purchase_number'  => $this->nextNumber(),
                'employee_id'      => $employee->id,
                'supplier_id'      => $data['supplier_id'] ?? null,
                'warehouse_id'     => $data['warehouse_id'] ?? null,
                'status'           => 'pending',
                'purchase_date'    => $data['purchase_date'],
                'invoice_number'   => $data['invoice_number'] ?? null,
                'reference_number' => $data['reference_number'] ?? null,
                'remarks'          => $data['remarks'] ?? null,
                'created_by'       => $creator->id,
            ]);

            $subtotal = $discountTotal = $taxTotal = $grandTotal = 0.0;

            foreach ($data['items'] as $item) {
                $product  = Product::findOrFail($item['product_id']);
                $quantity = (int) $item['quantity'];
                $price    = (float) $item['purchase_price'];
                $discount = (float) ($item['discount'] ?? 0);
                $tax      = (float) ($item['tax'] ?? 0);
                $lineBase = $quantity * $price;
                $lineTotal = $lineBase - $discount + $tax;

                $subtotal      += $lineBase;
                $discountTotal += $discount;
                $taxTotal      += $tax;
                $grandTotal    += $lineTotal;

                $purchase->items()->create([
                    'product_id'     => $product->id,
                    'product_name'   => $product->name,
                    'sku'            => $product->sku,
                    'quantity'       => $quantity,
                    'unit'           => $item['unit'] ?? null,
                    'purchase_price' => $price,
                    'discount'       => $discount,
                    'tax'            => $tax,
                    'line_total'     => $lineTotal,
                ]);
            }

            $purchase->update([
                'subtotal'       => $subtotal,
                'discount_total' => $discountTotal,
                'tax_total'      => $taxTotal,
                'grand_total'    => $grandTotal,
            ]);

            $this->auditService->record('direct_purchase.created', $purchase, null, $purchase->fresh()->toArray());

            $admins = User::query()->whereRelation('role', 'slug', 'admin')->where('status', 'active')->get();
            Notification::send($admins, new SystemNotification('New direct purchase submitted', route('direct-purchases.show', $purchase), 'direct_purchase'));

            return $purchase->load('items', 'supplier', 'warehouse', 'employee');
        });
    }

    /**
     * Approve a pending direct purchase: receive every line into stock using the
     * shared StockService and debit the cost from the employee's wallet.
     *
     * The debit is never blocked. If the wallet holds money the cost comes off it;
     * if it does not, the wallet goes negative and stays there — a negative balance
     * IS the company's standing debt for money the employee spent out of pocket. It
     * clears by itself the next time the admin credits the wallet, since the credit
     * absorbs the negative back towards zero.
     */
    public function approve(DirectPurchase $purchase, User $admin): DirectPurchase
    {
        return DB::transaction(function () use ($purchase, $admin) {
            $locked = DirectPurchase::query()->with('items', 'employee')->whereKey($purchase->id)->lockForUpdate()->firstOrFail();

            if ($locked->status !== 'pending') {
                throw ValidationException::withMessages(['status' => 'Only pending direct purchases can be approved.']);
            }

            foreach ($locked->items as $item) {
                $product = Product::findOrFail($item->product_id);
                $this->stockService->move($product, 'in_purchase', (int) $item->quantity, $item, $admin->id);
            }

            $this->balanceService->debit(
                $locked->employee,
                (float) $locked->grand_total,
                $locked,
                $admin->id,
                'debit_direct_purchase',
                'Direct purchase '.$locked->purchase_number,
                allowNegative: true,
            );

            $locked->update([
                'status'      => 'approved',
                'approved_at' => now(),
                'approved_by' => $admin->id,
            ]);

            $this->auditService->record('direct_purchase.approved', $locked, null, $locked->fresh()->toArray());
            $locked->employee->notify(new SystemNotification('Direct purchase approved', route('direct-purchases.show', $locked), 'direct_purchase'));

            return $locked->fresh(['items', 'employee', 'supplier', 'warehouse']);
        });
    }

    /**
     * Cancel a still-pending direct purchase. Approved purchases cannot be
     * cancelled here — stock and balance have already moved.
     */
    public function cancel(DirectPurchase $purchase, User $admin): DirectPurchase
    {
        return DB::transaction(function () use ($purchase, $admin) {
            $locked = DirectPurchase::query()->whereKey($purchase->id)->lockForUpdate()->firstOrFail();

            if ($locked->status !== 'pending') {
                throw ValidationException::withMessages(['status' => 'Only pending direct purchases can be cancelled.']);
            }

            $locked->update(['status' => 'cancelled']);
            $this->auditService->record('direct_purchase.cancelled', $locked, null, $locked->fresh()->toArray());
            $locked->employee->notify(new SystemNotification('Direct purchase cancelled', route('direct-purchases.show', $locked), 'direct_purchase'));

            return $locked->fresh();
        });
    }

    private function nextNumber(): string
    {
        $date  = now()->format('Ymd');
        $count = DirectPurchase::query()->whereDate('created_at', today())->lockForUpdate()->count() + 1;

        return 'DP-'.$date.'-'.str_pad((string) $count, 4, '0', STR_PAD_LEFT);
    }
}
