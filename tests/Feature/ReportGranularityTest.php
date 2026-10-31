<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportGranularityTest extends TestCase
{
    use RefreshDatabase;

    public function test_time_filters_show_their_own_detail_and_keep_zero_intervals(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->travelTo(Carbon::parse('2026-10-15 16:00:00'));
        $this->actingAs(User::where('role', 'manager')->firstOrFail());
        foreach ([['early', '09:30:00', 'delivered', 100000], ['late', '15:45:00', 'pending', 200000]] as [$code,$time,$status,$total]) {
            $order = Order::create(['code' => $code, 'user_id' => User::where('role', 'customer')->firstOrFail()->id, 'recipient_name' => 'Test', 'phone' => '0912345678', 'shipping_address' => 'Test address', 'subtotal' => $total, 'total' => $total, 'status' => $status, 'payment_method' => 'cod']);
            $order->forceFill(['created_at' => '2026-10-15 '.$time])->save();
        }
        $this->get(route('manager.dashboard', ['period' => 'today', 'metric' => 'orders']))->assertOk()->assertViewHas('granularity', 'hour')->assertViewHas('chartPoints', fn ($p) => count($p) === 24 && $p[9]['value'] === 1 && $p[15]['value'] === 1 && $p[0]['value'] === 0);
        $this->get(route('manager.dashboard', ['period' => 'today', 'metric' => 'sales']))->assertOk()->assertViewHas('chartPoints', fn ($p) => $p[9]['value'] === 100000 && $p[15]['value'] === 0);
        $this->get(route('manager.dashboard', ['period' => 'week']))->assertOk()->assertViewHas('granularity', 'day')->assertViewHas('chartPoints', fn ($p) => count($p) === 7 && $p[0]['label'] === '09/10' && $p[6]['label'] === '15/10');
        $this->get(route('manager.dashboard', ['period' => 'month']))->assertOk()->assertViewHas('chartPoints', fn ($p) => count($p) === 31 && $p[14]['value'] === 100000);
        $this->get(route('manager.dashboard', ['period' => 'custom', 'from' => '2026-10-01', 'to' => '2026-10-03']))->assertOk()->assertViewHas('granularity', 'day')->assertViewHas('chartPoints', fn ($p) => count($p) === 3);
        $this->get(route('manager.dashboard', ['period' => 'custom', 'from' => '2026-01-01', 'to' => '2026-10-31']))->assertOk()->assertViewHas('granularity', 'month')->assertViewHas('chartPoints', fn ($p) => count($p) === 10);
        $this->get(route('manager.dashboard', ['period' => 'year']))->assertOk()->assertViewHas('chartPoints', fn ($p) => count($p) === 12 && $p[0]['label'] === 'January');
        $this->travelBack();
    }
}
