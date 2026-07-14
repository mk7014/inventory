<?php

namespace App\Services;

use App\Enums\SaleStatus;
use App\Models\ProductReturn;
use App\Models\Sale;
use App\Models\User;
use App\Notifications\SystemNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

class ReturnService
{
    public function __construct(private StockService $stockService, private AuditService $auditService)
    {
    }

    public function create(Sale $sale, array $data, User $user): ProductReturn
    {
        return DB::transaction(function () use ($sale, $data, $user) {
            $lockedSale = Sale::query()->with('product')->whereKey($sale->id)->lockForUpdate()->firstOrFail();

            // SaleStatus is the single source of truth: only a delivered sale can be
            // returned. Without this, a pending sale (nothing ever shipped) would be
            // restocked into existence, and a shipped one would be credited back while
            // its reservation leaked — the goods had not left stock yet.
            $current = SaleStatus::from($lockedSale->status);

            if (! $current->canTransitionTo(SaleStatus::Returned)) {
                throw ValidationException::withMessages([
                    'sale_id' => 'A '.$current->label().' sale cannot be returned — only delivered sales can.',
                ]);
            }

            if ((int) $data['quantity'] > $lockedSale->quantity) {
                throw ValidationException::withMessages(['quantity' => 'Return quantity cannot exceed sale quantity.']);
            }

            $return = ProductReturn::create([
                'sale_id' => $lockedSale->id,
                'daraz_account_id' => $lockedSale->daraz_account_id,
                'product_id' => $lockedSale->product_id,
                'product_name' => $lockedSale->product_name,
                'quantity' => $data['quantity'],
                'condition' => $data['condition'],
                'return_date' => $data['return_date'],
                'reason' => $data['reason'] ?? null,
                'created_by' => $user->id,
            ]);

            // Restock good-condition returns, but only goods that actually left stock:
            // stock_state is 'delivered' exactly once out_sale has run. The flag also
            // stops the sales Action menu from restoring the same sale twice.
            $restock = $return->condition === 'good'
                && $lockedSale->affectsStock()
                && $lockedSale->stock_state === 'delivered';

            if ($restock) {
                $this->stockService->move($lockedSale->product, 'in_return', $return->quantity, $return, $user->id);
            }

            $lockedSale->update([
                'status' => 'returned',
                'stock_state' => 'returned',
                'returned_quantity' => $return->quantity,
                'status_updated_at' => now(),
                'status_updated_by' => $user->id,
            ]);

            $this->auditService->record('return.created', $return, null, $return->toArray());

            $admins = User::query()->whereRelation('role', 'slug', 'admin')->where('status', 'active')->get();
            Notification::send($admins, new SystemNotification('Product return recorded', route('returns.index'), 'return'));

            return $return;
        }, 3);
    }
}
