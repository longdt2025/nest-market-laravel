<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

class AdditionalFurnitureSeeder extends Seeder
{
    public const BUNDLES = [
        ['binh-gom-kanso', 'bo-2-ly-gom-kanso', 'Bộ 2 ly gốm Kanso', 2, 15],
        ['den-ban-nami', 'bo-2-den-ban-nami', 'Bộ 2 đèn bàn Nami', 2, 8],
        ['chau-cay-lento', 'bo-3-chau-cay-lento', 'Bộ 3 chậu cây Lento', 3, 12],
        ['nen-thom-slow-sunday', 'bo-2-nen-thom-slow-sunday', 'Bộ 2 nến thơm Slow Sunday', 2, 10],
        ['ghe-go-form-01', 'bo-2-ghe-go-form-01', 'Bộ 2 ghế gỗ Form 01', 2, 7],
        ['ghe-an-haru', 'bo-2-ghe-an-haru', 'Bộ 2 ghế ăn Haru', 2, 9],
    ];

    public function run(): void
    {
        $manifest = json_decode(file_get_contents(public_path('images/catalog/sources.json')), true, 512, JSON_THROW_ON_ERROR);
        $sourceRows = collect(require database_path('data/furniture.php'))->keyBy(fn ($row) => $row[2]);
        $photos = ['ceramic' => 'photo-1514228742587-6b1558fcca3d', 'lamp' => 'photo-1507473885765-e6ed057f782c', 'candle' => 'photo-1603006905003-be475563bc59', 'chair' => 'photo-1503602642458-232111445657'];
        foreach (self::BUNDLES as [$sourceSlug,$slug,$name,$quantity,$stock]) {
            if (Product::where('slug', $slug)->exists()) {
                continue;
            }
            $source = Product::where('slug', $sourceSlug)->firstOrFail();
            $row = $sourceRows->get($sourceSlug);
            $images = $sourceSlug === 'chau-cay-lento' ? ['catalog/lento-plant.jpg'] : ($row[11] ? $manifest[$row[11]]['images'] : ['demo/'.$photos[$row[12]].'.jpg']);
            if ($sourceSlug === 'chau-cay-lento') {
                $images = ['catalog/lento-plant.jpg'];
            }
            $disk = Storage::disk('minio_media');
            $paths = [];
            $created = [];
            try {
                foreach (array_slice($images, 0, 2) as $image) {
                    $bytes = file_get_contents(public_path('images/'.$image));
                    $key = 'products/minio/'.substr(hash('sha256', $slug.'|'.$image.'|'.hash('sha256', $bytes)), 0, 40).'.'.pathinfo($image, PATHINFO_EXTENSION);
                    if (! $disk->exists($key)) {
                        $disk->put($key, $bytes);
                        $created[] = $key;
                    }
                    $paths[] = $key;
                }
                $price = (int) (floor($row[3] * $quantity * .95 / 1000) * 1000);
                Product::create([
                    'slug' => $slug, 'name' => $name, 'catalog_code' => 'nest-bundle-'.$slug,
                    'category_id' => $source->category_id, 'price' => $price, 'compare_at_price' => $row[3] * $quantity,
                    'cost_price' => (int) (floor($price * .7 / 1000) * 1000), 'stock' => $stock, 'unit' => 'Bộ', 'vat_rate' => 8,
                    'room' => $row[6], 'material' => $row[7], 'dimensions' => $row[8], 'color' => $row[9], 'warranty_months' => $row[10],
                    'description' => 'Bộ gồm '.$quantity.' sản phẩm cùng mẫu. Kích thước áp dụng cho từng sản phẩm. Ảnh minh họa mẫu sản phẩm trong bộ.',
                    'image_path' => $paths[0], 'gallery' => $paths, 'is_active' => true, 'is_featured' => false,
                ]);
            } catch (\Throwable $e) {
                if ($created) {
                    $disk->delete($created);
                }throw $e;
            }
        }
    }
}
