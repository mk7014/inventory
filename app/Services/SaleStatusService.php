<?php

namespace App\Services;

use App\Enums\SaleStatus;
use App\Models\Sale;
use App\Models\SaleStatusHistory;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SaleStatusService
{
    public function __construct(private StockService $stockService, private AuditService $auditService)
    {
    }

    /**
     * Move a sale to a new lifecycle status, applying the matching inventory
     * movement exactly once. Everything runs inside a single transaction with a
     * row lock on the sale so concurrent clicks cannot double-apply stock.
     *
     * Inventory effects (only for stock-source sales with a product):
     *   → Shipped   : reserve the quantity (Book)
     *   → Delivered : ship the reserved quantity out of stock (Stock Out)
     *   → Returned  : add the quantity back to stock (Return)
     *   → Cancelled : release any reservation back to available (Release)
     */
    public function transition(Sale $sale, string $targetValue, User $user): Sale
    {
        $target = SaleStatus::tryFrom($targetValue);

        if ($target === null) {
            throw ValidationException::withMessages(['status' => 'Unknown sale status.']);
        }

        return DB::transaction(function () use ($sale, $target, $user) {
            $locked = Sale::query()->with('product')->whereKey($sale->id)->lockForUpdate()->firstOrFail();
            $current = SaleStatus::from($locked->status);

            if ($current === $target) {
                throw ValidationException::withMessages(['status' => 'The sale is already '.$target->label().'.']);
            }

            if (! $current->canTransitionTo($target)) {
                throw ValidationException::withMessages([
                    'status' => "Cannot change status from {$current->label()} to {$target->label()}.",
                ]);
            }

            $movementType = $this->applyStockMovement($locked, $target, $user);

            $locked->fill([
                'status' => $target->value,
                'status_updated_at' => now(),
                'status_updated_by' => $user->id,
            ]);
            $locked->save();

            SaleStatusHistory::create([
                'sale_id' => $locked->id,
                'product_id' => $locked->product_id,
                'previous_status' => $current->value,
                'new_status' => $target->value,
                'movement_type' => $movementType,
                'quantity' => $locked->quantity,
                'created_by' => $user->id,
            ]);

            $this->auditService->record(
                'sale.status_updated',
                $locked,
                ['status' => $current->value],
                ['status' => $target->value],
                "Status changed from {$current->label()} to {$target->label()}."
            );

            return $locked;
        });
    }

    /**
     * Perform the inventory side effect for entering $target and update the
     * sale's stock bookkeeping columns. Returns the movement type recorded in
     * the status history (book | stock_out | return | release | null). Guards on
     * stock_state keep every movement idempotent.
     */
    private function applyStockMovement(Sale $sale, SaleStatus $target, User $user): ?string
    {
        if (! $sale->affectsStock()) {
            // new_purchase (or product-less) sales carry the status only.
            if ($target === SaleStatus::Delivered) {
                $sale->delivered_quantity = $sale->quantity;
            } elseif ($target === SaleStatus::Returned) {
                $sale->returned_quantity = $sale->quantity;
            }

            return null;
        }

        $product = $sale->product;

        switch ($target) {
            case SaleStatus::Shipped:
                if ($sale->stock_state === 'booked') {
                    return null; // already reserved
                }
                $this->stockService->move($product, 'book', $sale->quantity, $sale, $user->id);
                $sale->stock_state = 'booked';
                $sale->booked_quantity = $sale->quantity;

                return 'book';

            case SaleStatus::Delivered:
                if ($sale->stock_state === 'delivered') {
                    return null; // already stocked out
                }
                $this->stockService->move($product, 'out_sale', $sale->quantity, $sale, $user->id);
                $sale->stock_state = 'delivered';
                $sale->booked_quantity = 0;
                $sale->delivered_quantity = $sale->quantity;

                return 'stock_out';

            case SaleStatus::Returned:
                if ($sale->stock_state === 'returned') {
                    return null; // already restored
                }
                $this->stockService->move($product, 'in_return', $sale->quantity, $sale, $user->id);
                $sale->stock_state = 'returned';
                $sale->returned_quantity = $sale->quantity;

                return 'return';

            case SaleStatus::Cancelled:
                if ($sale->stock_state === 'booked') {
                    $this->stockService->move($product, 'release', $sale->booked_quantity ?: $sale->quantity, $sale, $user->id);
                    $sale->stock_state = 'released';
                    $sale->booked_quantity = 0;

                    return 'release';
                }

                return null; // nothing was reserved

            default:
                return null; // pending / confirmed / send_to_courier carry no stock effect
        }
    }
}
