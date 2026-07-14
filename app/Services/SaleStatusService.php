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
    /**
     * Where a sale's units physically sit. Every status implies exactly one of
     * these, and the stock movements needed for a status change are simply the
     * ones that carry the units from the old position to the new one — which is
     * what lets an admin override jump between any two statuses and still leave
     * inventory correct.
     */
    private const FREE = 'free';         // on hand, not reserved
    private const RESERVED = 'reserved'; // on hand, reserved for this sale
    private const OUT = 'out';           // shipped out of stock

    public function __construct(private StockService $stockService, private AuditService $auditService)
    {
    }

    /**
     * Move a sale to a new lifecycle status, applying the matching inventory
     * movement exactly once. Everything runs inside a single transaction with a
     * row lock on the sale so concurrent clicks cannot double-apply stock.
     *
     * Inventory effects (only for sales tied to a product) follow from the
     * position map above: Shipped reserves, Delivered ships out, Returned brings
     * the goods back, Cancelled releases the reservation.
     *
     * $allowOverride lets an administrator set any status regardless of the state
     * machine, to repair a mistake such as a sale a user cancelled by accident.
     * The inventory reconcile below is what makes that safe — nothing else about
     * the write is special-cased.
     */
    public function transition(Sale $sale, string $targetValue, User $user, bool $allowOverride = false): Sale
    {
        $target = SaleStatus::tryFrom($targetValue);

        if ($target === null) {
            throw ValidationException::withMessages(['status' => 'Unknown sale status.']);
        }

        return DB::transaction(function () use ($sale, $target, $user, $allowOverride) {
            $locked = Sale::query()->with('product')->whereKey($sale->id)->lockForUpdate()->firstOrFail();
            $current = SaleStatus::from($locked->status);

            if ($current === $target) {
                throw ValidationException::withMessages(['status' => 'The sale is already '.$target->label().'.']);
            }

            $isOverride = ! $current->canTransitionTo($target);

            if ($isOverride && ! $allowOverride) {
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
                $isOverride
                    ? "Status overridden by {$user->name} from {$current->label()} to {$target->label()} (outside the normal flow)."
                    : "Status changed from {$current->label()} to {$target->label()}."
            );

            return $locked;
        }, 3);
    }

    /**
     * Reconcile inventory with the status the sale is entering, and restate its
     * stock bookkeeping columns. Returns the movement types written to the status
     * history ('book', 'in_return+book', … or null when nothing moved).
     *
     * Deriving the movements from where the units are versus where they must end up
     * makes every status pair correct — including the backwards ones only an admin
     * override can reach — and makes each one idempotent for free: a status whose
     * position matches the current one moves no stock at all.
     */
    private function applyStockMovement(Sale $sale, SaleStatus $target, User $user): ?string
    {
        $this->syncQuantities($sale, $target);

        // Product-less manual sales carry the status and counters only, no inventory.
        if (! $sale->affectsStock()) {
            return null;
        }

        $movements = $this->movementsBetween($this->positionOf($sale), $this->positionFor($target));

        foreach ($movements as $type) {
            $this->stockService->move($sale->product, $type, $sale->quantity, $sale, $user->id);
        }

        $sale->stock_state = $this->stockStateFor($target);

        return $movements === [] ? null : implode('+', $movements);
    }

    /** Where the sale's units sit right now, read back from its stock flag. */
    private function positionOf(Sale $sale): string
    {
        return match ($sale->stock_state) {
            'booked' => self::RESERVED,
            'delivered' => self::OUT,
            default => self::FREE, // none | released | returned — on hand, unreserved
        };
    }

    /** Where a status says the units must sit. */
    private function positionFor(SaleStatus $status): string
    {
        return match ($status) {
            SaleStatus::Shipped, SaleStatus::SendToCourier => self::RESERVED,
            SaleStatus::Delivered => self::OUT,
            // Pending / Confirmed / Cancelled / Returned all mean the goods are on
            // hand with nothing reserved against them.
            default => self::FREE,
        };
    }

    /** The stock movements that carry a sale's units from one position to the other. */
    private function movementsBetween(string $from, string $to): array
    {
        return match (true) {
            $from === $to => [],
            $from === self::FREE && $to === self::RESERVED => ['book'],
            $from === self::RESERVED && $to === self::FREE => ['release'],
            $from === self::RESERVED && $to === self::OUT => ['out_sale'],
            $from === self::OUT && $to === self::FREE => ['in_return'],
            // Book first: out_sale always clears a reservation as the goods leave, so
            // with none of our own to clear it would eat another sale's. Booking first
            // also means an override still has to pass the availability check.
            $from === self::FREE && $to === self::OUT => ['book', 'out_sale'],
            // The goods come back into stock, then get reserved again for this sale.
            $from === self::OUT && $to === self::RESERVED => ['in_return', 'book'],
        };
    }

    /** The stock flag persisted for a status — the inverse of positionFor(). */
    private function stockStateFor(SaleStatus $status): string
    {
        return match ($status) {
            SaleStatus::Shipped, SaleStatus::SendToCourier => 'booked',
            SaleStatus::Delivered => 'delivered',
            SaleStatus::Returned => 'returned',
            SaleStatus::Cancelled => 'released',
            default => 'none', // pending | confirmed
        };
    }

    /**
     * Restate the fulfilment counters for the status being entered. They are set from
     * the status rather than nudged along, so reverting a sale also clears whatever a
     * later stage had written. Delivered is deliberately left alone on Returned: a
     * normally-returned sale really was delivered first, while one an admin forces
     * straight from Pending to Returned never was, and both then read correctly.
     */
    private function syncQuantities(Sale $sale, SaleStatus $target): void
    {
        match ($target) {
            SaleStatus::Shipped, SaleStatus::SendToCourier => $sale->fill([
                'booked_quantity' => $sale->quantity,
                'delivered_quantity' => 0,
                'returned_quantity' => 0,
            ]),
            SaleStatus::Delivered => $sale->fill([
                'booked_quantity' => 0,
                'delivered_quantity' => $sale->quantity,
                'returned_quantity' => 0,
            ]),
            SaleStatus::Returned => $sale->fill([
                'booked_quantity' => 0,
                'returned_quantity' => $sale->quantity,
            ]),
            default => $sale->fill([ // pending | confirmed | cancelled — nothing fulfilled
                'booked_quantity' => 0,
                'delivered_quantity' => 0,
                'returned_quantity' => 0,
            ]),
        };
    }
}
