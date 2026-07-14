<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Contracts\Database\Query\Builder;

/**
 * The single source of truth for "which users are excluded from the books".
 *
 * Every financial aggregate — dashboard, reports, profit, cost basis — routes its
 * exclusion through here, so voiding a user takes effect everywhere at once and a
 * new report cannot silently forget to apply the rule.
 */
class VoidedUsers
{
    /** @var array<int>|null resolved once per request */
    private static ?array $ids = null;

    /** @return array<int> */
    public static function ids(): array
    {
        return self::$ids ??= User::query()->voided()->pluck('id')->all();
    }

    public static function any(): bool
    {
        return self::ids() !== [];
    }

    /**
     * A signature that changes whenever the voided set changes — mixed into cache
     * keys so voiding a user immediately invalidates cached figures instead of
     * leaving stale numbers on screen until the TTL lapses.
     */
    public static function signature(): string
    {
        return self::any() ? substr(md5(implode(',', self::ids())), 0, 8) : 'none';
    }

    /**
     * Drop rows whose $column points at a voided user.
     *
     * NULL-safe on purpose: `col NOT IN (1,2)` evaluates to NULL when col IS NULL,
     * which would silently drop every record with no author (e.g. a sale whose
     * creator was detached). Those rows belong to the company and must survive.
     */
    public static function exclude(Builder $query, string $column): Builder
    {
        if (! self::any()) {
            return $query;
        }

        return $query->where(
            fn (Builder $inner) => $inner->whereNull($column)->orWhereNotIn($column, self::ids())
        );
    }

    /** Test/CLI helper — forget the memoised set after voiding or restoring. */
    public static function flush(): void
    {
        self::$ids = null;
    }
}
