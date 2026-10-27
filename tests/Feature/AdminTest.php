<?php
namespace Tests\Feature;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
class AdminTest extends TestCase
{
    use RefreshDatabase;
    protected function setUp(): void { parent::setUp(); $this->seed(DatabaseSeeder::class); }
    public function test_guest_redirected_and_customer_forbidden(): void
    {
        $this->get(route('admin.dashboard'))->assertRedirect(route('login'));
        $user=User::where('email','customer@nest.test')->firstOrFail();
        $this->actingAs($user)->get(route('admin.dashboard'))->assertForbidden();
    }
    public function test_admin_crud_category_and_product(): void
    {
        $admin=User::where('email','admin@nest.test')->firstOrFail();
        $this->actingAs($admin);
        $this->post(route('admin.categories.store'),['name'=>'Chăm sóc cá nhân','is_active'=>'1'])->assertRedirect();
        $category=Category::where('name','Chăm sóc cá nhân')->firstOrFail();
        $this->post(route('admin.products.store'),['name'=>'Kem dưỡng tay','category_id'=>$category->id,'price'=>130000,'stock'=>12,'is_active'=>'1'])->assertRedirect();
        $product=Product::where('name','Kem dưỡng tay')->firstOrFail();
        $this->put(route('admin.products.update',$product),['name'=>'Kem dưỡng tay hoa hồng','category_id'=>$category->id,'price'=>155000,'stock'=>9,'is_active'=>'1'])->assertRedirect();
        $this->assertEquals(155000,$product->fresh()->price);
        $this->delete(route('admin.products.destroy',$product))->assertRedirect();
        $this->delete(route('admin.categories.destroy',$category))->assertRedirect();
        $this->assertDatabaseMissing('categories',['id'=>$category->id]);
    }
    public function test_admin_cancellation_restores_stock(): void
    {
        $admin=User::where('email','admin@nest.test')->firstOrFail();
        $customer=User::where('email','customer@nest.test')->firstOrFail();
        $product=Product::where('slug','binh-gom-kanso')->firstOrFail();
        $stock=$product->stock;
        $order=Order::create(['code'=>'NS-TEST-0002','user_id'=>$customer->id,'recipient_name'=>'Khách demo','phone'=>'0912345678','shipping_address'=>'TP HCM, Viet Nam','subtotal'=>289000,'shipping_fee'=>30000,'total'=>319000,'status'=>'pending']);
        $order->items()->create(['product_id'=>$product->id,'product_name'=>$product->name,'unit_price'=>289000,'quantity'=>1,'line_total'=>289000]);
        $product->decrement('stock');
        $this->actingAs($admin)->patch(route('admin.orders.update',$order),['status'=>'cancelled'])->assertRedirect();
        $this->assertEquals($stock,$product->fresh()->stock);
        $this->assertEquals('cancelled',$order->fresh()->status);
    }
}
