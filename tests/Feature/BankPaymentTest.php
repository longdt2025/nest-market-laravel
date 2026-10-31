<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\PaymentSetting;
use App\Models\Product;
use App\Models\User;
use App\Services\BackupService;
use App\Services\OrderWorkflow;
use App\Support\ProductImages;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class BankPaymentTest extends TestCase
{
    use RefreshDatabase;

    private User $customer;

    private User $manager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        Storage::fake('public');
        Storage::fake('minio');
        $this->customer = User::where('role', 'customer')->firstOrFail();
        $this->manager = User::where('role', 'manager')->firstOrFail();
    }

    private function setting(): PaymentSetting
    {
        Storage::disk('public')->put('products/payment-qr/test.jpg', 'qr-test');

        return PaymentSetting::create(['id' => 1, 'enabled' => true, 'bank_name' => 'BIDV', 'account_number' => '0012345678', 'account_holder' => 'TEST ACCOUNT', 'qr_path' => 'products/payment-qr/test.jpg']);
    }

    private function order(array $extra = []): Order
    {
        return Order::create(array_replace(['code' => 'TEST'.Str::random(6), 'user_id' => $this->customer->id, 'recipient_name' => 'Khách kiểm thử', 'phone' => '0912345678', 'shipping_address' => 'Địa chỉ kiểm thử', 'subtotal' => 200000, 'shipping_fee' => 0, 'total' => 200000, 'payment_method' => 'bank_transfer', 'status' => 'pending', 'payment_status' => 'unpaid'], $extra));
    }

    public function test_manager_can_upload_qr_and_customer_cannot_change_settings(): void
    {
        $this->actingAs($this->customer)->get(route('manager.payments.settings'))->assertForbidden();
        $this->actingAs($this->manager)->put(route('manager.payments.settings.update'), ['enabled' => 1, 'bank_name' => 'BIDV', 'account_number' => '0012345678', 'account_holder' => 'TEST ACCOUNT', 'qr' => UploadedFile::fake()->image('qr.png', 300, 300)])->assertSessionHasNoErrors();
        $setting = PaymentSetting::active();
        $this->assertNotNull($setting);
        Storage::disk('minio_media')->assertExists($setting->qr_path);
        $this->put(route('manager.payments.settings.update'), ['enabled' => 1, 'bank_name' => 'BIDV', 'account_number' => '0012345678', 'account_holder' => 'TEST', 'qr' => UploadedFile::fake()->create('qr.svg', 1, 'image/svg+xml')])->assertSessionHasErrors('qr');
    }

    public function test_checkout_snapshots_bank_details_and_retries_do_not_duplicate_orders(): void
    {
        $setting = $this->setting();
        $product = Product::firstOrFail();
        $token = (string) Str::uuid();
        $payload = ['checkout_token' => $token, 'recipient_name' => 'Khách kiểm thử', 'phone' => '0912345678', 'shipping_address' => 'Địa chỉ kiểm thử giao hàng', 'payment_method' => 'bank_transfer'];
        $this->actingAs($this->customer)->withSession(['checkout_token' => $token, 'cart' => [$product->id => 1]])->post(route('checkout.store'), $payload)->assertSessionHasNoErrors();
        $order = Order::firstOrFail();
        $this->assertSame('bank_transfer', $order->payment_method);
        $this->assertSame('unpaid', $order->payment_status);
        $this->assertSame($setting->qr_path, $order->payment_qr_path);
        $setting->update(['account_number' => '999999']);
        $this->assertSame('0012345678', $order->fresh()->payment_details['account_number']);
        $this->post(route('checkout.store'), $payload)->assertSessionHasNoErrors();
        $this->assertDatabaseCount('orders', 1);
    }

    public function test_replacing_qr_uses_minio_and_keeps_old_order_instructions(): void
    {
        $setting = $this->setting();
        $oldPath = $setting->qr_path;
        $order = $this->order(['payment_qr_path' => $oldPath]);
        $this->actingAs($this->manager)->put(route('manager.payments.settings.update'), [
            'enabled' => 1, 'bank_name' => 'BIDV', 'account_number' => '0012345678',
            'account_holder' => 'TEST ACCOUNT', 'qr' => UploadedFile::fake()->image('new-qr.png'),
        ])->assertSessionHasNoErrors()->assertSessionHas('success');
        $newPath = $setting->fresh()->qr_path;
        $this->assertStringStartsWith('products/minio/', $newPath);
        Storage::disk('minio_media')->assertExists($newPath);
        Storage::disk('public')->assertExists($oldPath);
        $this->assertSame($oldPath, $order->fresh()->payment_qr_path);
        $this->get(route('manager.payments.settings'))->assertOk()->assertSee(ProductImages::url($newPath), false);
    }

    public function test_qr_storage_failure_is_a_form_error_and_preserves_old_settings(): void
    {
        $setting = $this->setting();
        $before = $setting->fresh()->getAttributes();
        Storage::shouldReceive('disk')->with('minio_media')->andThrow(new \RuntimeException('Storage unavailable'));
        $this->actingAs($this->manager)->put(route('manager.payments.settings.update'), [
            'enabled' => 1, 'bank_name' => 'New bank', 'account_number' => '999999',
            'account_holder' => 'NEW ACCOUNT', 'qr' => UploadedFile::fake()->image('qr.png'),
        ])->assertSessionHasErrors('qr');
        $this->assertSame($before, $setting->fresh()->getAttributes());
    }

    public function test_disabled_transfer_rejects_checkout_without_decrementing_stock(): void
    {
        $product = Product::firstOrFail();
        $stock = $product->stock;
        $token = (string) Str::uuid();
        $this->actingAs($this->customer)->withSession(['checkout_token' => $token, 'cart' => [$product->id => 1]])->post(route('checkout.store'), ['checkout_token' => $token, 'recipient_name' => 'Khách kiểm thử', 'phone' => '0912345678', 'shipping_address' => 'Địa chỉ kiểm thử giao hàng', 'payment_method' => 'bank_transfer'])->assertSessionHasErrors('payment_method');
        $this->assertSame($stock, $product->fresh()->stock);
        $this->assertDatabaseCount('orders', 0);
    }

    public function test_customer_report_does_not_mark_paid_and_is_idempotent(): void
    {
        $order = $this->order();
        $this->actingAs($this->customer)->post(route('orders.payment.report', $order))->assertSessionHasNoErrors();
        $this->post(route('orders.payment.report', $order))->assertSessionHasNoErrors();
        $this->assertSame('pending_review', $order->fresh()->payment_status);
        $this->assertNull($order->fresh()->paid_at);
        $this->assertSame(1, $order->events()->count());
        $this->get(route('orders.payment.status', $order))->assertOk()->assertJsonPath('status', 'pending_review');
        $other = User::create(['name' => 'Other', 'email' => 'other@test.test', 'password' => 'Password@123', 'role' => 'customer']);
        $this->actingAs($other)->post(route('orders.payment.report', $order))->assertForbidden();
        $this->get(route('orders.payment.status', $order))->assertForbidden();
        $this->post(route('manager.orders.payment.verify', $order), ['action' => 'confirm', 'payment_note' => 'test'])->assertForbidden();
    }

    public function test_manager_confirmation_unlocks_shipping_and_paid_cancellation_requires_refund(): void
    {
        $order = $this->order(['status' => 'confirmed', 'payment_status' => 'pending_review']);
        $workflow = app(OrderWorkflow::class);
        try {
            $workflow->changeStatus($order, 'shipping', $this->manager);
            $this->fail('Unpaid shipping must fail');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('payment', $e->errors());
        }
        $this->actingAs($this->manager)->post(route('manager.orders.payment.verify', $order), ['action' => 'confirm', 'payment_note' => 'Đã kiểm tra giao dịch TEST'])->assertSessionHasNoErrors();
        $this->post(route('manager.orders.payment.verify', $order), ['action' => 'confirm', 'payment_note' => 'Gửi lại'])->assertSessionHasNoErrors();
        $this->assertSame('paid', $order->fresh()->payment_status);
        $this->assertSame(1, $order->events()->count());
        $workflow->changeStatus($order, 'cancelled', $this->manager, 'Đổi nhu cầu');
        $this->assertSame('refund_required', $order->fresh()->payment_status);
        $this->post(route('manager.orders.payment.verify', $order), ['action' => 'refunded', 'payment_note' => 'Đã kiểm tra và hoàn giao dịch TEST'])->assertSessionHasNoErrors();
        $this->assertSame('refunded', $order->fresh()->payment_status);
    }

    public function test_cancelled_order_cannot_report_payment(): void
    {
        $order = $this->order(['status' => 'cancelled']);
        $this->actingAs($this->customer)->post(route('orders.payment.report', $order))->assertSessionHasErrors('payment');
        $this->assertSame('unpaid', $order->fresh()->payment_status);
    }

    public function test_manager_quick_actions_need_no_note_and_keep_an_audit_trail(): void
    {
        $order = $this->order(['payment_status' => 'pending_review']);
        $this->actingAs($this->manager)->get(route('manager.orders.show', $order))
            ->assertOk()->assertSee('Xác nhận đơn')->assertSee('Xác nhận đã nhận')
            ->assertDontSee('Chọn trạng thái mới')->assertDontSee('<select name="action"', false);
        $this->post(route('manager.orders.payment.verify', $order), ['action' => 'reject'])->assertSessionHasNoErrors();
        $this->assertSame('unpaid', $order->fresh()->payment_status);
        $this->post(route('manager.orders.payment.verify', $order), ['action' => 'confirm'])->assertSessionHasNoErrors();
        $this->assertSame('paid', $order->fresh()->payment_status);
        $event = $order->events()->where('type', 'payment')->reorder()->latest('id')->firstOrFail();
        $this->assertSame($this->manager->id, $event->actor_id);
        $this->assertStringContainsString('Quản lý xác nhận', $event->note);
        $this->assertNotNull($order->fresh()->paid_at);
        $this->patch(route('manager.orders.update', $order), ['status' => 'confirmed'])->assertSessionHasNoErrors();
        $this->assertSame('confirmed', $order->fresh()->status);
        $this->patch(route('manager.orders.update', $order), ['status' => 'cancelled'])->assertSessionHasNoErrors();
        $this->post(route('manager.orders.payment.verify', $order), ['action' => 'refunded'])->assertSessionHasNoErrors();
        $this->assertSame('refunded', $order->fresh()->payment_status);
    }

    public function test_backup_preserves_bank_setting_and_order_qr_snapshot(): void
    {
        $setting = $this->setting();
        $order = $this->order(['payment_qr_path' => $setting->qr_path, 'payment_details' => ['bank_name' => 'BIDV']]);
        $admin = User::where('role', 'admin')->firstOrFail();
        $service = app(BackupService::class);
        $backup = $service->create($admin, 'Test QR backup');
        $service->restore($backup, $admin);
        $restored = PaymentSetting::findOrFail(1);
        $this->assertNotSame($setting->qr_path, $restored->qr_path);
        $this->assertSame($restored->qr_path, $order->fresh()->payment_qr_path);
        $this->assertSame('qr-test', Storage::disk('public')->get($restored->qr_path));
    }
}
