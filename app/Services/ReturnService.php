<?php

namespace App\Services;

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

            if ($lockedSale->status === 'returned') {
                throw ValidationException::withMessages(['sale_id' => 'This sale is already returned.']);
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

            $lockedSale->update(['status' => 'returned']);

            if ($return->condition === 'good' && $lockedSale->product) {
                $this->stockService->move($lockedSale->product, 'in_return', $return->quantity, $return, $user->id);
            }

            $this->auditService->record('return.created', $return, null, $return->toArray());

            $admins = User::query()->whereRelation('role', 'slug', 'admin')->where('status', 'active')->get();
            Notification::send($admins, new SystemNotification('Product return recorded', route('returns.index'), 'return'));

            return $return;
        });
    }
}
