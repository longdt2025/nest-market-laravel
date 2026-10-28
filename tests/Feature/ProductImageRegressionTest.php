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

class ProductImageRegressionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        Storage::fake('public');
        $this->actingAs(User::where('email', 'admin@nest.test')->firstOrFail());
    }

    private function payload(Product $product): array
    {
        return ['name' => $product->name, 'category_id' => $product->category_id, 'price' => $product->price,
            'stock' => $product->stock, 'is_active' => 1, 'image' => UploadedFile::fake()->image('replacement.png')];
    }

    public function test_successful_replacement_keeps_new_image_and_removes_old_image(): void
    {
        $product = Product::firstOrFail();
        Storage::disk('public')->put('products/old.jpg', 'old image');
        $product->update(['image_path' => 'products/old.jpg']);
        $this->put(route('admin.products.update', $product), $this->payload($product))->assertSessionHasNoErrors();
        Storage::disk('public')->assertExists($product->fresh()->image_path);
        Storage::disk('public')->assertMissing('products/old.jpg');
        $this->assertCount(1, Storage::disk('public')->files('products'));
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
            $this->put(route('admin.products.update', $product), $this->payload($product));
        } catch (\RuntimeException $exception) {
            $caught = true;
            $this->assertSame('Simulated database failure', $exception->getMessage());
        }
        $this->assertTrue($caught);
        $this->assertSame('products/old.jpg', $product->fresh()->image_path);
        Storage::disk('public')->assertExists('products/old.jpg');
        $this->assertSame(['products/old.jpg'], Storage::disk('public')->files('products'));
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
            $this->post(route('admin.products.store'), $this->payload($product));
        } catch (\RuntimeException $exception) {
            $caught = true;
            $this->assertSame('Simulated database failure', $exception->getMessage());
        }
        $this->assertTrue($caught);
        $this->assertSame($count, Product::count());
        $this->assertSame([], Storage::disk('public')->files('products'));
    }
}
