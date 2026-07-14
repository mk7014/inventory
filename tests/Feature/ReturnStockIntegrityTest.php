<?php

namespace Tests\Feature;

use App\Models\DarazAccount;
use App\Models\Product;
use App\Models\Role;
use App\Models\Sale;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Returns used to bypass the SaleStatus machine entirely: ReturnService checked only
 * that the sale was not already returned, so a sale whose goods had never left stock
 * could still be "returned" and restocked — inventing units out of nothing and, for a
 * shipped sale, leaking its reservation permanently.
 */
class ReturnStockIntegrityTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $this->seed(RolePermissionSeeder::class);

        return User::create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => bcrypt('secret'),
            'role_id' => Role::where('slug', 'admin')->value('id'),
            'status' => 'active',
        ]);
    }

    private function product(int $currentStock, int $bookedStock = 0): Product
    {
        $product = Product::create(['name' => 'Widget']);

        $product->forceFill([
            'current_stock' => $currentStock,
            'booked_stock' => $bookedStock,
        ])->save();

        return $product;
    }

    private function sale(Product $product, User $user, string $status, string $stockState, int $quantity = 5): Sale
    {
        $account = DarazAccount::create([
            'account_name' => 'Acct',
            'shop_name' => 'Shop',
            'status' => 'active',
        ]);

        return Sale::create([
            'daraz_account_id' => $account->id,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'selling_price' => 100,
            'quantity' => $quantity,
            'source' => 'stock',
            'status' => $status,
            'stock_state' => $stockState,
            'booked_quantity' => $stockState === 'booked' ? $quantity : 0,
            'delivered_quantity' => $stockState === 'delivered' ? $quantity : 0,
            'sold_date' => now()->toDateString(),
            'created_by' => $user->id,
        ]);
    }

    public function test_a_pending_sale_cannot_be_returned(): void
    {
        $admin = $this->admin();
        $product = $this->product(10);
        $sale = $this->sale($product, $admin, 'pending', 'none');

        $this->actingAs($admin)->post(route('returns.store'), [
            'sale_id' => $sale->id,
            'quantity' => 5,
            'condition' => 'good',
            'return_date' => now()->toDateString(),
        ])->assertSessionHasErrors('sale_id');

        // Nothing ever left stock, so nothing may come back into it.
        $this->assertSame(10, $product->fresh()->current_stock);
        $this->assertDatabaseCount('returns', 0);
    }

    public function test_a_shipped_sale_cannot_be_returned(): void
    {
        $admin = $this->admin();
        $product = $this->product(10, 5); // 5 reserved for the shipped order
        $sale = $this->sale($product, $admin, 'shipped', 'booked');

        $this->actingAs($admin)->post(route('returns.store'), [
            'sale_id' => $sale->id,
            'quantity' => 5,
            'condition' => 'good',
            'return_date' => now()->toDateString(),
        ])->assertSessionHasErrors('sale_id');

        // The goods are still booked, not yet shipped out: stock and the reservation
        // must both be untouched (this used to add 5 AND strand the 5 booked units).
        $fresh = $product->fresh();
        $this->assertSame(10, $fresh->current_stock);
        $this->assertSame(5, $fresh->booked_stock);
        $this->assertDatabaseCount('returns', 0);
    }

    public function test_a_delivered_sale_is_returned_and_restocked(): void
    {
        $admin = $this->admin();
        // Delivered: the 5 units already left stock, so 5 of the original 10 remain.
        $product = $this->product(5);
        $sale = $this->sale($product, $admin, 'delivered', 'delivered');

        $this->actingAs($admin)->post(route('returns.store'), [
            'sale_id' => $sale->id,
            'quantity' => 5,
            'condition' => 'good',
            'return_date' => now()->toDateString(),
        ])->assertRedirect()->assertSessionHasNoErrors();

        $this->assertSame(10, $product->fresh()->current_stock);
        $this->assertSame('returned', $sale->fresh()->status);
    }

    public function test_a_damaged_return_is_not_restocked(): void
    {
        $admin = $this->admin();
        $product = $this->product(5);
        $sale = $this->sale($product, $admin, 'delivered', 'delivered');

        $this->actingAs($admin)->post(route('returns.store'), [
            'sale_id' => $sale->id,
            'quantity' => 5,
            'condition' => 'damaged',
            'return_date' => now()->toDateString(),
        ])->assertRedirect()->assertSessionHasNoErrors();

        $this->assertSame(5, $product->fresh()->current_stock);
        $this->assertSame('returned', $sale->fresh()->status);
    }
}
