<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FurnitureStorefrontTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_combined_room_material_color_budget_and_stock_filters(): void
    {
        $product = Product::where('slug', 'ghe-thu-gian-lyh')->firstOrFail();
        $filters = ['room' => 'living', 'material' => 'Gỗ & vải bọc', 'color' => 'Gỗ tự nhiên', 'min_price' => 1000000, 'max_price' => 2000000, 'in_stock' => 1];
        $this->get(route('shop.index', $filters))->assertOk()
            ->assertViewHas('products', fn ($page) => $page->pluck('id')->all() === [$product->id]);
        $product->update(['stock' => 0]);
        $this->get(route('shop.index', $filters))->assertOk()->assertViewHas('products', fn ($page) => $page->total() === 0);
        unset($filters['in_stock']);
        $this->get(route('shop.index', $filters))->assertSee($product->name)->assertSee('Tạm hết hàng');
    }

    public function test_offers_include_only_visible_products_with_a_real_discount(): void
    {
        $this->get(route('shop.offers'))->assertOk()->assertViewHas('products', fn ($page) => $page->total() === 21 && $page->every(fn ($p) => $p->compare_at_price > $p->price));
        $this->getJson(route('shop.index', ['min_price' => 5000000, 'max_price' => 1000000]))->assertUnprocessable()->assertJsonValidationErrors('max_price');
        $this->getJson(route('shop.index', ['room' => 'unknown']))->assertUnprocessable();
    }

    public function test_product_details_have_local_gallery_and_complete_furniture_specs(): void
    {
        $product = Product::where('slug', 'sofa-2-cho-oslo')->firstOrFail();
        $this->assertCount(2, $product->gallery_urls);
        foreach ($product->gallery_urls as $url) {
            $this->assertStringContainsString('/images/catalog/', $url);
        }
        $this->get(route('shop.show', $product))->assertOk()->assertSee('180 × 79 × 75 cm')->assertSee('Gỗ & vải bọc')
            ->assertSee('12 tháng')->assertSee('Mua ngay')->assertSee('data-gallery-thumb', false);
        $this->get(route('shop.policies'))->assertOk()->assertSee('800.000₫')->assertSee('COD');
    }

    public function test_buy_now_adds_to_cart_and_continues_to_checkout_without_javascript(): void
    {
        $product = Product::where('slug', 'sofa-2-cho-oslo')->firstOrFail();
        $this->post(route('cart.store', $product), ['quantity' => 1, 'buy_now' => 1])->assertRedirect(route('checkout.create'))
            ->assertSessionHas('cart', [$product->id => 1]);
    }

    public function test_admin_can_upload_and_replace_a_gallery_without_losing_its_cover(): void
    {
        Storage::fake('public');
        $this->actingAs(User::where('email', 'manager@nest.test')->firstOrFail());
        $product = Product::firstOrFail();
        $data = ['name' => $product->name, 'category_id' => $product->category_id, 'price' => $product->price, 'stock' => $product->stock, 'is_active' => 1,
            'image' => UploadedFile::fake()->image('cover.png'), 'gallery_images' => [UploadedFile::fake()->image('one.png'), UploadedFile::fake()->image('two.png')],
            'room' => 'decor', 'dimensions' => '20 × 20 × 30 cm', 'material' => 'Gốm sứ', 'color' => 'Kem', 'warranty_months' => 12];
        $this->put(route('manager.products.update', $product), $data)->assertSessionHasNoErrors();
        $product->refresh();
        $cover = $product->image_path;
        $oldGallery = $product->gallery;
        $this->assertCount(2, $oldGallery);
        Storage::disk('minio_media')->assertExists([$cover, ...$oldGallery]);
        unset($data['image']);
        $data['gallery_images'] = [UploadedFile::fake()->image('new.png')];
        $this->put(route('manager.products.update', $product), $data)->assertSessionHasNoErrors();
        $this->assertSame($cover, $product->fresh()->image_path);
        Storage::disk('minio_media')->assertMissing($oldGallery);
        Storage::disk('minio_media')->assertExists([$cover, ...$product->fresh()->gallery]);
    }

    public function test_gallery_database_failure_cleans_new_uploads_and_preserves_existing_gallery(): void
    {
        Storage::fake('public');
        $product = Product::firstOrFail();
        Storage::disk('public')->put('products/old.png', 'old');
        $product->update(['gallery' => ['products/old.png']]);
        $this->actingAs(User::where('email', 'manager@nest.test')->firstOrFail());
        Event::listen('eloquent.updating: '.Product::class, fn () => throw new \RuntimeException('Gallery transaction failed'));
        $this->withoutExceptionHandling();
        $caught = false;
        try {
            $this->put(route('manager.products.update', $product), ['name' => $product->name, 'category_id' => $product->category_id, 'price' => $product->price, 'stock' => $product->stock,
                'is_active' => 1, 'gallery_images' => [UploadedFile::fake()->image('new.png')]]);
        } catch (\RuntimeException $e) {
            $caught = true;
            $this->assertSame('Gallery transaction failed', $e->getMessage());
        }
        $this->assertTrue($caught);
        $this->assertSame(['products/old.png'], $product->fresh()->gallery);
        $this->assertSame(['products/old.png'], Storage::disk('public')->files('products'));
    }

    public function test_reseeding_preserves_edited_specs_and_deleting_a_product_keeps_shared_catalog_assets(): void
    {
        $product = Product::where('slug', 'sofa-2-cho-oslo')->firstOrFail();
        $product->update(['material' => 'Chất liệu đã chỉnh', 'dimensions' => null, 'gallery' => [], 'stock' => 3]);
        $before = $product->fresh()->getAttributes();
        $this->seed(DatabaseSeeder::class);
        $this->assertSame($before, $product->fresh()->getAttributes());
        $sharedImage = public_path('images/'.$product->image_path);
        $this->assertFileExists($sharedImage);
        $this->actingAs(User::where('email', 'manager@nest.test')->firstOrFail())->delete(route('manager.products.destroy', $product))->assertRedirect();
        $this->assertFileExists($sharedImage);
    }
}
