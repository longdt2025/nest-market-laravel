<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\User;
use App\Services\VietQr;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VietQrTest extends TestCase
{
    use RefreshDatabase;

    public function test_url_uses_only_order_snapshot_and_exact_amount(): void
    {
        $order = new Order(['code' => 'NS-261031-ABCDEFGH', 'payment_method' => 'bank_transfer', 'total' => 1234567, 'payment_details' => ['bank_bin' => '970418', 'account_number' => '0012345678', 'account_holder' => 'TÊN KIỂM THỬ']]);
        $service = new VietQr;
        $url = $service->imageUrl($order);
        $this->assertSame('img.vietqr.io', parse_url($url, PHP_URL_HOST));
        $this->assertSame('/image/970418-0012345678-compact2.svg', parse_url($url, PHP_URL_PATH));
        parse_str(parse_url($url, PHP_URL_QUERY), $params);
        $this->assertSame(['amount' => '1234567', 'addInfo' => 'NS261031ABCDEFGH', 'accountName' => 'TEN KIEM THU'], $params);
        $order->total = 7654321;
        $this->assertStringContainsString('amount=7654321', $service->imageUrl($order));
    }

    public function test_invalid_snapshot_or_cod_falls_back_without_external_url(): void
    {
        $base = ['code' => 'NS-TEST', 'payment_method' => 'bank_transfer', 'total' => 1000, 'payment_details' => ['bank_bin' => '970418', 'account_number' => '0012345678']];
        foreach ([['payment_method' => 'cod'], ['total' => 0], ['payment_details' => []], ['payment_details' => ['bank_bin' => '970418/evil', 'account_number' => '0012345678']], ['payment_details' => ['bank_bin' => '970418', 'account_number' => '123?amount=1']]] as $extra) {
            $this->assertNull((new VietQr)->imageUrl(new Order(array_replace($base, $extra))));
        }
    }

    public function test_customer_page_rejects_amount_tampering_and_enforces_ownership(): void
    {
        $this->seed(DatabaseSeeder::class);
        $customer = User::where('role', 'customer')->firstOrFail();
        $order = Order::create(['code' => 'NS-TEST-QR', 'user_id' => $customer->id, 'recipient_name' => 'Test', 'phone' => '0912345678', 'shipping_address' => 'Địa chỉ kiểm thử', 'subtotal' => 200000, 'total' => 200000, 'payment_method' => 'bank_transfer', 'status' => 'pending', 'payment_qr_path' => 'products/fallback.jpg', 'payment_details' => ['bank_bin' => '970418', 'account_number' => '0012345678', 'account_holder' => 'TEST ACCOUNT']]);
        $this->actingAs($customer)->get(route('orders.show', [$order, 'amount' => 1, 'account_number' => '999999']))->assertOk()->assertSee('amount=200000', false)->assertSee('addInfo=NSTESTQR', false)->assertSee('970418-0012345678-compact2.svg', false)->assertDontSee('amount=1&amp;', false)->assertSee('data-qr-fallback-note', false);
        $other = User::create(['name' => 'Other', 'email' => 'vietqr-other@test.test', 'password' => 'Password@123', 'role' => 'customer']);
        $this->actingAs($other)->get(route('orders.show',$order))->assertForbidden();
    }
}
