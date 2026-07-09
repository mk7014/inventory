<?php

namespace App\Enums;

/**
 * The sales lifecycle. `status` is stored on the sales table as the backing
 * string value (not cast to this enum) so legacy string comparisons keep
 * working; use SaleStatus::from($sale->status) where structured logic is needed.
 *
 * The transition map is the single source of truth for the state machine and is
 * enforced in SaleStatusService and surfaced in the UI (valid next actions).
 */
enum SaleStatus: string
{
    case Pending = 'pending';
    case Confirmed = 'confirmed';
    case SendToCourier = 'send_to_courier';
    case Shipped = 'shipped';
    case Delivered = 'delivered';
    case Returned = 'returned';
    case Cancelled = 'cancelled';

    /** Human-readable label for badges and dropdowns. */
    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Confirmed => 'Confirmed',
            self::SendToCourier => 'Send To Courier',
            self::Shipped => 'Shipped',
            self::Delivered => 'Delivered',
            self::Returned => 'Returned',
            self::Cancelled => 'Cancelled',
        };
    }

    /** Tailwind badge classes, consistent with resources/views/partials/status. */
    public function badgeClasses(): string
    {
        return match ($this) {
            self::Pending => 'bg-amber-100 text-amber-800',
            self::Confirmed => 'bg-sky-100 text-sky-800',
            self::SendToCourier => 'bg-violet-100 text-violet-800',
            self::Shipped => 'bg-indigo-100 text-indigo-800',
            self::Delivered => 'bg-emerald-100 text-emerald-800',
            self::Returned => 'bg-red-100 text-red-800',
            self::Cancelled => 'bg-slate-200 text-slate-700',
        };
    }

    /**
     * State machine: allowed target statuses from each state. Terminal states
     * (Returned, Cancelled) return an empty list.
     */
    public static function transitions(): array
    {
        return [
            self::Pending->value => [self::Shipped, self::Cancelled],
            self::Shipped->value => [self::SendToCourier, self::Cancelled],
            self::SendToCourier->value => [self::Delivered, self::Cancelled],
            self::Delivered->value => [self::Returned],
            self::Returned->value => [],
            self::Cancelled->value => [],
            // Legacy: Confirmed is retired from the active flow but any old row
            // still gets a graceful path forward.
            self::Confirmed->value => [self::Shipped, self::Cancelled],
        ];
    }

    /** @return SaleStatus[] valid next statuses from this state. */
    public function allowedNext(): array
    {
        return self::transitions()[$this->value] ?? [];
    }

    public function canTransitionTo(self $target): bool
    {
        foreach ($this->allowedNext() as $next) {
            if ($next === $target) {
                return true;
            }
        }

        return false;
    }

    public function isTerminal(): bool
    {
        return $this->allowedNext() === [];
    }
}
