<?php

namespace App\Services;

use App\Models\Product;
use App\Models\RequisitionItem;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PurchaseService
{
    public function __construct(
        private StockService $stockService,
        private BalanceService $balanceService,
        private AuditService $auditService,
    ) {
    }

    /**
     * Record that an approved requisition's product item was actually purchased:
     * the product stock is increased and the item cost is deducted from the
     * requesting employee's balance. Runs once per item (guarded by purchased_at).
     */
    public function purchaseItem(RequisitionItem $item, User $user): RequisitionItem
    {
        return DB::transaction(function () use ($item, $user) {
            $locked = RequisitionItem::query()->with('requisition')->whereKey($item->id)->lockForUpdate()->firstOrFail();

            if (! $locked->isProductItem() || $locked->product_id === null) {
                throw ValidationException::withMessages(['item' => 'Only product items can be purchased.']);
            }

            if ($locked->isPurchased()) {
                throw ValidationException::withMessages(['item' => 'This item has already been purchased.']);
            }

            $requisition = $locked->requisition;

            if ($requisition->status !== 'approved') {
                throw ValidationException::withMessages(['item' => 'Only items from approved requisitions can be purchased.']);
            }

            $product = Product::findOrFail($locked->product_id);

            // Increase stock now that the goods are actually bought.
            $this->stockService->move($product, 'in_purchase', (int) $locked->quantity, $locked, $user->id);

            // Deduct the purchase cost from the employee's balance.
            $this->balanceService->debit(
                $requisition->employee,
                (float) $locked->subtotal,
                $locked,
                $user->id,
                'debit_purchase',
                'Purchased '.$locked->product_name.' x'.$locked->quantity.' ('.$requisition->requisition_number.')',
            );

            $locked->update([
                'purchased_at' => now(),
                'purchased_by' => $user->id,
            ]);

            $this->auditService->record('purchase.recorded', $locked, null, $locked->fresh()->toArray());

            return $locked->fresh();
        });
    }
}
