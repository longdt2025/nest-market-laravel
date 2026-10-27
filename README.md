# NEST Market — Website bán hàng online

**Đồ án lập trình web PHP Laravel theo kiến trúc MVC**  
**Mục đích:** xây dựng website bán hàng online phiên bản đơn giản với hai vai trò **Customer** và **Admin**, có lưu trữ dữ liệu MySQL và quy trình mua hàng từ đầu đến cuối.

> Đây là ứng dụng **phục vụ học tập và trình diễn đồ án**, không phải hệ thống thương mại điện tử triển khai thực tế. Thanh toán khi nhận hàng (COD) là mô phỏng, không kết nối cổng thanh toán thật.

## 1. Công nghệ sử dụng

| Thành phần | Công nghệ |
| --- | --- |
| Ngôn ngữ backend | PHP 8.2 trở lên (đã sử dụng PHP 8.3.36 trên Laragon) |
| Framework | Laravel 12 |
| Kiến trúc | MVC (Model – View – Controller) |
| ORM | Laravel Eloquent |
| Database | MySQL |
| Giao diện | Laravel Blade, HTML5, CSS3, JavaScript |
| UI framework | Bootstrap 5.3.3 (CDN) + CSS tùy chỉnh |
| AJAX | JavaScript Fetch API |
| Xác thực và phân quyền | Laravel session authentication, middleware `auth`, `admin`, `customer` |
| Lưu file ảnh | Laravel Filesystem, disk `public` |
| API ngoài | Province Open API v2 (gợi ý tỉnh/thành) |
| Công cụ phát triển | Laragon, VS Code, Composer, HeidiSQL |

**Không cần Node.js hay `npm install` để chạy giao diện hiện tại**: CSS và JavaScript của đồ án nằm trong `public/`; Bootstrap và Google Fonts được tải qua Internet.

## 2. Các chức năng chính

### Khách chưa đăng nhập (Guest)
- Xem trang chủ, danh mục và chi tiết sản phẩm.
- Tìm kiếm theo tên/mô tả; lọc danh mục; sắp xếp, phân trang sản phẩm.
- Thêm/xem/sửa/xóa giỏ hàng lưu trong Session.
- Đăng ký tài khoản và đăng nhập.

### Khách hàng (Customer)
- Đăng nhập, đăng xuất, xem sản phẩm, quản lý giỏ hàng.
- Đặt hàng COD, nhập thông tin giao hàng, xem lịch sử và chi tiết **đơn của chính mình**.
- Thêm vào giỏ bằng AJAX; cập nhật số lượng và tổng tiền.
- Xem gợi ý tỉnh/thành từ API ngoài tại trang thanh toán; vẫn nhập địa chỉ thủ công nếu API không hoạt động.

### Quản trị viên (Admin)
- Dashboard thống kê sản phẩm, danh mục, khách hàng, đơn chờ xác nhận và doanh thu đơn đã giao.
- CRUD danh mục; không cho xóa danh mục đang có sản phẩm.
- CRUD sản phẩm, tìm kiếm/lọc/phân trang, upload và thay ảnh sản phẩm.
- Xem, lọc, tìm kiếm đơn hàng và cập nhật trạng thái theo quy trình cho phép.
- Phân quyền bằng middleware: Customer không thể vào trang Admin.

### Quy tắc nghiệp vụ
- **Phí giao hàng:** 30.000đ; **miễn phí** khi tiền hàng đạt từ **800.000đ**.
- **Trạng thái đơn hàng:** `pending` (Chờ xác nhận) → `confirmed` (Đã xác nhận) → `shipping` (Đang giao) → `delivered` (Đã giao). Có thể hủy từ `pending` hoặc `confirmed`.
- Khi đặt hàng, hệ thống kiểm tra tồn kho và tính tiền trên server; tạo đơn, chi tiết đơn và trừ tồn kho trong cùng database transaction.
- Khi Admin hủy đơn hợp lệ, tồn kho được hoàn lại.
- Sản phẩm đã xuất hiện trong đơn hàng sẽ **được ẩn** (`is_active = 0`) khi Admin chọn xóa, thay vì xóa bản ghi, để giữ lịch sử đơn. Sản phẩm chưa có lịch sử đơn có thể xóa thực sự.

## 3. Yêu cầu môi trường

- Windows có **Laragon** (PHP **8.2+** và MySQL) hoặc môi trường PHP/MySQL tương đương.
- **Composer 2** để cài các package PHP.
- VS Code (hoặc IDE khác), trình duyệt web và HeidiSQL để xem database.
- PHP extensions thông dụng của Laravel, đặc biệt `pdo_mysql`, `mbstring`, `openssl`, `fileinfo`. Để chạy bộ PHPUnit cấu hình sẵn cần thêm `pdo_sqlite` và `sqlite3`.

