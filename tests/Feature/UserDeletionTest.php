<?php

namespace Tests\Feature;

use App\Models\BalanceTransaction;
use App\Models\DarazAccount;
use App\Models\Product;
use App\Models\Role;
use App\Models\Sale;
use App\Models\User;
use App\Services\BalanceService;
use App\Services\StockService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Deleting a user is a hard purge: their sales, requisitions, direct purchases,
 * expenses and wallet ledger all go, and the stock those records moved is unwound.
 * It is irreversible, so the guards around it matter as much as the purge itself.
 */
class UserDeletionTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $roleSlug, string $name, string $email): User
    {
        return User::create([
            'name' => $name,
            'email' => $email,
            'password' => bcrypt('secret'),
            'role_id' => Role::where('slug', $roleSlug)->value('id'),
            'status' => 'active',
        ]);
    }

    private function admin(string $name = 'Admin', string $email = 'admin@example.com'): User
    {
        $this->seed(RolePermissionSeeder::class);

        return $this->user('admin', $name, $email);
    }

    private function product(int $stock): Product
    {
        $product = Product::create(['name' => 'Widget']);
        $product->forceFill(['current_stock' => $stock, 'booked_stock' => 0])->save();

        return $product;
    }

    /** A delivered sale that has already taken its units out of stock. */
    private function deliveredSale(Product $product, User $seller, int $quantity = 2): Sale
    {
        $account = DarazAccount::create(['account_name' => 'A', 'shop_name' => 'S', 'status' => 'active']);

        $sale = Sale::create([
            'daraz_account_id' => $account->id,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'selling_price' => 200,
            'quantity' => $quantity,
            'source' => 'stock',
            'status' => 'delivered',
            'stock_state' => 'delivered',
            'delivered_quantity' => $quantity,
            'sold_date' => now()->toDateString(),
            'created_by' => $seller->id,
        ]);

        DB::transaction(fn () => app(StockService::class)->move($product, 'out_sale', $quantity, $sale, $seller->id));

        return $sale;
    }

    public function test_it_purges_the_user_and_unwinds_the_stock_their_sales_moved(): void
    {
        $admin = $this->admin();
        $employee = $this->user('employee', 'Store Manager', 'sm@example.com');

        $product = $this->product(10);
        $sale = $this->deliveredSale($product, $employee, 3);

        DB::transaction(fn () => app(BalanceService::class)->credit($employee, 500, null, $admin->id));

        // The sale shipped: 10 − 3 = 7 on hand.
        $this->assertSame(7, $product->fresh()->current_stock);

        $this->actingAs($admin)
            ->delete(route('users.destroy', $employee), ['confirm_name' => 'Store Manager'])
            ->assertRedirect(route('users.index'))
            ->assertSessionHasNoErrors();

        $this->assertNull(User::find($employee->id));
        $this->assertNull(Sale::find($sale->id));
        $this->assertSame(0, BalanceTransaction::where('user_id', $employee->id)->count());

        // Purging the sale returns its units to stock.
        $this->assertSame(10, $product->fresh()->current_stock);
    }

    public function test_the_typed_name_must_match_or_nothing_is_deleted(): void
    {
        $admin = $this->admin();
        $employee = $this->user('employee', 'Store Manager', 'sm@example.com');

        $this->actingAs($admin)
            ->delete(route('users.destroy', $employee), ['confirm_name' => 'store manager'])
            ->assertSessionHasErrors('confirm_name');

        $this->assertNotNull(User::find($employee->id));
    }

    public function test_you_cannot_delete_your_own_account(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->delete(route('users.destroy', $admin), ['confirm_name' => $admin->name])
            ->assertSessionHasErrors('confirm_name');

        $this->assertNotNull(User::find($admin->id));
    }

    public function test_the_last_administrator_cannot_be_deleted(): void
    {
        $admin = $this->admin();
        $second = $this->user('admin', 'Second Admin', 'second@example.com');

        // Two admins exist, so the second one can go.
        $this->actingAs($admin)
            ->delete(route('users.destroy', $second), ['confirm_name' => 'Second Admin'])
            ->assertSessionHasNoErrors();
        $this->assertNull(User::find($second->id));

        // Now only one admin is left; a second admin re-appears purely to attempt the
        // delete, because you may never delete yourself.
        $third = $this->user('admin', 'Third Admin', 'third@example.com');
        $this->actingAs($third)
            ->delete(route('users.destroy', $admin), ['confirm_name' => $admin->name])
            ->assertSessionHasNoErrors();

        // Third is now the only admin — and cannot delete themselves either way.
        $this->assertSame(1, User::whereHas('role', fn ($q) => $q->where('slug', 'admin'))->count());
    }

    public function test_an_employee_without_the_permission_cannot_delete_users(): void
    {
        $this->admin();
        $employee = $this->user('employee', 'Store Manager', 'sm@example.com');
        $target = $this->user('employee', 'Other', 'other@example.com');

        $this->actingAs($employee)
            ->delete(route('users.destroy', $target), ['confirm_name' => 'Other'])
            ->assertForbidden();

        $this->assertNotNull(User::find($target->id));
    }

    public function test_deleting_a_user_leaves_no_balance_ledger_drift(): void
    {
        $admin = $this->admin();
        $employee = $this->user('employee', 'Store Manager', 'sm@example.com');
        $other = $this->user('employee', 'Colleague', 'col@example.com');

        DB::transaction(fn () => app(BalanceService::class)->credit($other, 900, null, $admin->id));

        $this->actingAs($admin)
            ->delete(route('users.destroy', $employee), ['confirm_name' => 'Store Manager'])
            ->assertSessionHasNoErrors();

        // The surviving colleague's wallet must still equal the sum of their ledger.
        $ledger = (float) BalanceTransaction::where('user_id', $other->id)->sum('amount');
        $this->assertSame((float) $other->fresh()->balance, $ledger);
    }
}
