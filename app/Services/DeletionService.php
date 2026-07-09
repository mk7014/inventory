<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\BalanceTransaction;
use App\Models\DarazAccount;
use App\Models\DirectPurchase;
use App\Models\DirectPurchaseItem;
use App\Models\DirectPurchasePayment;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductReturn;
use App\Models\Requisition;
use App\Models\RequisitionExpense;
use App\Models\RequisitionItem;
use App\Models\Sale;
use App\Models\SaleStatusHistory;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\Warehouse;
use Illuminate\Support\Facades\DB;

/**
 * Admin-only "force" hard delete used to clean up dummy/test records. It removes
 * a record together with every child row and every loose polymorphic ledger
 * entry (stock movements, balance transactions, audit logs) so nothing is left
 * orphaned. It deliberately does NOT reverse stock/balance side effects — it is
 * a data-cleanup tool, not a business reversal, so realised numbers on OTHER
 * records may need a manual recount afterwards.
 */
class DeletionService
{
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
            $returnIds = $sale->returns()->pluck('id')->all();

            $this->purgeLedger(ProductReturn::class, $returnIds);
            $this->purgeLedger(Sale::class, [$sale->id]);

            // returns + sale_status_histories cascade via their FK on delete.
            $sale->delete();
        });
    }

    public function deleteRequisition(Requisition $requisition): void
    {
        DB::transaction(function () use ($requisition) {
            $itemIds    = $requisition->items()->pluck('id')->all();
            $paymentIds = $requisition->payments()->pluck('id')->all();
            $expenseIds = $requisition->expenses()->pluck('id')->all();

            $this->purgeLedger(RequisitionItem::class, $itemIds);
            $this->purgeLedger(Payment::class, $paymentIds);
            $this->purgeLedger(RequisitionExpense::class, $expenseIds);
            $this->purgeLedger(Requisition::class, [$requisition->id]);

            // items, payments, expenses cascade via their FK on delete.
            $requisition->delete();
        });
    }

    public function deleteDirectPurchase(DirectPurchase $purchase): void
    {
        DB::transaction(function () use ($purchase) {
            $itemIds    = $purchase->items()->pluck('id')->all();
            $paymentIds = $purchase->payments()->pluck('id')->all();

            $this->purgeLedger(DirectPurchaseItem::class, $itemIds);
            $this->purgeLedger(DirectPurchasePayment::class, $paymentIds);
            $this->purgeLedger(DirectPurchase::class, [$purchase->id]);

            // items + payments cascade via their FK on delete.
            $purchase->delete();
        });
    }

    public function deleteProduct(Product $product): void
    {
        DB::transaction(function () use ($product) {
            // Detach loose references (all nullable FKs) so the rows survive.
            Sale::where('product_id', $product->id)->update(['product_id' => null]);
            ProductReturn::where('product_id', $product->id)->update(['product_id' => null]);
            SaleStatusHistory::where('product_id', $product->id)->update(['product_id' => null]);
            StockMovement::where('product_id', $product->id)->update(['product_id' => null]);
            RequisitionItem::where('product_id', $product->id)->update(['product_id' => null]);

            // Direct-purchase line items require a product, so they must go.
            DirectPurchaseItem::where('product_id', $product->id)->delete();

            $this->purgeLedger(Product::class, [$product->id]);
            $product->delete();
        });
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
        });
    }

    public function deleteSupplier(Supplier $supplier): void
    {
        DB::transaction(function () use ($supplier) {
            // direct_purchases.supplier_id is nullOnDelete — purchases survive.
            $this->purgeLedger(Supplier::class, [$supplier->id]);
            $supplier->delete();
        });
    }

    public function deleteWarehouse(Warehouse $warehouse): void
    {
        DB::transaction(function () use ($warehouse) {
            // direct_purchases.warehouse_id is nullOnDelete — purchases survive.
            $this->purgeLedger(Warehouse::class, [$warehouse->id]);
            $warehouse->delete();
        });
    }
}