Mở **Terminal PowerShell** của VS Code hoặc Terminal Laragon, kiểm tra:

```powershell
php -v
composer -V
php -m
```

Nếu PowerShell báo `php is not recognized`, vào Laragon → **Menu → Tools → Path → Add Laragon to Path**, đóng/mở lại VS Code; nếu chưa nhận, đăng xuất/đăng nhập Windows.

## 4. Cài đặt lần đầu trên Laragon

### Bước 1 — Mở đúng thư mục project

Giải nén vào `C:\laragon\www\`. Tên thư mục có thể là `nest-market`. Trong cuộc hội thoại hướng dẫn, project từng được đặt tại:

```text
C:\laragon\www\nest-market-laravel\nest-market
```

**Quan trọng:** Mở bằng VS Code **thư mục chứa trực tiếp `artisan` và `composer.json`**, không mở nhầm thư mục ZIP bọc ngoài.

Ví dụ:

```powershell
cd C:\laragon\www\nest-market
```

Hoặc thay đường dẫn trên bằng vị trí thực tế trên máy.

### Bước 2 — Cài thư viện và tạo `.env`

```powershell
composer install
```

Nếu **chưa có** file `.env`, chạy:

```powershell
Copy-Item .env.example .env
```

Tiếp tục:

```powershell
php artisan key:generate
```

**Không ghi đè `.env` đã cấu hình**, vì file này lưu APP_KEY và thông số database. Lệnh `composer install` sẽ tạo thư mục `vendor/` từ `composer.lock`.

### Bước 3 — Tạo cơ sở dữ liệu MySQL

Trong Laragon chọn **Start All**, mở HeidiSQL, kết nối MySQL và tạo database `nest_market`.

Có thể chạy SQL sau (hoặc dùng file `database/create_database.sql`):

```sql
CREATE DATABASE IF NOT EXISTS `nest_market`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;
```

### Bước 4 — Cấu hình kết nối tại `.env`

```dotenv
APP_NAME="NEST Market"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://127.0.0.1:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=nest_market
DB_USERNAME=root
DB_PASSWORD=
```

`root`, mật khẩu trống và cổng `3306` chỉ là cấu hình Laragon phổ biến; **điều chỉnh theo MySQL thực tế** trên máy bạn.

```powershell
php artisan config:clear
```

### Bước 5 — Tạo bảng, dữ liệu demo và liên kết lưu ảnh

```powershell
php artisan migrate --seed
php artisan storage:link
```

- `migrate` tạo bảng và quan hệ theo Migration.
- `--seed` chạy `DatabaseSeeder` để tạo tài khoản, danh mục và sản phẩm mẫu.
- `storage:link` tạo liên kết `public/storage` phục vụ ảnh upload.

Dữ liệu mẫu ban đầu: **2 tài khoản**, **4 danh mục** và **16 sản phẩm** (nếu database mới và Seeder chạy thành công).

> **Cảnh báo:** Không chạy `php artisan migrate:fresh --seed` trên database có dữ liệu cần giữ. Lệnh này **xóa tất cả các bảng** trước khi tạo lại. Không chạy lại `db:seed` tùy tiện sau khi đã đặt hàng hoặc cập nhật tồn kho vì Seeder có thể ghi đè dữ liệu demo.

### Bước 6 — Chạy website

```powershell
php artisan serve
```

Truy cập **http://127.0.0.1:8000**. Giữ Terminal này đang chạy khi sử dụng website.

Khi mở lại project vào ngày khác, thông thường chỉ cần bật MySQL trong Laragon và chạy `php artisan serve` (không cần migrate/seed lại).

## 5. Tài khoản demo

| Vai trò | Email | Mật khẩu |
| --- | --- | --- |
| Admin | `admin@nest.test` | `Password@123` |
| Customer | `customer@nest.test` | `Password@123` |

Tài khoản được tạo trong `database/seeders/DatabaseSeeder.php`. Chỉ sử dụng ở **môi trường local**; không dùng mật khẩu này cho hệ thống công khai.

### Các đường dẫn chính

| Trang | URL |
| --- | --- |
| Trang chủ | `/` |
| Danh sách sản phẩm | `/san-pham` |
| Giỏ hàng | `/gio-hang` |
| Đăng nhập / đăng ký | `/dang-nhap` / `/dang-ky` |
| Thanh toán (Customer) | `/thanh-toan` |
| Đơn hàng của tôi (Customer) | `/don-hang` |
| Dashboard Admin | `/admin` |
| Quản lý danh mục | `/admin/categories` |
| Quản lý sản phẩm | `/admin/products` |
| Quản lý đơn hàng | `/admin/orders` |

Ví dụ: `http://127.0.0.1:8000/admin/products`.

