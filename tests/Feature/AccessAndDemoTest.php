<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AccessAndDemoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_admin_can_sign_in_with_admin_username_and_new_demo_password(): void
    {
        $this->post(route('login.submit'), ['email' => 'admin', 'password' => 'admin123'])
            ->assertRedirect(route('system.dashboard'));
        $this->assertAuthenticatedAs(User::where('email', 'admin@nest.test')->firstOrFail());
        $this->post(route('logout'));
        $this->post(route('login.submit'), ['email' => 'admin', 'password' => 'Password@123'])
            ->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_registration_cannot_escalate_role_and_customer_can_log_back_in(): void
    {
        $data = ['name' => 'Khách mới', 'email' => 'new@nest.test', 'password' => 'Password123',
            'password_confirmation' => 'Password123', 'role' => 'admin'];
        $this->post(route('register.submit'), $data + ['phone'=>'0912345678'])->assertRedirect(route('shop.home'));
        $user = User::where('email', $data['email'])->firstOrFail();
        $this->assertSame('customer', $user->role);
        $this->assertTrue(Hash::check($data['password'], $user->password));
        $this->assertAuthenticatedAs($user);
        $this->post(route('logout'))->assertRedirect();
        $this->assertGuest();
        $this->post(route('login.submit'), ['email' => $data['email'], 'password' => 'wrong'])->assertSessionHasErrors('email');
        $this->assertGuest();
        $this->post(route('login.submit'), ['email' => $data['email'], 'password' => $data['password']])->assertRedirect();
        $this->assertAuthenticatedAs($user);
    }

    public function test_customers_are_forbidden_from_all_admin_read_and_write_actions(): void
    {
        $this->actingAs(User::where('email', 'customer@nest.test')->firstOrFail());
        $product = Product::firstOrFail();
        $category = Category::firstOrFail();
        foreach (['manager.dashboard', 'manager.products.index', 'manager.products.create', 'manager.categories.index', 'manager.orders.index'] as $name) {
            $this->get(route($name))->assertForbidden();
        }
        $this->get(route('manager.products.edit', $product))->assertForbidden();
        $this->post(route('manager.products.store'), [])->assertForbidden();
        $this->put(route('manager.products.update', $product), [])->assertForbidden();
        $this->delete(route('manager.products.destroy', $product))->assertForbidden();
        $this->post(route('manager.categories.store'), [])->assertForbidden();
        $this->put(route('manager.categories.update', $category), [])->assertForbidden();
        $this->delete(route('manager.categories.destroy', $category))->assertForbidden();
    }

    public function test_guest_must_sign_in_and_admin_cannot_use_customer_checkout(): void
    {
        foreach (['checkout.create', 'orders.index'] as $name) {
            $this->get(route($name))->assertRedirect(route('login'));
        }
        $this->post(route('checkout.store'), [])->assertRedirect(route('login'));
        $this->actingAs(User::where('email', 'manager@nest.test')->firstOrFail());
        $this->get(route('checkout.create'))->assertForbidden();
        $this->post(route('checkout.store'), [])->assertForbidden();
        $this->get(route('orders.index'))->assertForbidden();
    }

    public function test_cancel_restocks_once_and_invalid_status_changes_are_rejected(): void
    {
        $admin = User::where('email', 'manager@nest.test')->firstOrFail();
        $customer = User::where('email', 'customer@nest.test')->firstOrFail();
        $product = Product::firstOrFail();
        $stock = $product->stock;
        $this->actingAs($customer)->withSession(['cart' => [$product->id => 2]])->get(route('checkout.create'));
        $this->post(route('checkout.store'), ['checkout_token' => session('checkout_token'), 'recipient_name' => 'Khách demo',
            'phone' => '0912345678', 'shipping_address' => '12 Nguyễn Trãi, TP Hồ Chí Minh'])->assertSessionHasNoErrors();
        $order = Order::firstOrFail();
        $this->get(route('orders.index'))->assertSee($order->code);
        $this->get(route('orders.show', $order))->assertOk();
        $this->get(route('manager.orders.show', $order))->assertForbidden();
        $this->patch(route('manager.orders.update', $order), ['status' => 'cancelled'])->assertForbidden();
        $this->actingAs($admin);
        $this->patch(route('manager.orders.update', $order), ['status' => 'delivered'])->assertSessionHasErrors('status');
        $this->assertSame($stock - 2, $product->fresh()->stock);
        $this->patch(route('manager.orders.update', $order), ['status' => 'cancelled'])->assertRedirect();
        $this->assertSame($stock, $product->fresh()->stock);
        $this->patch(route('manager.orders.update', $order), ['status' => 'cancelled'])->assertSessionHasErrors('status');
        $this->assertSame($stock, $product->fresh()->stock);
        $this->delete(route('manager.products.destroy', $product))->assertRedirect();
        $this->assertDatabaseHas('products', ['id' => $product->id, 'is_active' => false]);
        $this->assertDatabaseHas('order_items', ['order_id' => $order->id, 'product_name' => $product->name]);
    }

    public function test_seeder_preserves_edited_accounts_categories_and_products(): void
    {
        $user = User::where('email', 'admin@nest.test')->firstOrFail();
        $user->update(['name' => 'Tên đã chỉnh', 'password' => 'NewPassword123']);
        $product = Product::firstOrFail();
        $product->update(['stock' => 3, 'price' => 155000, 'image_path' => 'products/custom.jpg', 'is_active' => false]);
        $category = $product->category;
        $category->update(['name' => 'Danh mục đã chỉnh', 'is_active' => false]);
        $snapshots = [$user->fresh()->getAttributes(), $product->fresh()->getAttributes(), $category->fresh()->getAttributes()];
        $this->seed(DatabaseSeeder::class);
        $this->assertSame($snapshots, [$user->fresh()->getAttributes(), $product->fresh()->getAttributes(), $category->fresh()->getAttributes()]);
        $this->assertDatabaseCount('users', 3);
        $this->assertDatabaseCount('products', 16);
        $this->assertDatabaseCount('categories', 5);
    }

    public function test_store_and_admin_use_local_styles_fonts_and_sample_photos(): void
    {
        $this->get(route('shop.home'))->assertOk()->assertSee('/vendor/bootstrap/bootstrap.min.css')
            ->assertSee('/vendor/fonts/inter/inter.css')->assertDontSee('https://cdn.jsdelivr.net')
            ->assertDontSee('https://fonts.googleapis.com')->assertDontSee('https://images.unsplash.com');
        $this->actingAs(User::where('email', 'manager@nest.test')->firstOrFail())
            ->get(route('manager.products.index'))->assertOk()->assertSee('/vendor/bootstrap/bootstrap.min.css')
            ->assertDontSee('https://images.unsplash.com');
    }
}
