<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\BalanceTransaction;
use App\Models\DarazAccount;
use App\Models\DirectPurchase;
use App\Models\DirectPurchaseItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductReturn;
use App\Models\Requisition;
use App\Models\RequisitionExpense;
use App\Models\RequisitionItem;
use App\Models\Sale;
use App\Models\SaleStatusHistory;
use App\Models\StockAdjustment;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\Warehouse;
use Illuminate\Support\Facades\DB;

/**
 * Admin-only "force" hard delete used to clean up dummy/test records. It removes a
 * record together with every child row and every loose polymorphic ledger entry.
 *
 * Crucially it FIRST reverses whatever the record did to stock and balances, then
 * purges the ledger. Purging alone would delete the evidence while leaving the
 * effect — the employee's balance would still carry a debit whose ledger row no
 * longer exists, permanently breaking `users.balance == SUM(balance_transactions.amount)`.
 *
 * The reversal entries deliberately reference the model being deleted, so purgeLedger
 * sweeps up both the original entry and its reversal: the net effect on stock/balance
 * is undone, the ledger stays consistent, and nothing is left pointing at a dead row.
 */
class DeletionService
{
    public function __construct(
        private StockService $stockService,
        private BalanceService $balanceService,
    ) {
    }

    /** Purge stock/balance/audit ledger rows that point at the given model ids. */
    private function purgeLedger(string $class, array $ids): void
    {
        if (empty($ids)) {
            return;
        }

        StockMovement::where('reference_type', $class)->whereIn('reference_id', $ids)->delete();
        BalanceTransaction::where('reference_type', $class)->whereIn('reference_id', $ids)->delete();
        AuditLog::where('auditable_type', $class)->whereIn('auditable_id', $ids)->delete();
    }

    public function deleteSale(Sale $sale): void
    {
        DB::transaction(function () use ($sale) {
            $locked = Sale::query()->with('product')->whereKey($sale->id)->lockForUpdate()->firstOrFail();

            $this->reverseSaleStock($locked);

            $returnIds = $locked->returns()->pluck('id')->all();

            $this->purgeLedger(ProductReturn::class, $returnIds);
            $this->purgeLedger(Sale::class, [$locked->id]);

            // returns + sale_status_histories cascade via their FK on delete.
            $locked->delete();
        }, 3);
    }

    /**
     * Undo whatever the sale currently holds against the product.
     *   booked    — reservation still held, never shipped  → release it
     *   delivered — stock was decremented on out_sale       → add it back
     *   returned  — decremented then restored, nets to zero → nothing to undo
     *   none / released — never touched stock               → nothing to undo
     */
    private function reverseSaleStock(Sale $sale): void
    {
        if (! $sale->affectsStock() || $sale->product === null) {
            return;
        }

        if ($sale->stock_state === 'booked') {
            $quantity = (int) ($sale->booked_quantity ?: $sale->quantity);

            if ($quantity > 0) {
                $this->stockService->move($sale->product, 'release', $quantity, $sale);
            }
        }

        if ($sale->stock_state === 'delivered') {
            $quantity = (int) ($sale->delivered_quantity ?: $sale->quantity);

            if ($quantity > 0) {
                $this->stockService->move($sale->product, 'adjust_in', $quantity, $sale);
            }
        }
    }

    public function deleteRequisition(Requisition $requisition): void
    {
        DB::transaction(function () use ($requisition) {
            $locked = Requisition::query()
                ->with(['items.product', 'payments', 'employee'])
                ->whereKey($requisition->id)->lockForUpdate()->firstOrFail();

            $employee = $locked->employee;

            // A purchased line put goods into stock and took the cost off the wallet.
            foreach ($locked->items->sortBy('product_id') as $item) {
                if (! $item->isPurchased()) {
                    continue;
                }

                if ($item->product && (int) $item->quantity > 0) {
                    $this->stockService->move($item->product, 'adjust_out', (int) $item->quantity, $item);
                }

                if ((float) $item->subtotal > 0) {
                    $this->balanceService->credit(
                        $employee, (float) $item->subtotal, $item, null,
                        'credit_reversal', 'Reversal — deleted requisition '.$locked->requisition_number,
                    );
                }
            }

            // A payment credited the wallet; deleting it takes that money back. The wallet
            // may go negative — that negative is the real debt and is the honest outcome.
            foreach ($locked->payments as $payment) {
                $this->balanceService->debit(
                    $employee, (float) $payment->amount, $payment, null,
                    'debit_reversal', 'Reversal — deleted requisition '.$locked->requisition_number,
                    allowNegative: true,
                );
            }

            $itemIds    = $locked->items->pluck('id')->all();
            $paymentIds = $locked->payments->pluck('id')->all();
            $expenseIds = $locked->expenses()->pluck('id')->all();

            $this->purgeLedger(RequisitionItem::class, $itemIds);
            $this->purgeLedger(Payment::class, $paymentIds);
            $this->purgeLedger(RequisitionExpense::class, $expenseIds);
            $this->purgeLedger(Requisition::class, [$locked->id]);

            // items, payments, expenses cascade via their FK on delete.
            $locked->delete();
        }, 3);
    }

