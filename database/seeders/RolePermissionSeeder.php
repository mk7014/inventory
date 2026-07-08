<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RolePermissionSeeder extends Seeder
{
    /**
     * Build the permission catalogue from config/permissions.php and grant it to
     * the base roles. Fully idempotent — safe to re-run whenever the catalogue
     * changes (e.g. after adding a new module).
     */
    public function run(): void
    {
        $actionLabels = config('permissions.actions');
        $permissionIds = [];
        $validNames = [];

        foreach (config('permissions.modules') as $module => $definition) {
            foreach ($definition['actions'] as $action) {
                $name = "{$module}.{$action}";
                $validNames[] = $name;
                $permission = Permission::query()->updateOrCreate(
                    ['name' => $name],
                    [
                        'module' => $module,
                        'action' => $action,
                        'label'  => ($definition['label'] ?? ucfirst($module)).' — '.($actionLabels[$action] ?? ucfirst($action)),
                    ]
                );
                $permissionIds[] = $permission->id;
            }
        }

        // Prune permissions that are no longer in the catalogue (cascades the pivot).
        Permission::query()->whereNotIn('name', $validNames)->delete();

        // Administrator: every permission (also bypasses checks via Gate::before,
        // but syncing keeps the matrix accurate on screen).
        $admin = Role::query()->updateOrCreate(
            ['slug' => 'admin'],
            ['name' => 'Administrator', 'description' => 'Full, unrestricted access to every module.', 'status' => 'active', 'is_system' => true],
        );
        $admin->permissions()->sync($permissionIds);

        // Employee: no management permissions by default. They still see their
        // own balance & costing, which are personal pages with no permission gate.
        // The admin can grant additional permissions from the Roles screen.
        Role::query()->updateOrCreate(
            ['slug' => 'employee'],
            ['name' => 'Employee', 'description' => 'Limited access — personal balance and costing.', 'status' => 'active', 'is_system' => true],
        );
    }
}
