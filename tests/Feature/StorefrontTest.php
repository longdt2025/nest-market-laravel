<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

class StorefrontTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_guest_can_view_products_and_search(): void
    {
        $this->get(route('shop.home'))->assertOk()->assertSee('NEST MARKET');
        $this->get(route('shop.index', ['q' => 'Kanso']))->assertOk()->assertSee('Ly gốm Kanso');
        $this->get(route('shop.index', ['q' => 'khongcotrongdb']))->assertOk()->assertSee('Chưa tìm thấy sản phẩm');
    }

    public function test_add_to_cart_ajax_without_reload(): void
    {
        $product = Product::where('slug', 'binh-gom-kanso')->firstOrFail();
        $this->postJson(route('cart.store', $product), ['quantity' => 2])->assertOk()->assertJsonPath('cart_count', 2);
        $this->get(route('cart.index'))->assertOk()->assertSee($product->name);
    }

    public function test_new_arrivals_excludes_old_or_hidden_products_and_breaks_date_ties_by_id(): void
    {
        $this->travelTo(now()->startOfSecond());
        Product::query()->update(['created_at' => now()->subDays(45)]);
        $products = Product::orderBy('id')->take(4)->get();
        foreach ([$products[1], $products[2]] as $product) {
            Product::whereKey($product->id)->update(['created_at' => now()]);
        }
        Product::whereKey($products[3]->id)->update(['created_at' => now(), 'is_active' => false]);
        $this->get(route('shop.new'))->assertOk()->assertSee('Hàng mới về — NEST Market')
            ->assertDontSee($products[0]->name)->assertDontSee($products[3]->name)
            ->assertViewHas('products', fn ($page) => $page->pluck('id')->all() === [$products[2]->id, $products[1]->id]);
        $this->assertTrue($products[1]->fresh()->is_new_arrival);
        $this->assertFalse($products[0]->fresh()->is_new_arrival);
        $this->get(route('shop.index', ['q' => $products[0]->name]))->assertSee($products[0]->name);
    }

    public function test_new_arrivals_keeps_its_filters_and_has_an_empty_state(): void
    {
        $this->get(route('shop.new', ['q' => 'Kanso', 'category' => 'trang-tri', 'sort' => 'price_asc']))
            ->assertOk()->assertSee('Ly gốm Kanso')->assertDontSee('Đèn bàn Nami')
            ->assertSee('action="'.route('shop.new').'"', false);
        $this->get(route('shop.new', ['page' => 2]))->assertOk()
            ->assertViewHas('products', fn ($page) => $page->currentPage() === 2 && $page->count() === 4);
        Product::query()->update(['created_at' => now()->subDays(45)]);
        $this->get(route('shop.new'))->assertOk()->assertSee('Chưa có hàng mới');
        $this->get(route('shop.index', ['q' => 'Kanso']))->assertSee('Ly gốm Kanso');
    }

    public function test_checkout_creates_order_and_decrements_stock(): void
    {
        $customer = User::where('email', 'customer@nest.test')->firstOrFail();
        $product = Product::where('slug', 'binh-gom-kanso')->firstOrFail();
        $stock = $product->stock;
        $token = (string) Str::uuid();
        $this->actingAs($customer)->withSession(['cart' => [$product->id => 2], 'checkout_token' => $token])
            ->post(route('checkout.store'), ['checkout_token' => $token, 'recipient_name' => 'Nguyen Van A', 'phone' => '0912345678', 'shipping_address' => '12 Nguyen Trai, Quan 1, TP HCM'])
            ->assertRedirect();
        $order = Order::firstOrFail();
        $this->assertEquals($customer->id, $order->user_id);
        $this->assertEquals($product->price, $order->items()->first()->unit_price);
        $this->assertEquals(2, $order->items()->first()->quantity);
        $this->assertEquals($stock - 2, $product->fresh()->stock);
    }

    public function test_checkout_rejects_insufficient_stock(): void
    {
        $customer = User::where('email', 'customer@nest.test')->firstOrFail();
        $product = Product::where('slug', 'binh-gom-kanso')->firstOrFail();
        $token = (string) Str::uuid();
        $this->actingAs($customer)->withSession(['cart' => [$product->id => 999], 'checkout_token' => $token])
            ->post(route('checkout.store'), ['checkout_token' => $token, 'recipient_name' => 'Nguyen Van A', 'phone' => '0912345678', 'shipping_address' => '12 Nguyen Trai, Quan 1, TP HCM'])
            ->assertSessionHasErrors('cart');
        $this->assertDatabaseCount('orders', 0);
    }

    public function test_external_province_api_proxy(): void
    {
        $customer = User::where('email', 'customer@nest.test')->firstOrFail();
        Cache::forget('nest_provinces_v2');
        Http::fake(['provinces.open-api.vn/*' => Http::response([['name' => 'Thành phố Hà Nội'], ['name' => 'Thành phố Hồ Chí Minh']], 200)]);
        $this->actingAs($customer)->getJson(route('location.provinces'))->assertOk()
            ->assertJsonPath('provinces.0', 'Thành phố Hà Nội');
    }

    public function test_customer_cannot_view_other_customers_order(): void
    {
        $customer = User::where('email', 'customer@nest.test')->firstOrFail();
        $other = User::create(['name' => 'Other', 'email' => 'other@nest.test', 'password' => 'Secret123']);
        $order = Order::create(['code' => 'NS-TEST-0001', 'user_id' => $other->id, 'recipient_name' => 'Other', 'phone' => '0912345678', 'shipping_address' => 'TP HCM, Viet Nam', 'subtotal' => 120000, 'shipping_fee' => 30000, 'total' => 150000]);
        $this->actingAs($customer)->get(route('orders.show', $order))->assertForbidden();
    }
}