    public function deleteDirectPurchase(DirectPurchase $purchase): void
    {
        DB::transaction(function () use ($purchase) {
            $locked = DirectPurchase::query()
                ->with(['items.product', 'employee'])
                ->whereKey($purchase->id)->lockForUpdate()->firstOrFail();

            // Only an approved purchase ever moved stock or money.
            if ($locked->status === 'approved') {
                foreach ($locked->items->sortBy('product_id') as $item) {
                    if ($item->product && (int) $item->quantity > 0) {
                        $this->stockService->move($item->product, 'adjust_out', (int) $item->quantity, $item);
                    }
                }

                if ((float) $locked->grand_total > 0) {
                    $this->balanceService->credit(
                        $locked->employee, (float) $locked->grand_total, $locked, null,
                        'credit_reversal', 'Reversal — deleted direct purchase '.$locked->purchase_number,
                    );
                }
            }

            $itemIds = $locked->items->pluck('id')->all();

            $this->purgeLedger(DirectPurchaseItem::class, $itemIds);
            $this->purgeLedger(DirectPurchase::class, [$locked->id]);

            // items cascade via their FK on delete.
            $locked->delete();
        }, 3);
    }

    public function deleteProduct(Product $product): void
    {
        DB::transaction(function () use ($product) {
            // Detach loose references (all nullable FKs) so the rows survive.
            Sale::where('product_id', $product->id)->update(['product_id' => null]);
            ProductReturn::where('product_id', $product->id)->update(['product_id' => null]);
            SaleStatusHistory::where('product_id', $product->id)->update(['product_id' => null]);
            StockMovement::where('product_id', $product->id)->update(['product_id' => null]);
            StockAdjustment::where('product_id', $product->id)->update(['product_id' => null]);
            RequisitionItem::where('product_id', $product->id)->update(['product_id' => null]);

            // Direct-purchase line items require a product, so they must go.
            DirectPurchaseItem::where('product_id', $product->id)->delete();

            $this->purgeLedger(Product::class, [$product->id]);
            $product->delete();
        }, 3);
    }

    public function deleteDarazAccount(DarazAccount $account): void
    {
        DB::transaction(function () use ($account) {
            // Sales/returns require an account (non-nullable FK); cascade them.
            $account->sales()->get()->each(fn (Sale $sale) => $this->deleteSale($sale));

            // Requisition lines only reference it loosely.
            RequisitionItem::where('daraz_account_id', $account->id)->update(['daraz_account_id' => null]);

            $this->purgeLedger(DarazAccount::class, [$account->id]);
            $account->forceDelete(); // bypass soft delete — this is a real purge.
        }, 3);
    }

    public function deleteSupplier(Supplier $supplier): void
    {
        DB::transaction(function () use ($supplier) {
            // direct_purchases.supplier_id is nullOnDelete — purchases survive.
            $this->purgeLedger(Supplier::class, [$supplier->id]);
            $supplier->delete();
        }, 3);
    }

    public function deleteWarehouse(Warehouse $warehouse): void
    {
        DB::transaction(function () use ($warehouse) {
            // direct_purchases.warehouse_id is nullOnDelete — purchases survive.
            $this->purgeLedger(Warehouse::class, [$warehouse->id]);
            $warehouse->delete();
        }, 3);
    }
}
