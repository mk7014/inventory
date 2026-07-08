<?php

namespace App\Http\Controllers;

use App\Http\Requests\RoleStoreRequest;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class RoleController extends Controller
{
    public function index(): View
    {
        $roles = Role::query()
            ->withCount(['permissions', 'users'])
            ->orderByDesc('is_system')
            ->orderBy('name')
            ->paginate(20);

        return view('roles.index', compact('roles'));
    }

    public function create(): View
    {
        return view('roles.create', [
            'role'          => new Role(['status' => 'active']),
            'modules'       => $this->modules(),
            'assigned'      => [],
        ]);
    }

    public function store(RoleStoreRequest $request): RedirectResponse
    {
        $role = Role::create($request->safe()->only(['name', 'description', 'status']));
        $role->permissions()->sync($request->permissionIds());

        return redirect()->route('roles.index')->with('success', 'Role created.');
    }

    public function edit(Role $role): View
    {
        return view('roles.edit', [
            'role'     => $role,
            'modules'  => $this->modules(),
            'assigned' => $role->permissions()->pluck('permissions.id')->all(),
        ]);
    }

    public function update(RoleStoreRequest $request, Role $role): RedirectResponse
    {
        $role->update($request->safe()->only(['name', 'description', 'status']));

        // The admin role must always retain every permission.
        if (! $role->isAdmin()) {
            $role->permissions()->sync($request->permissionIds());
        }

        return redirect()->route('roles.index')->with('success', 'Role updated.');
    }

    public function destroy(Role $role): RedirectResponse
    {
        if ($role->is_system) {
            return back()->with('error', 'System roles cannot be deleted.');
        }

        if ($role->users()->exists()) {
            return back()->with('error', 'This role is assigned to users — reassign them before deleting.');
        }

        $role->delete();

        return redirect()->route('roles.index')->with('success', 'Role deleted.');
    }

    /**
     * Build the module → permissions structure for the matrix UI, grouped by the
     * high-level section defined in config/permissions.php. Every catalogued
     * permission is loaded once and mapped onto its module + action.
     *
     * @return Collection<string, array<string, mixed>>
     */
    private function modules(): Collection
    {
        $permissions = Permission::query()->get()->groupBy('module');
        $actionLabels = config('permissions.actions');

        return collect(config('permissions.modules'))->map(function (array $definition, string $module) use ($permissions, $actionLabels) {
            $rows = collect($definition['actions'])
                ->mapWithKeys(fn (string $action) => [
                    $action => [
                        'label'      => $actionLabels[$action] ?? ucfirst($action),
                        'permission' => optional($permissions->get($module))->firstWhere('action', $action),
                    ],
                ])
                ->filter(fn ($row) => $row['permission'] !== null);

            return [
                'label'   => $definition['label'],
                'group'   => $definition['group'] ?? 'General',
                'actions' => $rows,
            ];
        })->groupBy('group');
    }
}
