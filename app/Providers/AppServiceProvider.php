<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Administrators bypass every ability.
        Gate::before(fn (User $user) => $user->isAdmin() ? true : null);

        // Register one ability per catalogued permission so Blade `@can`,
        // controller authorization and the permission middleware all resolve
        // against the role's granted permissions.
        foreach ((array) config('permissions.modules') as $module => $definition) {
            foreach ($definition['actions'] as $action) {
                $name = "{$module}.{$action}";
                Gate::define($name, fn (User $user) => $user->hasPermission($name));
            }
        }
    }
}
