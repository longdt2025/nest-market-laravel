<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;

class FurnitureCatalogSeeder extends Seeder
{
    public function run(): void
    {
        $names = ['trang-tri' => 'Đồ trang trí', 'phong-khach' => 'Phòng khách', 'phong-ngu' => 'Phòng ngủ', 'phong-an' => 'Phòng ăn', 'ban-lam-viec' => 'Phòng làm việc'];
        foreach ($names as $slug => $name) {
            Category::firstOrCreate(['slug' => $slug], ['name' => $name, 'is_active' => true]);
        }
        $ids = Category::pluck('id', 'slug');
        $manifest = json_decode(file_get_contents(public_path('images/catalog/sources.json')), true, 512, JSON_THROW_ON_ERROR);
        $oldPhotos = ['ceramic' => 'photo-1514228742587-6b1558fcca3d', 'lamp' => 'photo-1507473885765-e6ed057f782c', 'plant' => 'photo-1416879595882-3373a0480b5b', 'candle' => 'photo-1603006905003-be475563bc59', 'chair' => 'photo-1503602642458-232111445657'];
        foreach (require database_path('data/furniture.php') as $index => [$category,$name,$slug,$price,$compare,$stock,$room,$material,$dimensions,$color,$warranty,$source,$photo,$description]) {
            $images = $source ? $manifest[$source]['images'] : [];
            if ($slug === 'chau-cay-lento') {
                $images = ['catalog/lento-plant.jpg'];
            }
            $details = ['room' => $room, 'material' => $material, 'dimensions' => $dimensions, 'color' => $color, 'warranty_months' => $warranty,
                'care_instructions' => 'Lau bằng khăn mềm, khô hoặc hơi ẩm. Tránh nắng trực tiếp, nguồn nhiệt và chất tẩy rửa mạnh. Kiểm tra kích thước lối vào trước khi đặt sản phẩm lớn.',
                'gallery' => $images];
            $product = Product::firstOrCreate(['slug' => $slug], $details + [
                'category_id' => $ids[$category], 'name' => $name, 'price' => $price, 'compare_at_price' => $compare, 'stock' => $stock,
                'description' => $description, 'image_path' => $images[0] ?? ('https://images.unsplash.com/'.$oldPhotos[$photo].'?auto=format&fit=crop&w=1000&q=82'),
                'is_active' => true, 'is_featured' => $index >= 4,
            ]);
            // Initialize the new fields once; rerunning seeding preserves later edits.
            if ($product->catalog_code === null) {
                foreach ($details as $field => $value) {
                    if ($product->{$field} === null) {
                        $product->{$field} = $value;
                    }
                }
                $product->catalog_code = 'nest-furniture-'.$slug;
                $product->save();
            }
        }
        $legacy = ['so-tay-everyday', 'bo-sap-xep-desk-kit', 'dong-ho-minimal', 'ly-su-daily-cup', 'bo-khay-go-bento', 'phin-ca-phe-morning', 'khan-ban-linen', 'tui-vai-market-tote', 'tai-nghe-studio-beat', 'kinh-mat-sunday', 'ba-lo-go-anywhere'];
        Product::whereIn('slug', $legacy)->where('image_path', 'like', 'https://images.unsplash.com/%')->whereNull('catalog_code')->update(['is_active' => false]);
        Category::whereIn('slug', ['nha-bep', 'phu-kien'])->whereDoesntHave('products', fn ($q) => $q->where('is_active', true))->update(['is_active' => false]);
    }
}
