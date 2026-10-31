<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use App\Services\BackupService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProductImageRegressionTest extends TestCase
{
    use RefreshDatabase;

    private array $seededMedia = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        Storage::fake('public');
        $this->seededMedia = Storage::disk('minio_media')->allFiles('products/minio');
        $this->actingAs(User::where('email', 'manager@nest.test')->firstOrFail());
    }

    private function payload(Product $product): array
    {
        return ['name' => $product->name, 'category_id' => $product->category_id, 'price' => $product->price,
            'stock' => $product->stock, 'is_active' => 1, 'image' => UploadedFile::fake()->image('replacement.png')];
    }

    public function test_minio_image_is_served_without_exposing_other_bucket_paths(): void
    {
        $file = str_repeat('a', 40).'.png';
        Storage::disk('minio_media')->put('products/minio/'.$file, 'test-image');
        $this->get(route('products.image', $file))->assertOk()->assertHeader('Content-Type', 'image/png')->assertStreamedContent('test-image');
        $this->get('/media/products/missing.png')->assertNotFound();
        $product = Product::firstOrFail();
        $product->update(['image_path' => 'products/minio/'.$file]);
        $this->assertSame(route('products.image', $file), $product->image_url);
    }

    public function test_backup_restores_minio_product_images_to_minio(): void
    {
        Storage::fake('minio');
        $file = 'products/minio/'.str_repeat('b', 40).'.png';
        Storage::disk('minio_media')->put($file, 'original-minio-image');
        $product = Product::firstOrFail();
        $product->update(['image_path' => $file, 'gallery' => [$file]]);
        $admin = User::where('role', 'admin')->firstOrFail();
        $service = app(BackupService::class);
        $backup = $service->create($admin, 'MinIO image');
        $service->restore($backup, $admin);
        $restored = $product->fresh();
        $this->assertStringStartsWith('products/minio/', $restored->image_path);
        $this->assertNotSame($file, $restored->image_path);
        $this->assertSame('original-minio-image', Storage::disk('minio_media')->get($restored->image_path));
        $this->assertSame([$restored->image_path], $restored->gallery);
    }

    public function test_successful_replacement_keeps_new_image_and_removes_old_image(): void
    {
        $product = Product::firstOrFail();
        Storage::disk('public')->put('products/old.jpg', 'old image');
        $product->update(['image_path' => 'products/old.jpg']);
        $this->put(route('manager.products.update', $product), $this->payload($product))->assertSessionHasNoErrors();
        Storage::disk('minio_media')->assertExists($product->fresh()->image_path);
        Storage::disk('public')->assertMissing('products/old.jpg');
        $this->assertCount(count($this->seededMedia)+1, Storage::disk('minio_media')->allFiles('products/minio'));
        $this->assertSame([], Storage::disk('public')->allFiles('products'));
    }

    public function test_failed_update_preserves_old_image_and_removes_uncommitted_upload(): void
    {
        $product = Product::firstOrFail();
        Storage::disk('public')->put('products/old.jpg', 'old image');
        $product->update(['image_path' => 'products/old.jpg']);
        Event::listen('eloquent.updating: '.Product::class, function () {
            throw new \RuntimeException('Simulated database failure');
        });
        $this->withoutExceptionHandling();
        $caught = false;
        try {
            $this->put(route('manager.products.update', $product), $this->payload($product));
        } catch (\RuntimeException $exception) {
            $caught = true;
            $this->assertSame('Simulated database failure', $exception->getMessage());
        }
        $this->assertTrue($caught);
        $this->assertSame('products/old.jpg', $product->fresh()->image_path);
        Storage::disk('public')->assertExists('products/old.jpg');
        $this->assertSame(['products/old.jpg'], Storage::disk('public')->files('products'));
        $this->assertSame($this->seededMedia, Storage::disk('minio_media')->allFiles('products/minio'));
    }

    public function test_failed_creation_does_not_leave_an_orphan_upload(): void
    {
        $product = Product::firstOrFail();
        $count = Product::count();
        Event::listen('eloquent.creating: '.Product::class, function () {
            throw new \RuntimeException('Simulated database failure');
        });
        $this->withoutExceptionHandling();
        $caught = false;
        try {
            $this->post(route('manager.products.store'), $this->payload($product));
        } catch (\RuntimeException $exception) {
            $caught = true;
            $this->assertSame('Simulated database failure', $exception->getMessage());
        }
        $this->assertTrue($caught);
        $this->assertSame($count, Product::count());
        $this->assertSame([], Storage::disk('public')->files('products'));
        $this->assertSame($this->seededMedia, Storage::disk('minio_media')->allFiles('products/minio'));
    }
}
