<?php
namespace Tests\Feature;
use App\Models\Product;
use Database\Seeders\{DatabaseSeeder,AdditionalFurnitureSeeder};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
class AdditionalFurnitureSeederTest extends TestCase
{
    use RefreshDatabase;
    public function test_extra_products_use_minio_and_reseeding_preserves_edits(): void
    {
        $this->seed(DatabaseSeeder::class);
        foreach (AdditionalFurnitureSeeder::BUNDLES as $bundle) {
            $product=Product::where('slug',$bundle[1])->firstOrFail();
            $this->assertStringStartsWith('products/minio/',$product->image_path);
            Storage::disk('minio_media')->assertExists([$product->image_path,...$product->gallery]);
            $this->get($product->image_url)->assertOk();
        }
        $files=Storage::disk('minio_media')->allFiles();
        $product=Product::where('slug',AdditionalFurnitureSeeder::BUNDLES[0][1])->firstOrFail();
        $product->update(['name'=>'Tên đã sửa','stock'=>2,'price'=>123000]);
        $this->seed(AdditionalFurnitureSeeder::class);
        $this->assertDatabaseCount('products',22);
        $this->assertSame('Tên đã sửa',$product->fresh()->name);
        $this->assertSame(2,$product->fresh()->stock);
        $this->assertSame(123000,$product->fresh()->price);
        $this->assertSame($files,Storage::disk('minio_media')->allFiles());
    }
}
