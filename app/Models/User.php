<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'avatar',
        'role_id',
        'status',
        // 'balance' is owned by BalanceService, which writes it with forceFill alongside
        // a balance_transactions ledger row. Mass-assignment would let it drift from the
        // ledger silently.
    ];

    /**
     * Per-request cache of the assigned role's permission names.
     *
     * @var \Illuminate\Support\Collection<int, string>|null
     */
    private ?\Illuminate\Support\Collection $permissionCache = null;

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'balance' => 'decimal:2',
        ];
    }

    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    /**
     * Public URL for the uploaded avatar, or null to fall back to initials.
     * Built with asset() so it uses the host the app is actually served from
     * (honouring the trusted proxy) rather than a possibly-stale APP_URL.
     */
    public function avatarUrl(): ?string
    {
        if (blank($this->avatar)) {
            return null;
        }

        return asset('storage/'.ltrim($this->avatar, '/'));
    }

    /**
     * Two-letter initials used as the avatar fallback.
     */
    public function initials(): string
    {
        return \Illuminate\Support\Str::of($this->name)
            ->explode(' ')
            ->filter()
            ->take(2)
            ->map(fn ($part) => \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($part, 0, 1)))
            ->implode('') ?: 'U';
    }

    public function isAdmin(): bool
    {
        return $this->role?->slug === 'admin';
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Does this user's role grant the given permission? Admins bypass every
     * check. The role's permission names are resolved once and cached for the
     * lifetime of the request to keep authorization queries off the hot path.
     */
    public function hasPermission(string $name): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        return $this->permissionNames()->contains($name);
    }

    /**
     * @return \Illuminate\Support\Collection<int, string>
     */
    public function permissionNames(): \Illuminate\Support\Collection
    {
        return $this->permissionCache ??= ($this->role?->isActive()
            ? $this->role->permissions->pluck('name')
            : collect());
    }

    public function requisitions()
    {
        return $this->hasMany(Requisition::class, 'employee_id');
    }

    public function balanceTransactions()
    {
        return $this->hasMany(BalanceTransaction::class);
    }
}
