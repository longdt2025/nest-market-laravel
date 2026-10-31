<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use Tests\TestCase;

class OrderWorkspaceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        $this->actingAs(User::where('role', 'manager')->firstOrFail());
    }

    private function order(array $extra = []): Order
    {
        return Order::create(array_replace([
            'code' => 'TEST-'.Order::count(), 'user_id' => User::where('role', 'customer')->firstOrFail()->id,
            'recipient_name' => 'Khách kiểm thử', 'phone' => '0012345678', 'shipping_address' => 'Địa chỉ kiểm thử',
            'subtotal' => 100000, 'shipping_fee' => 30000, 'total' => 130000, 'payment_method' => 'cod', 'status' => 'pending',
        ], $extra));
    }

    public function test_combined_filters_search_and_summary_exclude_cancelled_from_revenue(): void
    {
        Order::query()->delete();
        $this->order(['status' => 'delivered', 'total' => 200000]);
        $this->order(['status' => 'cancelled', 'total' => 500000]);
        $this->order(['total' => 300000]);
        $response = $this->get(route('manager.orders.index', ['q' => '0012345678', 'from' => now()->toDateString(), 'to' => now()->toDateString(), 'min_total' => 200000, 'sort' => 'total', 'direction' => 'asc']));
        $response->assertOk()->assertViewHas('summary', fn ($summary) => (int) $summary->total_value === 1000000 && (int) $summary->delivered_value === 200000 && (int) $summary->processing_value === 300000);
        $response->assertViewHas('orders', fn ($orders) => $orders->pluck('total')->all() === [200000, 300000, 500000]);
        $this->get(route('manager.orders.index', ['status' => 'cancelled']))->assertOk()->assertViewHas('orders', fn ($orders) => $orders->total() === 1)->assertViewHas('statusCounts', fn ($counts) => $counts->sum() === 3);
    }

    public function test_one_sided_filters_and_invalid_ranges(): void
    {
        $this->get(route('manager.orders.index', ['to' => '2099-12-31', 'max_total' => 1000000]))->assertOk();
        $this->get(route('manager.orders.index', ['from' => '2026-10-31', 'to' => '2026-10-01']))->assertSessionHasErrors('to');
        $this->get(route('manager.orders.index', ['min_total' => 200, 'max_total' => 100]))->assertSessionHasErrors('max_total');
        $this->get(route('manager.orders.index', ['sort' => 'user_id; DROP TABLE orders']))->assertSessionHasErrors('sort');
    }

    public function test_panel_and_print_render_without_changing_the_order(): void
    {
        $order = $this->order();
        $before = $order->fresh()->getAttributes();
        $this->get(route('manager.orders.show', [$order, 'panel' => 1]))->assertOk()->assertSee('In đơn hàng')->assertDontSee('<!DOCTYPE', false);
        $this->get(route('manager.orders.print', $order))->assertOk()->assertSee('PHIẾU ĐƠN HÀNG')->assertSee('không thay thế hóa đơn giá trị gia tăng');
        $this->assertSame($before, $order->fresh()->getAttributes());
    }

    public function test_selected_excel_export_preserves_phone_and_prevents_formulas(): void
    {
        $order = $this->order(['recipient_name' => '=HYPERLINK("https://example.com")']);
        $this->order(['recipient_name' => 'Không xuất']);
        $response = $this->post(route('manager.orders.export'), ['scope' => 'selected', 'ids' => [$order->id]]);
        $response->assertOk()->assertDownload();
        $path = tempnam(sys_get_temp_dir(), 'nest-order-');
        try {
            file_put_contents($path, $response->streamedContent());
            $book = (new Xlsx)->load($path);
            $sheet = $book->getActiveSheet();
            $this->assertSame(2, $sheet->getHighestRow());
            $this->assertSame($order->code, $sheet->getCell('A2')->getValue());
            $this->assertSame(DataType::TYPE_STRING, $sheet->getCell('C2')->getDataType());
            $this->assertSame('0012345678', $sheet->getCell('D2')->getValue());
            $book->disconnectWorksheets();
        } finally {
            unlink($path);
        }
        $this->post(route('manager.orders.export'), ['scope' => 'selected'])->assertSessionHasErrors('ids');
    }

    public function test_customer_and_system_admin_cannot_access_manager_invoice_tools(): void
    {
        $order = $this->order();
        foreach (['customer', 'admin'] as $role) {
            $this->actingAs(User::where('role', $role)->firstOrFail());
            $this->get(route('manager.orders.show', [$order, 'panel' => 1]))->assertForbidden();
            $this->get(route('manager.orders.print', $order))->assertForbidden();
            $this->post(route('manager.orders.export'), ['scope' => 'filtered'])->assertForbidden();
        }
    }
}
