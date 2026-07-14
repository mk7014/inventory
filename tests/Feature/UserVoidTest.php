<?php

namespace Tests\Feature;

use App\Models\DarazAccount;
use App\Models\Expense;
use App\Models\Product;
use App\Models\Role;
use App\Models\Sale;
use App\Models\User;
use App\Services\BalanceService;
use App\Services\DashboardService;
use App\Support\VoidedUsers;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Voiding a user removes them from the books without destroying anything: their
 * sales, purchases and expenses stop counting in every calculation, the records stay
 * on disk for audit, stock is untouched, and the whole thing is reversible.
 */
class UserVoidTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        VoidedUsers::flush();
        Cache::flush();
    }

    private function user(string $slug, string $name, string $email): User
    {
        return User::create([
            'name' => $name,
            'email' => $email,
            'password' => bcrypt('secret'),
            'role_id' => Role::where('slug', $slug)->value('id'),
            'status' => 'active',
        ]);
    }

    private function admin(): User
    {
        $this->seed(RolePermissionSeeder::class);

        return $this->user('admin', 'Admin', 'admin@example.com');
    }

    private function deliveredSale(User $seller, int $quantity = 2, float $price = 200): Sale
    {
        $product = Product::firstOrCreate(['name' => 'Widget']);
        $product->forceFill(['current_stock' => 50, 'booked_stock' => 0])->save();

        $account = DarazAccount::firstOrCreate(
            ['account_name' => 'A'],
            ['shop_name' => 'S', 'status' => 'active'],
        );

        return Sale::create([
            'daraz_account_id' => $account->id,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'selling_price' => $price,
            'quantity' => $quantity,
            'source' => 'stock',
            'status' => 'delivered',
            'stock_state' => 'delivered',
            'delivered_quantity' => $quantity,
            'sold_date' => now()->toDateString(),
            'created_by' => $seller->id,
        ]);
    }

    private function overview(): array
    {
        VoidedUsers::flush();
        Cache::flush();

        return app(DashboardService::class)->overview(
            Carbon::parse('2000-01-01'), now(), null
        );
    }

    public function test_voiding_removes_the_users_sales_expenses_and_wallet_from_every_calculation(): void
    {
        $admin = $this->admin();
        $employee = $this->user('employee', 'Store Manager', 'sm@example.com');

        $this->deliveredSale($employee, 2, 500);   // 1000 revenue
        DB::transaction(fn () => app(BalanceService::class)->credit($employee, 900, null, $admin->id));

        Expense::create([
            'user_id' => $employee->id,
            'category' => 'Transport',
            'description' => 'Van',
            'amount' => 100,
            'expense_date' => now()->toDateString(),
            'created_by' => $admin->id,
        ]);

        $before = $this->overview();
        $this->assertSame(1000.0, $before['profit']['revenue']);
        $this->assertSame(1, $before['sales']['orders']);
        $this->assertSame(900.0, $before['funds']['total']);
        $this->assertSame(100.0, $before['profit']['operating_expenses']);

        $this->actingAs($admin)
            ->post(route('users.void', $employee))
            ->assertSessionHasNoErrors();

        $after = $this->overview();
        $this->assertSame(0.0, $after['profit']['revenue']);
        $this->assertSame(0, $after['sales']['orders']);
        $this->assertSame(0.0, $after['funds']['total']);
        $this->assertSame(0.0, $after['profit']['operating_expenses']);
        $this->assertSame(0.0, $after['profit']['net_profit']);
    }

    public function test_voiding_keeps_the_records_and_does_not_touch_stock(): void
    {
        $admin = $this->admin();
        $employee = $this->user('employee', 'Store Manager', 'sm@example.com');
        $sale = $this->deliveredSale($employee);

        $stockBefore = Product::sum('current_stock');

        $this->actingAs($admin)->post(route('users.void', $employee));

        // Nothing was destroyed, and the goods really did move — stock stands.
        $this->assertNotNull(User::find($employee->id));
        $this->assertNotNull(Sale::find($sale->id));
        $this->assertSame($stockBefore, Product::sum('current_stock'));
        $this->assertTrue($employee->fresh()->isVoided());
    }

    public function test_restoring_puts_the_user_back_into_the_books(): void
    {
        $admin = $this->admin();
        $employee = $this->user('employee', 'Store Manager', 'sm@example.com');
        $this->deliveredSale($employee, 2, 500);

        $this->actingAs($admin)->post(route('users.void', $employee));
        $this->assertSame(0.0, $this->overview()['profit']['revenue']);

        $this->actingAs($admin)
            ->post(route('users.restore', $employee))
            ->assertSessionHasNoErrors();

        $this->assertFalse($employee->fresh()->isVoided());
        $this->assertSame(1000.0, $this->overview()['profit']['revenue']);
    }

    public function test_a_voided_user_cannot_log_in(): void
    {
        $admin = $this->admin();
        $employee = $this->user('employee', 'Store Manager', 'sm@example.com');

        $this->actingAs($admin)->post(route('users.void', $employee));

        // Drop the admin's session first — /login is behind guest middleware.
        auth()->logout();

        $this->post(route('login'), [
            'email' => 'sm@example.com',
            'password' => 'secret',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_company_records_with_no_author_are_never_dropped(): void
    {
        $admin = $this->admin();
        $employee = $this->user('employee', 'Store Manager', 'sm@example.com');

        // A sale whose creator was detached (created_by IS NULL) belongs to the company.
        $orphan = $this->deliveredSale($admin, 2, 300);
        $orphan->forceFill(['created_by' => null])->save();

        $this->actingAs($admin)->post(route('users.void', $employee));

        // `created_by NOT IN (...)` is NULL for a NULL column — the null-safe filter
        // in VoidedUsers must keep this 600 in revenue rather than silently dropping it.
        $this->assertSame(600.0, $this->overview()['profit']['revenue']);
    }

    public function test_you_cannot_void_yourself_or_the_last_admin(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->post(route('users.void', $admin))
            ->assertSessionHasErrors('void');

        $this->assertFalse($admin->fresh()->isVoided());
    }

    public function test_an_employee_without_the_permission_cannot_void(): void
    {
        $this->admin();
        $employee = $this->user('employee', 'Store Manager', 'sm@example.com');
        $target = $this->user('employee', 'Other', 'other@example.com');

        $this->actingAs($employee)
            ->post(route('users.void', $target))
            ->assertForbidden();

        $this->assertFalse($target->fresh()->isVoided());
    }
}
