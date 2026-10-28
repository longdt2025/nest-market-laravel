<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderEvent;
use App\Models\Product;
use App\Models\User;
use App\Services\OrderWorkflow;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class CustomerSelfServiceTest extends TestCase
{
    use RefreshDatabase;

    private User $customer;

    private User $admin;

    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        $this->customer = User::where('email', 'customer@nest.test')->firstOrFail();
        $this->admin = User::where('email', 'admin@nest.test')->firstOrFail();
        $this->product = Product::firstOrFail();
    }

    private function delivery(array $extra = []): array
    {
        return array_replace(['recipient_name' => 'Khách kiểm thử', 'phone' => '0912345678',
            'shipping_address' => 'Địa chỉ kiểm thử số 12, TP Hồ Chí Minh', 'note' => null], $extra);
    }

    private function placeOrder(array $extra = []): Order
    {
        $token = (string) Str::uuid();
        $this->actingAs($this->customer)->withSession(['cart' => [$this->product->id => 2], 'checkout_token' => $token])
            ->post(route('checkout.store'), $this->delivery($extra) + ['checkout_token' => $token])->assertSessionHasNoErrors();

        return Order::latest('id')->firstOrFail();
    }

    public function test_customer_can_edit_pending_delivery_without_changing_order_value_or_defaults(): void
    {
        $order = $this->placeOrder();
        $total = $order->total;
        $this->get(route('orders.edit-delivery', $order))->assertOk();
        $this->patch(route('orders.update-delivery', $order), $this->delivery([
            'shipping_address' => 'Địa chỉ mới số 20, TP Hồ Chí Minh', 'status' => 'delivered', 'total' => 1,
        ]))->assertRedirect(route('orders.show', $order))->assertSessionHasNoErrors();
        $order->refresh();
        $this->assertSame('Địa chỉ mới số 20, TP Hồ Chí Minh', $order->shipping_address);
        $this->assertEquals($total, $order->total);
        $this->assertSame('pending', $order->status);
        $this->assertNull($this->customer->fresh()->shipping_address);
        $this->assertSame(['placed', 'delivery_updated'], $order->events()->pluck('type')->all());
        $this->get(route('orders.show', $order))->assertSee('Đã cập nhật thông tin nhận hàng');
    }

    public function test_saving_identical_delivery_does_not_create_duplicate_history(): void
    {
        $order = $this->placeOrder();
        $this->patch(route('orders.update-delivery', $order), $this->delivery())->assertSessionHasNoErrors();
        $this->assertSame(1, $order->events()->count());
    }

    public function test_customer_cancels_pending_order_once_and_reason_is_visible_to_admin(): void
    {
        $stock = $this->product->stock;
        $order = $this->placeOrder();
        $this->assertSame($stock - 2, $this->product->fresh()->stock);
        $this->post(route('orders.cancel', $order), ['cancel_reason' => 'Muốn đổi sang sản phẩm khác'])
            ->assertRedirect(route('orders.show', $order))->assertSessionHasNoErrors();
        $this->assertSame('cancelled', $order->fresh()->status);
        $this->assertSame($stock, $this->product->fresh()->stock);
        $this->assertDatabaseHas('order_events', ['order_id' => $order->id, 'actor_id' => $this->customer->id,
            'actor_role' => 'customer', 'from_status' => 'pending', 'status' => 'cancelled', 'note' => 'Muốn đổi sang sản phẩm khác']);
        $this->post(route('orders.cancel', $order), ['cancel_reason' => 'Gửi lại yêu cầu'])->assertSessionHasErrors('order');
        $this->assertSame($stock, $this->product->fresh()->stock);
        $this->assertSame(2, $order->events()->count());
        $this->actingAs($this->admin)->get(route('admin.orders.show', $order))
            ->assertSee('Muốn đổi sang sản phẩm khác')->assertSee('Khách hàng');
    }

    public function test_invalid_cancellation_reason_or_delivery_leaves_order_untouched(): void
    {
        $order = $this->placeOrder();
        $this->post(route('orders.cancel', $order), [])->assertSessionHasErrors('cancel_reason');
        $this->patch(route('orders.update-delivery', $order), $this->delivery(['phone' => '123']))->assertSessionHasErrors('phone');
        $this->assertSame('pending', $order->fresh()->status);
        $this->assertSame(1, $order->events()->count());
    }

    public function test_customer_cannot_edit_or_cancel_another_customers_order(): void
    {
        $order = $this->placeOrder();
        $other = User::create(['name' => 'Other', 'email' => 'other@test.invalid', 'password' => 'Password123']);
        $this->actingAs($other)->get(route('orders.edit-delivery', $order))->assertForbidden();
        $this->patch(route('orders.update-delivery', $order), $this->delivery())->assertForbidden();
        $this->post(route('orders.cancel', $order), ['cancel_reason' => 'Unauthorized'])->assertForbidden();
        $this->assertSame('pending', $order->fresh()->status);
        $this->assertSame(1, $order->events()->count());
    }

    public function test_admin_confirmation_prevents_later_customer_changes(): void
    {
        $order = $this->placeOrder();
        $this->actingAs($this->admin)->patch(route('admin.orders.update', $order), ['status' => 'confirmed'])->assertSessionHasNoErrors();
        $this->actingAs($this->customer)->get(route('orders.edit-delivery', $order))->assertRedirect(route('orders.show', $order));
        $this->patch(route('orders.update-delivery', $order), $this->delivery(['recipient_name' => 'Late change']))->assertSessionHasErrors('order');
        $this->post(route('orders.cancel', $order), ['cancel_reason' => 'Late cancellation'])->assertSessionHasErrors('order');
        $this->assertSame('confirmed', $order->fresh()->status);
        $this->assertSame('Khách kiểm thử', $order->fresh()->recipient_name);
        $this->assertSame(2, $order->events()->count());
    }

    public function test_workflow_rechecks_database_status_even_if_caller_holds_a_stale_order(): void
    {
        $order = $this->placeOrder();
        Order::whereKey($order->id)->update(['status' => 'confirmed']);
        $this->assertSame('pending', $order->status);
        $caught = false;
        try {
            app(OrderWorkflow::class)->updateDelivery($order, $this->customer, $this->delivery(['recipient_name' => 'Stale request']));
        } catch (ValidationException) {
            $caught = true;
        }
        $this->assertTrue($caught);
        $this->assertSame('Khách kiểm thử', $order->fresh()->recipient_name);
    }

    public function test_admin_status_history_tracks_each_step_with_actor_and_time(): void
    {
        $this->travelTo(now()->startOfSecond());
        $order = $this->placeOrder();
        $this->actingAs($this->admin);
        foreach (['confirmed', 'shipping', 'delivered'] as $status) {
            $this->travel(1)->minutes();
            $this->patch(route('admin.orders.update', $order), ['status' => $status])->assertSessionHasNoErrors();
        }
        $events = $order->events()->get();
        $this->assertSame(['pending', 'confirmed', 'shipping', 'delivered'], $events->pluck('status')->all());
        $this->assertSame(['customer', 'admin', 'admin', 'admin'], $events->pluck('actor_role')->all());
        $this->assertTrue($events[3]->created_at->gt($events[0]->created_at));
        $this->actingAs($this->customer)->get(route('orders.show', $order))->assertSee('Đã giao')->assertDontSee('Sửa thông tin nhận hàng');
    }

    public function test_history_failure_rolls_back_cancellation_and_stock_together(): void
    {
        $order = $this->placeOrder();
        $stock = $this->product->fresh()->stock;
        Event::listen('eloquent.creating: '.OrderEvent::class, function () {
            throw new \RuntimeException('History write failed');
        });
        $caught = false;
        try {
            app(OrderWorkflow::class)->changeStatus($order, 'cancelled', $this->customer, 'Test rollback');
        } catch (\RuntimeException $exception) {
            $caught = true;
            $this->assertSame('History write failed', $exception->getMessage());
        }
        $this->assertTrue($caught);
        $this->assertSame('pending', $order->fresh()->status);
        $this->assertSame($stock, $this->product->fresh()->stock);
        $this->assertSame(1, $order->events()->count());
    }

    public function test_profile_saves_only_allowed_fields_and_prefills_checkout(): void
    {
        $this->actingAs($this->customer)->get(route('profile.edit'))->assertOk();
        $this->patch(route('profile.update'), ['name' => 'Tên tài khoản mới', 'delivery_name' => 'Người nhận mặc định',
            'phone' => '0912345678', 'shipping_address' => 'Địa chỉ mặc định số 33, TP Hồ Chí Minh',
            'role' => 'admin', 'email' => 'hijack@test.invalid'])->assertSessionHasNoErrors();
        $customer = $this->customer->fresh();
        $this->assertSame('customer', $customer->role);
        $this->assertSame('customer@nest.test', $customer->email);
        $this->withSession(['cart' => [$this->product->id => 1]])->get(route('checkout.create'))
            ->assertSee('Người nhận mặc định')->assertSee('Địa chỉ mặc định số 33, TP Hồ Chí Minh');
    }

    public function test_checkout_saves_delivery_only_with_consent_and_profile_changes_do_not_rewrite_orders(): void
    {
        $order = $this->placeOrder();
        $this->assertNull($this->customer->fresh()->shipping_address);
        $second = $this->placeOrder(['save_address' => 1, 'recipient_name' => 'Người nhận đã lưu']);
        $this->assertSame('Người nhận đã lưu', $this->customer->fresh()->delivery_name);
        $this->patch(route('profile.update'), ['name' => 'Tên mới', 'delivery_name' => 'Địa chỉ tiếp theo',
            'phone' => '0987654321', 'shipping_address' => 'Địa chỉ mới số 55, TP Hồ Chí Minh'])->assertSessionHasNoErrors();
        $this->assertSame('Khách kiểm thử', $order->fresh()->recipient_name);
        $this->assertSame('Người nhận đã lưu', $second->fresh()->recipient_name);
    }

    public function test_profile_validation_and_role_middleware_block_invalid_updates(): void
    {
        $this->get(route('profile.edit'))->assertRedirect(route('login'));
        $this->actingAs($this->customer)->patch(route('profile.update'), ['name' => 'Valid', 'phone' => '123', 'shipping_address' => 'Short'])
            ->assertSessionHasErrors(['phone', 'shipping_address']);
        $this->actingAs($this->admin)->get(route('profile.edit'))->assertForbidden();
        $this->patch(route('profile.update'), ['name' => 'Admin'])->assertForbidden();
    }
}
