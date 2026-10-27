<?php
namespace Database\Seeders;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::query()->firstOrNew(['email'=>'admin@nest.test']);
        $admin->forceFill(['name'=>'Nguyễn Minh (Admin)','password'=>bcrypt('Password@123'),'role'=>'admin'])->save();
        $customer = User::query()->firstOrNew(['email'=>'customer@nest.test']);
        $customer->forceFill(['name'=>'Khách hàng Demo','password'=>bcrypt('Password@123'),'role'=>'customer'])->save();

        $categories = [
            ['name'=>'Đồ trang trí','slug'=>'trang-tri','description'=>'Những món nhỏ tạo nên không gian riêng.'],
            ['name'=>'Bàn làm việc','slug'=>'ban-lam-viec','description'=>'Gọn gàng và tập trung mỗi ngày.'],
            ['name'=>'Nhà bếp','slug'=>'nha-bep','description'=>'Đồ dùng tinh giản cho bữa ăn ấm cúng.'],
            ['name'=>'Phụ kiện','slug'=>'phu-kien','description'=>'Vật dụng mang theo bên mình.'],
        ];
        foreach ($categories as $category) Category::query()->updateOrCreate(['slug'=>$category['slug']], $category+['is_active'=>true]);
        $ids=Category::pluck('id','slug');
        // URL Unsplash chỉ dùng ảnh minh hoạ dữ liệu mẫu; không phải ảnh sản phẩm thương mại thực.
        $images=[
            'ceramic'=>'photo-1514228742587-6b1558fcca3d', 'room'=>'photo-1600210492486-724fe5c67fb0',
            'chair'=>'photo-1503602642458-232111445657', 'table'=>'photo-1494438639946-1ebd1d20bf85',
            'lamp'=>'photo-1507473885765-e6ed057f782c', 'plant'=>'photo-1416879595882-3373a0480b5b',
            'desk'=>'photo-1497366754035-f200968a6e72','notebook'=>'photo-1531346878377-a5be20888e57',
            'coffee'=>'photo-1442512595331-e89e73853f31','wood'=>'photo-1513506003901-1e6a229e2d15',
            'clock'=>'photo-1523275335684-37898b6baf30', 'headphones'=>'photo-1505740420928-5e560c06d30e',
            'bag'=>'photo-1544816155-12df9643f363','glasses'=>'photo-1572635196237-14b3f281503f',
            'candle'=>'photo-1603006905003-be475563bc59','linen'=>'photo-1586023492125-27b2c045efd7',
        ];
        $items=[
            ['trang-tri','Bình gốm Kanso','binh-gom-kanso',289000,359000,22,'ceramic','Bình gốm tinh giản, đường nét mềm mại, phù hợp phòng khách hoặc bàn làm việc.'],
            ['trang-tri','Đèn bàn Nami','den-ban-nami',649000,790000,12,'lamp','Ánh sáng ấm, thiết kế linh hoạt cho góc đọc sách và nghỉ ngơi.'],
            ['trang-tri','Chậu cây Lento','chau-cay-lento',189000,null,18,'plant','Một mảng xanh nhỏ dành cho không gian sống tinh tế.'],
            ['trang-tri','Nến thơm Slow Sunday','nen-thom-slow-sunday',329000,389000,9,'candle','Hương gỗ dịu nhẹ, thời gian lưu hương dài, phù hợp thư giãn.'],
            ['ban-lam-viec','Ghế gỗ Form 01','ghe-go-form-01',1590000,1850000,6,'chair','Kiểu dáng tối giản, cân bằng giữa chức năng và thẩm mỹ.'],
            ['ban-lam-viec','Sổ tay Everyday','so-tay-everyday',99000,129000,40,'notebook','Giấy viết dày, gáy chắc chắn; đồng hành cùng những ý tưởng mới.'],
            ['ban-lam-viec','Bộ sắp xếp Desk Kit','bo-sap-xep-desk-kit',249000,299000,16,'desk','Bộ phụ kiện tổ chức bàn làm việc gọn gàng và dễ sử dụng.'],
            ['ban-lam-viec','Đồng hồ Minimal','dong-ho-minimal',520000,null,11,'clock','Thiết kế rõ ràng, mang tinh thần tối giản vào nhịp sống.'],
            ['nha-bep','Ly sứ Daily Cup','ly-su-daily-cup',169000,219000,35,'ceramic','Ly sứ màu kem, kích thước vừa tay, sử dụng mỗi ngày.'],
            ['nha-bep','Bộ khay gỗ Bento','bo-khay-go-bento',369000,450000,14,'wood','Chất liệu gỗ tự nhiên, phù hợp khi thưởng trà hoặc sắp đặt.'],
            ['nha-bep','Phin cà phê Morning','phin-ca-phe-morning',225000,null,25,'coffee','Dụng cụ pha cà phê nhỏ gọn, dễ làm sạch.'],
            ['nha-bep','Khăn bàn Linen','khan-ban-linen',420000,490000,13,'linen','Chất liệu mềm nhẹ, tông màu trung tính, dễ phối nội thất.'],
            ['phu-kien','Túi vải Market Tote','tui-vai-market-tote',245000,290000,31,'bag','Túi sử dụng hằng ngày, bền, dễ gấp gọn khi mang theo.'],
            ['phu-kien','Tai nghe Studio Beat','tai-nghe-studio-beat',1150000,1390000,7,'headphones','Tai nghe chụp tai tiện dụng, phù hợp học tập và làm việc.'],
            ['phu-kien','Kính mát Sunday','kinh-mat-sunday',390000,null,17,'glasses','Dáng kính cổ điển, màu sắc dễ phối trong nhiều phong cách.'],
            ['phu-kien','Ba lô Go Anywhere','ba-lo-go-anywhere',790000,990000,8,'bag','Balo đa ngăn để mang những vật dụng cần thiết mỗi ngày.'],
        ];
        foreach ($items as $index => [$category,$name,$slug,$price,$compare,$stock,$photo,$description]) {
            Product::query()->updateOrCreate(['slug'=>$slug],[
                'category_id'=>$ids[$category],'name'=>$name,'price'=>$price,'compare_at_price'=>$compare,
                'stock'=>$stock,'description'=>$description,
                'image_path'=>'https://images.unsplash.com/'.$images[$photo].'?auto=format&fit=crop&w=1000&q=82',
                'is_active'=>true,'is_featured'=>$index < 8,
            ]);
        }
    }
}
