<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Role;
use App\Models\StockAdjustment;
use App\Models\StockMovement;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockAdjustmentTest extends TestCase
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

    public function test_the_adjustment_page_renders(): void
    {
        $this->actingAs($this->admin())
            ->get(route('stock-adjustments.index'))
            ->assertOk()
            ->assertSee('Stock Adjustment')
            ->assertSee('New Adjustment');
    }

    public function test_an_increase_adds_stock_and_writes_the_ledger(): void
    {
        $admin = $this->admin();
        $product = Product::create(['name' => 'Widget', 'current_stock' => 4, 'booked_stock' => 0]);

        $this->actingAs($admin)->post(route('stock-adjustments.store'), [
            'product_id' => $product->id,
            'type' => 'increase',
            'quantity' => 6,
            'reason' => 'found',
        ])->assertRedirect()->assertSessionHasNoErrors();

        $this->assertSame(10, $product->fresh()->current_stock);

        $adjustment = StockAdjustment::sole();
        $this->assertSame([4, 10], [$adjustment->stock_before, $adjustment->stock_after]);

        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id,
            'type' => 'adjust_in',
            'quantity' => 6,
            'reference_type' => StockAdjustment::class,
            'reference_id' => $adjustment->id,
        ]);
    }

    public function test_a_decrease_removes_stock(): void
    {
        $admin = $this->admin();
        $product = Product::create(['name' => 'Widget', 'current_stock' => 10, 'booked_stock' => 0]);

        $this->actingAs($admin)->post(route('stock-adjustments.store'), [
            'product_id' => $product->id,
            'type' => 'decrease',
            'quantity' => 3,
            'reason' => 'damaged',
        ])->assertRedirect()->assertSessionHasNoErrors();

        $this->assertSame(7, $product->fresh()->current_stock);
        $this->assertDatabaseHas('stock_movements', ['type' => 'adjust_out', 'quantity' => -3]);
    }

    public function test_a_decrease_cannot_cut_into_booked_stock(): void
    {
        $admin = $this->admin();
        // 6 on hand, 5 reserved for shipped orders → only 1 may be removed.
        $product = Product::create(['name' => 'Widget', 'current_stock' => 6, 'booked_stock' => 5]);

        $this->actingAs($admin)->post(route('stock-adjustments.store'), [
            'product_id' => $product->id,
            'type' => 'decrease',
            'quantity' => 3,
            'reason' => 'lost',
        ])->assertSessionHasErrors('quantity');

        // Stock untouched and the adjustment row rolled back with the movement.
        $this->assertSame(6, $product->fresh()->current_stock);
        $this->assertSame(0, StockAdjustment::count());
        $this->assertSame(0, StockMovement::count());
    }

    public function test_a_reason_must_match_the_direction(): void
    {
        $admin = $this->admin();
        $product = Product::create(['name' => 'Widget', 'current_stock' => 10, 'booked_stock' => 0]);

        // "damaged" is a decrease-only reason.
        $this->actingAs($admin)->post(route('stock-adjustments.store'), [
            'product_id' => $product->id,
            'type' => 'increase',
            'quantity' => 2,
            'reason' => 'damaged',
        ])->assertSessionHasErrors('reason');

        $this->assertSame(10, $product->fresh()->current_stock);
    }

    public function test_an_employee_without_the_permission_is_blocked(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $employee = User::create([
            'name' => 'Employee',
            'email' => 'employee@example.com',
            'password' => bcrypt('secret'),
            'role_id' => Role::where('slug', 'employee')->value('id'),
            'status' => 'active',
        ]);

        $this->actingAs($employee)->get(route('stock-adjustments.index'))->assertForbidden();
    }
}
