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
 * Clicking a dashboard card shows the records behind it. The whole point is that the
 * rows reconcile: if the drill-down and the card disagree, the drill-down is a lie.
 */
class DashboardDetailsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        VoidedUsers::flush();
        Cache::flush();
    }

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

    private function sale(User $seller, string $status, int $quantity, float $price): Sale
    {
        $product = Product::firstOrCreate(['name' => 'Widget']);
        $product->forceFill(['current_stock' => 100, 'booked_stock' => 0])->save();

        $account = DarazAccount::firstOrCreate(
            ['account_name' => 'Shop A'],
            ['shop_name' => 'S', 'status' => 'active'],
        );

        return Sale::create([
            'daraz_account_id' => $account->id,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'selling_price' => $price,
            'quantity' => $quantity,
            'source' => 'stock',
            'status' => $status,
            'stock_state' => $status === 'delivered' ? 'delivered' : 'none',
            'delivered_quantity' => $status === 'delivered' ? $quantity : 0,
            'sold_date' => now()->toDateString(),
            'created_by' => $seller->id,
        ]);
    }

    private function service(): DashboardService
    {
        return app(DashboardService::class);
    }

    private function range(): array
    {
        return [Carbon::parse('2000-01-01'), now()];
    }

    public function test_the_sales_drilldown_rows_add_up_to_the_card_figure(): void
    {
        $admin = $this->admin();
        $this->sale($admin, 'delivered', 2, 500);   // 1000, counts
        $this->sale($admin, 'delivered', 1, 300);   // 300,  counts
        $this->sale($admin, 'pending', 5, 900);     // never counts — not delivered

        [$from, $to] = $this->range();

        $card = $this->service()->overview($from, $to, null)['profit']['revenue'];
        $drill = $this->service()->details('revenue', $from, $to, null);

        $this->assertSame(1300.0, $card);
        $this->assertSame($card, $drill['total']);
        $this->assertCount(2, $drill['rows']); // the pending order is absent
    }

    public function test_the_money_given_and_spent_drilldowns_match_their_cards(): void
    {
        $admin = $this->admin();

        DB::transaction(fn () => app(BalanceService::class)->credit($admin, 900, null, $admin->id));

        Expense::create([
            'user_id' => $admin->id,
            'category' => 'Transport',
            'description' => 'Van',
            'amount' => 250,
            'expense_date' => now()->toDateString(),
            'created_by' => $admin->id,
        ]);
        DB::transaction(fn () => app(BalanceService::class)->debit($admin, 250, null, $admin->id, 'debit_expense'));

        [$from, $to] = $this->range();
        $overview = $this->service()->overview($from, $to, null);

        $funds = $this->service()->details('funds', $from, $to, null);
        $spend = $this->service()->details('spend', $from, $to, null);
        $expenses = $this->service()->details('expenses', $from, $to, null);

        $this->assertSame($overview['funds']['total'], $funds['total']);
        $this->assertSame($overview['spend']['total'], $spend['total']);
        $this->assertSame(900.0, $funds['total']);
        $this->assertSame(250.0, $spend['total']);
        $this->assertSame(250.0, $expenses['total']);
    }

    public function test_the_cost_drilldown_matches_the_profit_cards_product_cost(): void
    {
        $admin = $this->admin();

        $product = Product::firstOrCreate(['name' => 'Widget']);
        $product->forceFill(['current_stock' => 100, 'default_purchase_price' => 120])->save();

        $this->sale($admin, 'delivered', 3, 500);

        [$from, $to] = $this->range();

        $card = $this->service()->overview($from, $to, null)['profit']['product_cost'];
        $drill = $this->service()->details('cost', $from, $to, null);

        // No purchase history, so the cost basis falls back to the default price: 3 × 120.
        $this->assertSame(360.0, $card);
        $this->assertSame($card, $drill['total']);
    }

    public function test_the_endpoint_returns_json_and_rejects_an_unknown_metric(): void
    {
        $admin = $this->admin();
        $this->sale($admin, 'delivered', 2, 500);

        $this->actingAs($admin)
            ->getJson(route('dashboard.details', ['metric' => 'revenue']))
            ->assertOk()
            ->assertJsonStructure(['title', 'subtitle', 'columns', 'rows', 'total', 'total_label']);

        $this->actingAs($admin)
            ->getJson(route('dashboard.details', ['metric' => 'nonsense']))
            ->assertStatus(422);
    }

    public function test_a_voided_users_orders_never_appear_in_the_drilldown(): void
    {
        $admin = $this->admin();
        $employee = User::create([
            'name' => 'Store Manager',
            'email' => 'sm@example.com',
            'password' => bcrypt('secret'),
            'role_id' => Role::where('slug', 'employee')->value('id'),
            'status' => 'active',
        ]);

        $this->sale($admin, 'delivered', 1, 400);
        $this->sale($employee, 'delivered', 1, 600);

        [$from, $to] = $this->range();
        $this->assertSame(1000.0, $this->service()->details('revenue', $from, $to, null)['total']);

        $this->actingAs($admin)->post(route('users.void', $employee));
        VoidedUsers::flush();
        Cache::flush();

        $drill = $this->service()->details('revenue', $from, $to, null);
        $this->assertSame(400.0, $drill['total']);
        $this->assertCount(1, $drill['rows']);
    }
}
