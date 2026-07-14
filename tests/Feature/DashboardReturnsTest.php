<?php

namespace Tests\Feature;

use App\Models\DarazAccount;
use App\Models\Product;
use App\Models\ProductReturn;
use App\Models\Role;
use App\Models\Sale;
use App\Models\User;
use App\Services\DashboardService;
use App\Support\VoidedUsers;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * Returns are their own line in the books.
 *
 * The rule that matters: a returned order is NOT simply erased. If a customer keeps
 * part of an order, that part is still your money — so every figure works from
 * `quantity - returned_quantity` rather than dropping the whole order.
 */
class DashboardReturnsTest extends TestCase
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

    /** Unit cost of 100 via the fallback, so COGS is easy to reason about. */
    private function product(): Product
    {
        $product = Product::firstOrCreate(['name' => 'Widget']);
        $product->forceFill(['current_stock' => 100, 'booked_stock' => 0, 'default_purchase_price' => 100])->save();

        return $product;
    }

    private function sale(User $seller, string $status, int $quantity, float $price, int $returned = 0): Sale
    {
        $account = DarazAccount::firstOrCreate(
            ['account_name' => 'Shop A'],
            ['shop_name' => 'S', 'status' => 'active'],
        );

        return Sale::create([
            'daraz_account_id' => $account->id,
            'product_id' => $this->product()->id,
            'product_name' => 'Widget',
            'selling_price' => $price,
            'quantity' => $quantity,
            'source' => 'stock',
            'status' => $status,
            'stock_state' => $status === 'returned' ? 'returned' : 'delivered',
            'delivered_quantity' => $quantity,
            'returned_quantity' => $returned,
            'sold_date' => now()->toDateString(),
            'created_by' => $seller->id,
        ]);
    }

    private function overview(): array
    {
        Cache::flush();

        return app(DashboardService::class)->overview(Carbon::parse('2000-01-01'), now(), null);
    }

    public function test_a_partial_return_only_removes_the_part_that_came_back(): void
    {
        $admin = $this->admin();

        // Sold 5 at 200 (= 1000). Customer sent 2 back (= 400 refunded), kept 3 (= 600).
        $this->sale($admin, 'returned', 5, 200, returned: 2);

        $profit = $this->overview()['profit'];

        $this->assertSame(1000.0, $profit['gross_sales']);
        $this->assertSame(400.0, $profit['returned_value']);

        // The old rule dropped the whole order and would have reported 0 here.
        $this->assertSame(600.0, $profit['revenue']);

        // Cost applies only to the 3 units the customer kept: 3 × 100.
        $this->assertSame(300.0, $profit['product_cost']);
        $this->assertSame(300.0, $profit['gross_profit']);
    }

    public function test_a_full_return_leaves_nothing_in_revenue(): void
    {
        $admin = $this->admin();
        $this->sale($admin, 'returned', 2, 500, returned: 2);

        $profit = $this->overview()['profit'];

        $this->assertSame(1000.0, $profit['gross_sales']);
        $this->assertSame(1000.0, $profit['returned_value']);
        $this->assertSame(0.0, $profit['revenue']);
        $this->assertSame(0.0, $profit['product_cost']); // nothing was kept, nothing cost you
    }

    public function test_a_damaged_return_is_a_loss_but_a_good_one_is_not(): void
    {
        $admin = $this->admin();

        $damagedSale = $this->sale($admin, 'returned', 1, 500, returned: 1);
        ProductReturn::create([
            'sale_id' => $damagedSale->id,
            'daraz_account_id' => $damagedSale->daraz_account_id,
            'product_id' => $damagedSale->product_id,
            'product_name' => 'Widget',
            'quantity' => 1,
            'condition' => 'damaged',
            'return_date' => now()->toDateString(),
            'created_by' => $admin->id,
        ]);

        $overview = $this->overview();

        // The goods never went back on the shelf: one unit at cost 100 is gone.
        $this->assertSame(100.0, $overview['profit']['damaged_loss']);
        $this->assertSame(1, $overview['returns']['damaged_quantity']);
        $this->assertSame(0, $overview['returns']['good_quantity']);

        // Net profit carries that loss.
        $this->assertSame(-100.0, $overview['profit']['gross_profit']);
    }

    public function test_the_returns_card_and_the_profit_line_can_never_disagree(): void
    {
        $admin = $this->admin();

        // One return recorded through the returns table, one marked returned with no
        // returns row at all (the sales Action-menu path). Both must be counted.
        $withRow = $this->sale($admin, 'returned', 1, 300, returned: 1);
        ProductReturn::create([
            'sale_id' => $withRow->id,
            'daraz_account_id' => $withRow->daraz_account_id,
            'product_id' => $withRow->product_id,
            'product_name' => 'Widget',
            'quantity' => 1,
            'condition' => 'good',
            'return_date' => now()->toDateString(),
            'created_by' => $admin->id,
        ]);

        $this->sale($admin, 'returned', 1, 700, returned: 1); // no returns row

        $overview = $this->overview();

        $this->assertSame(1000.0, $overview['returns']['value']);
        $this->assertSame($overview['profit']['returned_value'], $overview['returns']['value']);

        // And the drill-down must add up to the very same number.
        $drill = app(DashboardService::class)->details('returns', Carbon::parse('2000-01-01'), now(), null);
        $this->assertSame($overview['returns']['value'], $drill['total']);
        $this->assertCount(2, $drill['rows']);
    }

    public function test_each_status_drilldown_matches_its_row_in_the_breakdown(): void
    {
        $admin = $this->admin();
        $this->sale($admin, 'delivered', 2, 500);
        $this->sale($admin, 'returned', 1, 300, returned: 1);
        $this->sale($admin, 'pending', 4, 100);

        $overview = $this->overview();
        $service = app(DashboardService::class);
        [$from, $to] = [Carbon::parse('2000-01-01'), now()];

        foreach ($overview['sales']['statuses'] as $status) {
            $drill = $service->details('orders', $from, $to, null, $status['status']);

            $this->assertSame(
                $status['amount'],
                $drill['total'],
                "The {$status['status']} drill-down does not add up to its breakdown row.",
            );
            $this->assertCount($status['orders'], $drill['rows']);
        }
    }
}
