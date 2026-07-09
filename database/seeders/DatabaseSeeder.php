<?php

namespace Database\Seeders;

use App\Models\DarazAccount;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductReturn;
use App\Models\Requisition;
use App\Models\Sale;
use App\Models\Role;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Roles & permissions must exist before we can assign users to them.
        $this->call(RolePermissionSeeder::class);

        $adminRole = Role::query()->where('slug', 'admin')->firstOrFail();
        $employeeRole = Role::query()->where('slug', 'employee')->firstOrFail();

        $admin = User::query()->updateOrCreate([
            'email' => 'admin@example.com',
        ], [
            'name' => 'Owner Admin',
            'password' => 'password',
            'role_id' => $adminRole->id,
            'status' => 'active',
        ]);

        $employee = User::query()->updateOrCreate([
            'email' => 'employee@example.com',
        ], [
            'name' => 'Store Manager',
            'password' => 'password',
            'role_id' => $employeeRole->id,
            'status' => 'active',
        ]);

        $accounts = collect([
            ['account_name' => 'Daraz Account 1', 'shop_name' => 'Smart IT Flagship'],
            ['account_name' => 'Daraz Account 2', 'shop_name' => 'Gadget Point BD'],
            ['account_name' => 'Daraz Account 3', 'shop_name' => 'Tech Basket'],
            ['account_name' => 'Daraz Account 4', 'shop_name' => 'Daily Deals Hub'],
            ['account_name' => 'Daraz Account 5', 'shop_name' => 'NextTop BD'],
        ])->map(fn ($row) => DarazAccount::query()->updateOrCreate(['account_name' => $row['account_name']], $row + ['status' => 'active']));

        // Suppliers & warehouses power the Direct Purchase module.
        collect([
            ['name' => 'Dhaka Wholesale Market', 'phone' => '01700000001', 'address' => 'Gulistan, Dhaka'],
            ['name' => 'Chittagong Import House', 'phone' => '01700000002', 'address' => 'Agrabad, Chittagong'],
            ['name' => 'BD Gadget Suppliers', 'phone' => '01700000003', 'address' => 'Motijheel, Dhaka'],
        ])->each(fn ($row) => Supplier::query()->updateOrCreate(['name' => $row['name']], $row + ['status' => 'active']));

        collect([
            ['name' => 'Main Warehouse', 'location' => 'Dhaka HQ'],
            ['name' => 'Secondary Store', 'location' => 'Uttara'],
        ])->each(fn ($row) => Warehouse::query()->updateOrCreate(['name' => $row['name']], $row + ['status' => 'active']));

        $products = collect([
            ['name' => 'USB Type-C Cable', 'sku' => 'USB-C-01', 'default_purchase_price' => 120, 'current_stock' => 8],
            ['name' => 'Bluetooth Earbuds', 'sku' => 'EAR-BT-02', 'default_purchase_price' => 780, 'current_stock' => 2],
            ['name' => 'Laptop Stand', 'sku' => 'LAP-ST-03', 'default_purchase_price' => 550, 'current_stock' => 4],
            ['name' => 'Phone Charger 20W', 'sku' => 'CHR-20W-04', 'default_purchase_price' => 420, 'current_stock' => 0],
        ])->map(fn ($row) => Product::query()->updateOrCreate(['sku' => $row['sku']], $row));

        foreach ($products as $product) {
            if ($product->current_stock > 0) {
                StockMovement::query()->firstOrCreate([
                    'product_id' => $product->id,
                    'reference_type' => Product::class,
                    'reference_id' => $product->id,
                    'type' => 'in_purchase',
                ], [
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'type' => 'in_purchase',
                    'quantity' => $product->current_stock,
                    'reference_type' => Product::class,
                    'reference_id' => $product->id,
                    'created_by' => $admin->id,
                ]);
            }
        }

        $requisition = Requisition::query()->firstOrCreate([
            'requisition_number' => 'REQ-'.now()->format('Ymd').'-0001',
        ], [
            'employee_id' => $employee->id,
            'total_amount' => 2400,
            'approved_amount' => 2400,
            'status' => 'approved',
            'admin_note' => 'Approved for urgent dispatch.',
            'requested_at' => now()->subDays(2),
            'reviewed_at' => now()->subDay(),
            'reviewed_by' => $admin->id,
        ]);

        $requisition->items()->firstOrCreate([
            'order_id_daraz' => 'DZ-10001',
        ], [
            'daraz_account_id' => $accounts[0]->id,
            'product_id' => $products[3]->id,
            'product_name' => $products[3]->name,
            'order_id_daraz' => 'DZ-10001',
            'quantity' => 5,
            'purchase_price' => 420,
            'subtotal' => 2100,
        ]);
        $requisition->items()->firstOrCreate([
            'order_id_daraz' => 'DZ-10002',
        ], [
            'daraz_account_id' => $accounts[1]->id,
            'product_id' => $products[0]->id,
            'product_name' => $products[0]->name,
            'order_id_daraz' => 'DZ-10002',
            'quantity' => 2,
            'purchase_price' => 150,
            'subtotal' => 300,
        ]);

        Payment::query()->firstOrCreate([
            'requisition_id' => $requisition->id,
            'reference' => 'BKASH-TEST-1',
        ], [
            'requisition_id' => $requisition->id,
            'paid_to' => $employee->id,
            'paid_by' => $admin->id,
            'amount' => 2400,
            'payment_method' => 'bkash',
            'payment_date' => now()->subDay(),
            'reference' => 'BKASH-TEST-1',
            'note' => 'Demo payment',
        ]);

        $sale = Sale::query()->firstOrCreate([
            'product_name' => $products[0]->name,
            'sold_date' => now()->subDays(3)->toDateString(),
            'status' => 'returned',
        ], [
            'daraz_account_id' => $accounts[0]->id,
            'product_id' => $products[0]->id,
            'product_name' => $products[0]->name,
            'selling_price' => 260,
            'quantity' => 1,
            'source' => 'stock',
            'status' => 'returned',
            'sold_date' => now()->subDays(3)->toDateString(),
            'created_by' => $employee->id,
        ]);

        ProductReturn::query()->firstOrCreate([
            'sale_id' => $sale->id,
            'return_date' => now()->subDay()->toDateString(),
        ], [
            'sale_id' => $sale->id,
            'daraz_account_id' => $accounts[0]->id,
            'product_id' => $products[0]->id,
            'product_name' => $products[0]->name,
            'quantity' => 1,
            'condition' => 'good',
            'return_date' => now()->subDay()->toDateString(),
            'reason' => 'Customer changed mind',
            'created_by' => $employee->id,
        ]);

        Sale::query()->firstOrCreate([
            'product_name' => $products[1]->name,
            'sold_date' => now()->toDateString(),
            'status' => 'completed',
        ], [
            'daraz_account_id' => $accounts[2]->id,
            'product_id' => $products[1]->id,
            'product_name' => $products[1]->name,
            'selling_price' => 1250,
            'quantity' => 2,
            'source' => 'new_purchase',
            'status' => 'completed',
            'sold_date' => now()->toDateString(),
            'created_by' => $employee->id,
        ]);
    }
}
