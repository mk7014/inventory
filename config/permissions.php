<?php

/*
|--------------------------------------------------------------------------
| Permission Catalogue
|--------------------------------------------------------------------------
|
| Single source of truth for every module, its available actions and the
| sidebar menu it maps to. Used by:
|   - RolePermissionSeeder  (creates the permission rows)
|   - AppServiceProvider    (registers a Gate per permission)
|   - The permission-matrix UI (roles create/edit screen)
|   - The dynamic sidebar   (menu visibility via the `view` permission)
|
| A permission name is always "{module}.{action}" (e.g. requisitions.create).
| To add a new module or action in the future, just extend this file and run
| `php artisan db:seed --class=RolePermissionSeeder` — nothing else is required.
|
*/

return [

    // Human-readable labels for each action, shown as the matrix column headers.
    'actions' => [
        'view'    => 'View / Menu',
        'create'  => 'Create',
        'update'  => 'Update',
        'delete'  => 'Delete',
        'export'  => 'Export',
        'approve' => 'Approve',
    ],

    // module key => definition. `menu` (optional) drives the dynamic sidebar.
    'modules' => [
        'dashboard' => [
            'label'   => 'Dashboard',
            'group'   => 'Overview',
            'actions' => ['view'],
            'menu'    => ['route' => 'dashboard', 'label' => 'Dashboard'],
        ],
        'requisitions' => [
            'label'   => 'Requisitions',
            'group'   => 'Operations',
            'actions' => ['view', 'create', 'approve', 'delete'],
            'menu'    => ['route' => 'requisitions.index', 'label' => 'Requisitions', 'parent' => 'Operations'],
        ],
        'direct_purchases' => [
            'label'   => 'Direct Purchases',
            'group'   => 'Operations',
            'actions' => ['view', 'create', 'approve', 'delete'],
            'menu'    => ['route' => 'direct-purchases.index', 'label' => 'Direct Purchase', 'parent' => 'Operations'],
        ],
        'payments' => [
            'label'   => 'Payments',
            'group'   => 'Operations',
            'actions' => ['view', 'create'],
            'menu'    => ['route' => 'payments.index', 'label' => 'Payments', 'parent' => 'Operations'],
        ],
        'sales' => [
            'label'   => 'Sales',
            'group'   => 'Operations',
            'actions' => ['view', 'create', 'update'],
            'menu'    => ['route' => 'sales.index', 'label' => 'Sales', 'parent' => 'Operations'],
        ],
        'returns' => [
            'label'   => 'Returns',
            'group'   => 'Operations',
            'actions' => ['view', 'create'],
            'menu'    => ['route' => 'returns.index', 'label' => 'Returns', 'parent' => 'Operations'],
        ],
        'products' => [
            'label'   => 'Products & Stock',
            'group'   => 'Inventory',
            'actions' => ['view', 'create', 'update'],
            'menu'    => ['route' => 'products.index', 'label' => 'Products & Stock', 'parent' => 'Inventory'],
        ],
        'reports' => [
            'label'   => 'Reports',
            'group'   => 'Inventory',
            'actions' => ['view', 'export'],
            'menu'    => ['route' => 'reports.index', 'label' => 'Reports', 'parent' => 'Inventory'],
        ],
        'suppliers' => [
            'label'   => 'Suppliers',
            'group'   => 'Administration',
            'actions' => ['view', 'create', 'update'],
            'menu'    => ['route' => 'suppliers.index', 'label' => 'Suppliers', 'parent' => 'Admin Panel'],
        ],
        'warehouses' => [
            'label'   => 'Warehouses',
            'group'   => 'Administration',
            'actions' => ['view', 'create', 'update'],
            'menu'    => ['route' => 'warehouses.index', 'label' => 'Warehouses', 'parent' => 'Admin Panel'],
        ],
        'accounts' => [
            'label'   => 'Daraz Accounts',
            'group'   => 'Administration',
            'actions' => ['view', 'create', 'update'],
            'menu'    => ['route' => 'accounts.index', 'label' => 'Daraz Accounts', 'parent' => 'Admin Panel'],
        ],
        'users' => [
            'label'   => 'Users',
            'group'   => 'Administration',
            'actions' => ['view', 'create', 'update'],
            'menu'    => ['route' => 'users.index', 'label' => 'Users', 'parent' => 'Admin Panel'],
        ],
        'roles' => [
            'label'   => 'Roles & Permissions',
            'group'   => 'Administration',
            'actions' => ['view', 'create', 'update', 'delete'],
            'menu'    => ['route' => 'roles.index', 'label' => 'Roles & Permissions', 'parent' => 'Admin Panel'],
        ],
        'balances' => [
            'label'   => 'Employee Balances',
            'group'   => 'Administration',
            'actions' => ['view'],
            'menu'    => ['route' => 'balances.index', 'label' => 'Employee Balances', 'parent' => 'Admin Panel'],
        ],
    ],
];