## 6. Cấu trúc project MVC

```text
nest-market/
├── app/
│   ├── Http/
│   │   ├── Controllers/       # Storefront, Auth, Admin, Checkout...
│   │   ├── Middleware/        # EnsureAdmin, EnsureCustomer
│   │   └── Requests/          # Validation cho form
│   ├── Models/                # User, Category, Product, Order, OrderItem
│   ├── Providers/
│   └── Support/Cart.php       # Giỏ hàng qua Session
├── bootstrap/
├── config/
├── database/
│   ├── migrations/            # 5 Migration nghiệp vụ
│   ├── seeders/DatabaseSeeder.php
│   └── create_database.sql
├── docs/                     # Barem, kiểm thử, đề cương báo cáo
├── public/
│   ├── css/app.css
│   ├── js/app.js
│   └── images/product-fallback.svg
├── resources/views/          # Blade khách hàng và Admin
├── routes/web.php
├── tests/Feature/             # Feature tests
├── .env.example
├── composer.json
├── composer.lock
└── artisan
```

Luồng cơ bản: **Browser → Route → Middleware → Controller → Eloquent Model → MySQL → Blade View → Browser**.

### Database chính

| Bảng | Vai trò |
| --- | --- |
| `users` | Tài khoản, mật khẩu đã hash và vai trò |
| `categories` | Danh mục sản phẩm |
| `products` | Tên, giá, số lượng tồn, ảnh, danh mục, trạng thái |
| `orders` | Thông tin khách hàng, tổng tiền, trạng thái đơn |
| `order_items` | Sản phẩm, số lượng, **giá tại thời điểm mua** |

Quan hệ Eloquent: Category `hasMany` Products; Product `belongsTo` Category và `hasMany` OrderItems; User `hasMany` Orders; Order `hasMany` OrderItems.

## 7. Kiểm thử và đối chiếu barem

### Chạy kiểm thử tự động

```powershell
php artisan migrate:status
php artisan route:list -v
php artisan test
```

Các test chính nằm trong:

- `tests/Feature/StorefrontTest.php`: xem/tìm sản phẩm, giỏ hàng AJAX, tạo đơn/trừ tồn, không cho mua quá tồn, API tỉnh/thành dùng mock, bảo vệ đơn hàng cá nhân.
- `tests/Feature/AdminTest.php`: chặn truy cập trái quyền, CRUD, hủy đơn và hoàn tồn kho.

**Bộ PHPUnit dùng SQLite trong bộ nhớ** theo `phpunit.xml`, không dùng MySQL demo. Nếu gặp `could not find driver`, kiểm tra `php -m`, bật `pdo_sqlite` và `sqlite3` trong PHP Laragon.

> Trong bản ZIP này, chưa có log xác nhận **tất cả PHPUnit test PASS trên môi trường của người nhận**. Bạn cần chạy lệnh thực tế và lưu ảnh/đầu ra để làm bằng chứng trước khi nộp. Test API sử dụng `Http::fake()` không thay thế cho việc thử kết nối API thật trong trình duyệt.

### Bảng đối chiếu barem (10 điểm)

| Tiêu chí | Điểm tối đa | Kiểm tra/minh chứng |
| --- | ---: | --- |
| CRUD ít nhất 2 bảng liên quan | 1,5 | Admin: thêm/sửa/xóa danh mục và sản phẩm; kiểm tra quan hệ FK |
| Đăng nhập, đăng xuất, phân quyền | 1,0 | Login Admin/Customer; Customer vào `/admin` nhận 403 |
| Tìm kiếm, phân trang | 1,0 | Tìm “Kanso”, lọc và chuyển trang sản phẩm |
| Validation | 0,5 | Thử tên thiếu, giá âm, ảnh sai định dạng, email trùng |
| Eloquent ORM | 1,0 | Model, `belongsTo`, `hasMany`, `with`, truy vấn Eloquent |
| Migration & Seeder | 1,0 | `migrate:status`, 5 bảng nghiệp vụ, tài khoản/sản phẩm mẫu |
| Controller, Middleware, Blade | 0,5 | Cấu trúc MVC, Route và middleware bảo vệ Admin |
| UI/UX | 1,0 | Trải nghiệm trang chủ, sản phẩm, cart, checkout, Admin |
| Responsive | 0,5 | Chrome DevTools: 390px, 768px, 1440px |
| Báo cáo và hình minh chứng | 0,5 | Báo cáo hoàn chỉnh, ERD, UML Use Case, ảnh chụp trang thật |
| Thuyết trình và vấn đáp | 0,5 | Demo nghiệp vụ và giải thích luồng MVC |
| Sáng tạo/mở rộng | 1,0 | Upload ảnh, AJAX, API ngoài và kỹ thuật tối ưu; **tổng nhóm tối đa 1,0** |

