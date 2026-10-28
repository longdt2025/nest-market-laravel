<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class CheckoutRegressionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    private function payload(string $token): array
    {
        return ['checkout_token' => $token, 'recipient_name' => 'Khách kiểm thử', 'phone' => '0912345678',
            'shipping_address' => '12 Nguyễn Trãi, TP Hồ Chí Minh'];
    }

    private function customer(): User
    {
        return User::where('email', 'customer@nest.test')->firstOrFail();
    }

    public function test_cart_syncs_reduced_stock_and_can_checkout_the_displayed_quantity(): void
    {
        $product = Product::firstOrFail();
        $product->update(['stock' => 2]);
        $this->actingAs($this->customer())->withSession(['cart' => [$product->id => 5]])
            ->get(route('checkout.create'))->assertOk()->assertSessionHas('cart', [$product->id => 2])
            ->assertSee('Giỏ hàng đã được cập nhật');
        $token = session('checkout_token');
        $this->post(route('checkout.store'), $this->payload($token))->assertSessionHasNoErrors();
        $this->assertDatabaseHas('order_items', ['product_id' => $product->id, 'quantity' => 2]);
        $this->assertSame(0, $product->fresh()->stock);
    }

    public function test_hidden_deleted_out_of_stock_and_disabled_category_items_are_removed_from_session(): void
    {
        $products = Product::take(4)->get();
        $products[0]->update(['is_active' => false]);
        $products[1]->update(['stock' => 0]);
        $products[2]->category->update(['is_active' => false]);
        $cart = $products->mapWithKeys(fn ($product) => [$product->id => 1])->all() + [999999 => 1];
        $valid = Product::where('category_id', '!=', $products[2]->category_id)->firstOrFail();
        $cart[$valid->id] = 1;
        $this->withSession(['cart' => $cart])->get(route('cart.index'))->assertOk()
            ->assertSessionHas('cart', [$valid->id => 1])->assertSee($valid->name);
    }

    public function test_cart_caps_legacy_quantity_and_rejects_addition_beyond_100(): void
    {
        $product = Product::firstOrFail();
        $product->update(['stock' => 300]);
        $this->withSession(['cart' => [$product->id => 150]])->get(route('cart.index'))
            ->assertSessionHas('cart', [$product->id => 100]);
        $this->postJson(route('cart.store', $product), ['quantity' => 1])->assertUnprocessable()
            ->assertSessionHas('cart', [$product->id => 100]);
    }

    public function test_quantity_update_rejects_disabled_category(): void
    {
        $product = Product::firstOrFail();
        $product->category->update(['is_active' => false]);
        $this->withSession(['cart' => [$product->id => 1]])->patchJson(route('cart.update', $product), ['quantity' => 2])
            ->assertUnprocessable();
    }

    public function test_all_unavailable_items_redirect_to_an_empty_cart(): void
    {
        $product = Product::firstOrFail();
        $product->update(['is_active' => false]);
        $this->actingAs($this->customer())->withSession(['cart' => [$product->id => 1]])
            ->get(route('checkout.create'))->assertRedirect(route('cart.index'))->assertSessionHas('cart', []);
    }

    public function test_checkout_token_is_stable_across_tabs_and_rendered_in_form(): void
    {
        $product = Product::firstOrFail();
        $this->actingAs($this->customer())->withSession(['cart' => [$product->id => 1]])
            ->get(route('checkout.create'))->assertOk();
        $token = session('checkout_token');
        $this->get(route('checkout.create'))->assertSee($token)->assertSessionHas('checkout_token', $token);
    }

    public function test_repeated_checkout_returns_same_order_and_does_not_decrement_stock_twice(): void
    {
        $product = Product::firstOrFail();
        $stock = $product->stock;
        $token = (string) Str::uuid();
        $this->actingAs($this->customer())->withSession(['cart' => [$product->id => 2], 'checkout_token' => $token]);
        $this->post(route('checkout.store'), $this->payload($token))->assertSessionHasNoErrors();
        $order = Order::firstOrFail();
        $this->post(route('checkout.store'), $this->payload($token))->assertRedirect(route('orders.show', $order));
        $this->assertDatabaseCount('orders', 1);
        $this->assertDatabaseCount('order_items', 1);
        $this->assertSame($stock - 2, $product->fresh()->stock);
    }

    public function test_retry_does_not_clear_a_new_cart_or_its_checkout_token(): void
    {
        $product = Product::firstOrFail();
        $token = (string) Str::uuid();
        $this->actingAs($this->customer())->withSession(['cart' => [$product->id => 1], 'checkout_token' => $token]);
        $this->post(route('checkout.store'), $this->payload($token));
        $newToken = (string) Str::uuid();
        $this->withSession(['cart' => [$product->id => 3], 'checkout_token' => $newToken])
            ->post(route('checkout.store'), $this->payload($token))
            ->assertSessionHas('cart', [$product->id => 3])->assertSessionHas('checkout_token', $newToken);
        $this->assertDatabaseCount('orders', 1);
    }

    public function test_forged_or_missing_checkout_token_cannot_create_order(): void
    {
        $product = Product::firstOrFail();
        $this->actingAs($this->customer())->withSession(['cart' => [$product->id => 1], 'checkout_token' => (string) Str::uuid()]);
        $this->post(route('checkout.store'), $this->payload((string) Str::uuid()))->assertSessionHasErrors('checkout_token');
        $payload = $this->payload('');
        unset($payload['checkout_token']);
        $this->post(route('checkout.store'), $payload)->assertSessionHasErrors('checkout_token');
        $this->assertDatabaseCount('orders', 0);
    }

    public function test_another_customers_checkout_token_does_not_expose_their_order(): void
    {
        $other = User::create(['name' => 'Other customer', 'email' => 'other@test.invalid', 'password' => 'Password123']);
        $token = (string) Str::uuid();
        $order = Order::create(['code' => 'NS-PRIVATE', 'checkout_token' => $token, 'user_id' => $other->id,
            'recipient_name' => 'Other customer', 'phone' => '0912345678', 'shipping_address' => 'Private address',
            'subtotal' => 100000, 'shipping_fee' => 30000, 'total' => 130000]);
        $product = Product::firstOrFail();
        $stock = $product->stock;
        $this->actingAs($this->customer())->withSession(['cart' => [$product->id => 1], 'checkout_token' => (string) Str::uuid()])
            ->post(route('checkout.store'), $this->payload($token))->assertSessionHasErrors('checkout_token');
        $this->get(route('orders.index'))->assertDontSee($order->code);
        $this->assertDatabaseCount('orders', 1);
        $this->assertSame($stock, $product->fresh()->stock);
    }

    public function test_stock_changes_after_checkout_page_do_not_create_a_partial_order(): void
    {
        $products = Product::take(2)->get();
        $stock = $products[0]->stock;
        $this->actingAs($this->customer())->withSession(['cart' => $products->mapWithKeys(fn ($p) => [$p->id => 2])->all()])
            ->get(route('checkout.create'))->assertOk();
        $token = session('checkout_token');
        $products[1]->update(['stock' => 0]);
        $this->post(route('checkout.store'), $this->payload($token))->assertSessionHasErrors('cart');
        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('order_items', 0);
        $this->assertSame($stock, $products[0]->fresh()->stock);
    }

    public function test_order_totals_use_database_price_instead_of_submitted_values(): void
    {
        $product = Product::firstOrFail();
        $token = (string) Str::uuid();
        $this->actingAs($this->customer())->withSession(['cart' => [$product->id => 3], 'checkout_token' => $token])
            ->post(route('checkout.store'), $this->payload($token) + ['subtotal' => 1, 'total' => 1, 'shipping_fee' => 0, 'status' => 'delivered'])
            ->assertSessionHasNoErrors();
        $this->assertDatabaseHas('orders', ['subtotal' => $product->price * 3, 'shipping_fee' => 0, 'total' => $product->price * 3, 'status' => 'pending']);
    }
}
