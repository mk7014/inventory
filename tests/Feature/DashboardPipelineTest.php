<?php

namespace Tests\Feature;

use App\Models\DarazAccount;
use App\Models\Product;
use App\Models\Requisition;
use App\Models\RequisitionItem;
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
 * The product journey (asked for → bought → sold → left in stock), the
 * pending-delivery card, and the who/what-for breakdowns behind the money cards.
 *
 * The rule every test here enforces: a card and the drill-down that explains it must
 * add up to the same number. A drill-down that disagrees with its card is a lie.
 */
class DashboardPipelineTest extends TestCase
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

    private function employee(string $name, string $email): User
    {
        return User::create([
            'name' => $name,
            'email' => $email,
            'password' => bcrypt('secret'),
            'role_id' => Role::where('slug', 'employee')->value('id'),
            'status' => 'active',
        ]);
    }

    /** Pass $stock only when you mean to set it — otherwise existing stock is left alone. */
    private function product(?int $stock = null): Product
    {
        $product = Product::firstOrCreate(['name' => 'Widget']);

        // A freshly created model has no current_stock attribute yet (the 0 default lives
        // in the schema, not on the instance), so coalesce it rather than writing NULL.
        $product->forceFill([
            'current_stock' => $stock ?? (int) $product->current_stock,
            'booked_stock' => 0,
            'default_purchase_price' => 100,
        ])->save();

        return $product;
    }

    /** A requisition line for $quantity units, optionally already bought. */
    private function requisitionLine(User $employee, int $quantity, bool $purchased): RequisitionItem
    {
        $requisition = Requisition::create([
            'requisition_number' => 'REQ-'.uniqid(),
            'employee_id' => $employee->id,
            'total_amount' => $quantity * 100,
            'status' => 'approved',
            'requested_at' => now(),
        ]);

        return RequisitionItem::create([
            'requisition_id' => $requisition->id,
            'item_type' => 'product',
            'product_id' => $this->product()->id,
            'product_name' => 'Widget',
            'quantity' => $quantity,
            'purchase_price' => 100,
            'subtotal' => $quantity * 100,
            'purchased_at' => $purchased ? now() : null,
        ]);
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
            'stock_state' => in_array($status, ['delivered', 'returned'], true) ? $status : 'none',
            'delivered_quantity' => in_array($status, ['delivered', 'returned'], true) ? $quantity : 0,
            'returned_quantity' => $returned,
            'sold_date' => now()->toDateString(),
            'created_by' => $seller->id,
        ]);
    }

    private function service(): DashboardService
    {
        return app(DashboardService::class);
    }

    private function overview(): array
    {
        Cache::flush();

        return $this->service()->overview(Carbon::parse('2000-01-01'), now(), null);
    }

    public function test_the_product_journey_counts_add_up(): void
    {
        $admin = $this->admin();
        $this->product(stock: 12);

        $this->requisitionLine($admin, 10, purchased: true);   // asked for + bought
        $this->requisitionLine($admin, 4, purchased: false);   // asked for, still to buy
        $this->sale($admin, 'delivered', 3, 500);              // sold and kept

        $pipeline = $this->overview()['pipeline'];

        $this->assertSame(14, $pipeline['requested']);          // 10 + 4
        $this->assertSame(10, $pipeline['purchased']);
        $this->assertSame(4, $pipeline['awaiting_purchase']);
        $this->assertSame(3, $pipeline['sold']);
        $this->assertSame(12, $pipeline['stock']);

        // Asked for = already bought + still to buy. This must never drift.
        $this->assertSame(
            $pipeline['requested'],
            $pipeline['purchased_via_requisition'] + $pipeline['awaiting_purchase'],
        );

        // Stock is valued at cost: 12 × 100.
        $this->assertSame(1200.0, $pipeline['stock_value']);
    }

    public function test_a_returned_unit_is_not_counted_as_sold(): void
    {
        $admin = $this->admin();
        $this->product();

        // Sold 5, customer sent 2 back → only 3 were really sold.
        $this->sale($admin, 'returned', 5, 200, returned: 2);

        $this->assertSame(3, $this->overview()['pipeline']['sold']);
    }

    public function test_pending_delivery_holds_orders_still_on_the_way_only(): void
    {
        $admin = $this->admin();
        $this->product();

        $this->sale($admin, 'pending', 2, 100);      // on the way
        $this->sale($admin, 'shipped', 3, 100);      // on the way
        $this->sale($admin, 'delivered', 4, 100);    // arrived — not pending
        $this->sale($admin, 'cancelled', 9, 100);    // finished — nobody is waiting
        $this->sale($admin, 'returned', 1, 100, returned: 1); // finished

        $pending = $this->overview()['pendingDelivery'];

        $this->assertSame(2, $pending['orders']);
        $this->assertSame(5, $pending['quantity']);   // 2 + 3
        $this->assertSame(500.0, $pending['value']);

        // And the drill-down must show exactly those two orders.
        $drill = $this->service()->details('pending_delivery', Carbon::parse('2000-01-01'), now(), null);
        $this->assertCount(2, $drill['rows']);
        $this->assertSame(500.0, $drill['total']);
    }

    public function test_delivered_card_and_its_drilldown_agree_even_with_a_partial_return(): void
    {
        $admin = $this->admin();
        $this->product();

        $this->sale($admin, 'delivered', 2, 500);              // 1000 kept
        $this->sale($admin, 'returned', 5, 200, returned: 2);  // 3 kept = 600

        $overview = $this->overview();
        $drill = $this->service()->details('revenue', Carbon::parse('2000-01-01'), now(), null);

        $this->assertSame(1600.0, $overview['profit']['revenue']);
        $this->assertSame($overview['profit']['revenue'], $drill['total']);
    }

    public function test_investment_and_expense_drilldowns_break_down_by_staff_and_purpose(): void
    {
        $admin = $this->admin();
        $rahim = $this->employee('Rahim', 'rahim@example.com');
        $karim = $this->employee('Karim', 'karim@example.com');

        DB::transaction(fn () => app(BalanceService::class)->credit($rahim, 600, null, $admin->id, 'credit_payment'));
        DB::transaction(fn () => app(BalanceService::class)->credit($karim, 400, null, $admin->id, 'credit_payment'));
        DB::transaction(fn () => app(BalanceService::class)->debit($rahim, 250, null, $admin->id, 'debit_expense'));

        [$from, $to] = [Carbon::parse('2000-01-01'), now()];
        $overview = $this->overview();

        $funds = $this->service()->details('funds', $from, $to, null);
        $spend = $this->service()->details('spend', $from, $to, null);

        // Totals reconcile with the cards.
        $this->assertSame($overview['funds']['total'], $funds['total']);
        $this->assertSame($overview['spend']['total'], $spend['total']);
        $this->assertSame(1000.0, $funds['total']);
        $this->assertSame(250.0, $spend['total']);

        // Section 1 = who, section 2 = what for.
        [$byStaff, $byPurpose] = $funds['sections'];

        $this->assertSame('Rahim', $byStaff['items'][0]['label']);
        $this->assertSame('৳ 600.00', $byStaff['items'][0]['total']);
        $this->assertSame('Karim', $byStaff['items'][1]['label']);
        $this->assertSame('Requisition Payment', $byPurpose['items'][0]['label']);

        // The staff rows must add up to the card total, or the breakdown is meaningless.
        $this->assertEqualsWithDelta(
            100.0,
            array_sum(array_column($byStaff['items'], 'percent')),
            0.1,
        );
    }

    public function test_every_pipeline_drilldown_is_reachable_over_http(): void
    {
        $admin = $this->admin();
        $this->product();
        $this->requisitionLine($admin, 5, purchased: false);
        $this->sale($admin, 'delivered', 1, 100);

        foreach (['requested', 'purchased', 'awaiting_purchase', 'sold', 'stock', 'pending_delivery'] as $metric) {
            $this->actingAs($admin)
                ->getJson(route('dashboard.details', ['metric' => $metric]))
                ->assertOk()
                ->assertJsonStructure(['title', 'subtitle', 'columns', 'rows', 'total', 'total_label']);
        }
    }
}