Xem thêm `docs/BAREM.md` và `docs/KIEM_THU.md` để có danh sách minh chứng kiểm thử. `docs/BAO_CAO.md` là **bản phác thảo nội dung**, không thay thế cho báo cáo học thuật cuối cùng có ERD/UML/ảnh chụp thực tế.

### Kịch bản demo ngắn khi bảo vệ

1. Guest mở `/san-pham` → tìm “Kanso” → xem chi tiết.
2. Customer đăng nhập → thêm 2 sản phẩm vào giỏ → đặt COD → xem `/don-hang`.
3. HeidiSQL kiểm tra `orders`, `order_items`, `products.stock` (tồn kho trừ theo đơn).
4. Admin đăng nhập → CRUD danh mục/sản phẩm → xem đơn và cập nhật/hủy → kiểm tra hoàn tồn.
5. Customer thử vào `/admin` (bị chặn 403); giới thiệu Model, Migration, Middleware và các bài test.

## 8. Ảnh sản phẩm và API ngoài

- Dữ liệu Seeder dùng URL ảnh minh họa **Unsplash**, nên cần mạng để tải lần đầu. Khi không tải được có ảnh fallback tại `public/images/product-fallback.svg`.
- Ảnh do Admin upload lưu ở `storage/app/public/products`, truy cập qua `public/storage`.
- Nếu muốn tải ảnh demo về local để trình diễn offline, **chạy một lần sau khi seed**:

```powershell
php artisan nest:localize-images
php artisan storage:link
```

- API tỉnh/thành: backend gọi `https://provinces.open-api.vn/api/v2/` thông qua `ProvinceController`, endpoint nội bộ `/tinh-thanh` chỉ dành cho Customer đã đăng nhập. Dữ liệu có cache; khi API lỗi, khách vẫn nhập địa chỉ đầy đủ thủ công.
- Ảnh demo là hình minh họa, không phải bằng chứng nguồn gốc sản phẩm hoặc hàng đang bán thật.

## 9. Các lỗi thường gặp

| Lỗi | Hướng kiểm tra |
| --- | --- |
| `php is not recognized` | Thêm Laragon vào PATH, khởi động lại VS Code |
| `vendor/autoload.php` không tồn tại | Đảm bảo đang ở thư mục có `composer.json`; chạy `composer install` |
| `No application encryption key` | Kiểm tra `.env`, chạy `php artisan key:generate` |
| `Access denied for user` / `Unknown database` | Kiểm tra MySQL đang chạy, `DB_*` trong `.env`, tạo `nest_market` |
| Bảng chưa tồn tại | Chạy `php artisan migrate --seed` trên DB đã chọn |
| Ảnh upload không hiển thị | Kiểm tra `config/filesystems.php`, `storage:link`, `APP_URL`, `image_path` |
| Trang dùng CSS/Blade cũ | Tải lại Ctrl+F5, chạy `php artisan view:clear` / `php artisan optimize:clear` |
| Lỗi 419 khi gửi form | Kiểm tra `@csrf`, Session, cookies, URL/domain đang dùng |
| `could not find driver` khi test | Bật `pdo_sqlite`/`sqlite3` rồi chạy lại `php artisan test` |
| API tỉnh/thành không tải | Kiểm tra Internet/API và vẫn nhập địa chỉ thủ công |

**Giới hạn đã biết:** Giỏ hàng dựa trên Session; nếu Admin thay đổi tồn kho hoặc ẩn sản phẩm **sau khi khách đã thêm vào giỏ**, số lượng hiển thị ở danh sách giỏ và badge có thể chưa đồng bộ hoàn toàn. Checkout vẫn kiểm tra tồn kho trên server và có thể yêu cầu khách sửa giỏ. Nên kiểm thử/sửa tình huống này trước khi khẳng định hệ thống không còn lỗi.

## 10. Lưu ý khi nộp hoặc đẩy GitHub

- **Không đưa `.env`** (APP_KEY, mật khẩu DB), `vendor/`, `node_modules/`, cache và log lên GitHub.
- **Có đưa** `.env.example`, `composer.json`, `composer.lock`, mã nguồn, Migration/Seeder, README và tài liệu cần thiết.
- Nếu trình diễn khi không có mạng, hãy bảo đảm ảnh demo và CSS/Fonts ngoài CDN đã có phương án dự phòng hoặc Internet ổn định.
- Ghi ảnh minh chứng **từ website chạy thật** và lưu kết quả PHPUnit; không ghi “tất cả test PASS” khi chưa có log xác nhận.
- Đổi mật khẩu demo và dùng `APP_DEBUG=false` nếu triển khai công khai.

---

**NEST Market — Đồ án PHP Laravel MVC, môi trường phát triển Laragon + VS Code.**
