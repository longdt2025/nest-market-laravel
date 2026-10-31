## Công nghệ và phạm vi

| Backend | PHP 8.2+, Laravel 12, Eloquent ORM, kiến trúc MVC |
| Frontend | Blade, JavaScript thuần/Fetch API, Bootstrap 5.3.3, CSS tùy chỉnh |
| Database | MySQL 8.4 trong Docker |
| Máy chủ | PHP 8.3 FPM và Nginx 1.28 |
| Asset | Bootstrap, font Inter và ảnh minh họa lưu trong public/; workspace tham khảo bố cục iOrder |
| API ngoài | Province Open API v2, gợi ý tỉnh/thành ở checkout |
| Kiểm thử | PHPUnit 11, SQLite trong bộ nhớ, tách khỏi MySQL demo |

Không cần Node.js/npm để chạy giao diện. Docker là cách chạy chính; Laragon là phương án thay thế. Bản này không sử dụng PostgreSQL hay Redis. MinIO lưu các bản sao lưu mã hóa trong bucket riêng tư. Session/cache dùng file; ảnh upload dùng Laravel public disk.

## Chạy lần đầu bằng Docker trên Windows

Cài Git và Docker Desktop, khởi động Docker Desktop ở chế độ Linux containers. Mở PowerShell tại thư mục có artisan, composer.json và compose.yaml:

```powershell
git clone https://github.com/longdt2025/nest-market-laravel.git
cd nest-market-laravel
powershell -ExecutionPolicy Bypass -File .\scripts\docker-setup.ps1
```

Nếu đã có source, bỏ qua hai lệnh clone/cd. Script tự tạo .env từ .env.example khi chưa có, build PHP và MinIO, khởi động MySQL/MinIO, cài Composer dependencies, tạo APP_KEY nếu thiếu, chạy migration và dựng app/Nginx. Script chỉ tự seed khi phát hiện database mới chưa có users/products/orders; database có sẵn được giữ lại.

Truy cập **http://localhost:8082/**. Không cần bật PHP/MySQL của Laragon.

### Những lần chạy tiếp theo

```powershell
docker compose up -d
docker compose ps
```

Dừng project nhưng giữ dữ liệu:

```powershell
docker compose stop
```

Khi lấy code mới:

powershell
git pull origin main
powershell -ExecutionPolicy Bypass -File .\scripts\docker-setup.ps1
```

```Lệnh check version laravel
docker compose exec app php artisan --version

Nếu cần bổ sung dữ liệu mẫu vào database có sẵn, chạy có chủ đích:

```powershell
docker compose exec app php artisan db:seed
```

Seeder tạo bản ghi còn thiếu, giữ các chỉnh sửa tài khoản/sản phẩm đã tồn tại. Có bước chuyển catalog demo cũ sang nội thất: ẩn một số sản phẩm mẫu cũ theo slug, không xóa lịch sử đơn.

### Cổng và dữ liệu

| Website | http://localhost:8082 |
| Laravel kết nối MySQL trong Docker | mysql:3306 |
| DBeaver/HeidiSQL kết nối từ máy host | 127.0.0.1:13306 |
| Database mặc định | nest_market |

Tài khoản MySQL xem DOCKER_DB_USERNAME và DOCKER_DB_PASSWORD trong .env. APP_PORT, FORWARD_DB_PORT và DOCKER_APP_URL điều chỉnh cổng/URL Docker; nhóm DB_* phục vụ môi trường PHP chạy trực tiếp.

Named volumes lưu MySQL, uploads/session/cache, vendor và bootstrap cache. Nginx phục vụ /storage/ trực tiếp từ volume, không cần tạo Windows symlink khi dùng Docker. docker compose down vẫn giữ named volumes; **không dùng down -v hoặc migrate:fresh trên dữ liệu cần giữ**.

## Tài khoản demo

| Vai trò | Tài khoản đăng nhập | Mật khẩu |
| --- | --- | --- |
| Admin hệ thống | admin | admin123 |
| Quản lý cửa hàng | manager | Password@123 |
| Customer | customer@nest.test | Password@123 |

Admin đăng nhập bằng username admin, email nội bộ admin@nest.test. Manager dùng username manager, email manager@nest.test. Seeder chỉ tạo tài khoản nếu chưa tồn tại, không đặt lại mật khẩu đã đổi. Đây là tài khoản trình diễn local.


## Workspace hệ thống và cửa hàng

- **Admin hệ thống — /admin:** quản lý tài khoản, tạo/sửa tên đăng nhập, phân quyền, khóa/mở tài khoản; sao lưu và phục hồi. Không truy cập nghiệp vụ sản phẩm/đơn hàng của Manager.
- **Quản lý cửa hàng — /quan-ly:** dashboard chỉ gồm biểu đồ doanh thu/số đơn và bộ lọc ngày (tối đa một năm), đơn hàng, danh mục, sản phẩm và tồn kho. Không quản lý tài khoản hoặc backup.
- **Customer:** mua hàng và tự phục vụ trên đơn của mình.
- Cùng khung workspace có sidebar, header và menu tài khoản: Đổi mật khẩu, Cập nhật thông tin, Đăng xuất. Menu không có Đánh giá cửa hàng.
- Khóa tài khoản chặn đăng nhập và phiên đang hoạt động. Không cho tự khóa/gỡ quyền Admin hiện tại; phải giữ ít nhất một Admin hoạt động.

## MinIO và sao lưu/phục hồi

| Mục | Giá trị mặc định |
| --- | --- |
| API MinIO trên host | http://localhost:19000 |
| Console MinIO | http://localhost:19001 |
| Endpoint nội bộ Laravel | http://minio:9000 |
| Bucket riêng tư | nest-market-backups |
| Biến cấu hình | MINIO_ROOT_USER, MINIO_ROOT_PASSWORD, MINIO_BUCKET và MINIO_* trong .env |

MinIO được build từ source chính thức tại tag RELEASE.2025-04-22T22-12-26Z vì image upstream không tải được trong môi trường kiểm tra. Lần build đầu cần mạng và có thể lâu hơn. License upstream: GNU AGPLv3, xem https://github.com/minio/minio.

Script Docker setup build/start MinIO và gọi lệnh dưới để tạo bucket nếu chưa có:

```powershell
docker compose exec app php artisan nest:storage-init
```

Chỉ Admin được tạo, xem, tải và phục hồi backup qua ứng dụng. Backup chứa 6 bảng nghiệp vụ cùng ảnh upload đang được sản phẩm tham chiếu (tối đa 16 MB tổng ảnh); không chứa source, .env, session, cache, danh sách backups hoặc ảnh catalog đi kèm source.

File .nestbackup chứa JSON nén được mã hóa/xác thực bằng APP_KEY, có SHA-256 và kiểm tra danh sách migration khi đọc. **Giữ APP_KEY của ứng dụng** và cả named volumes MySQL/MinIO; thay APP_KEY sẽ không đọc được bản cũ.

Phục hồi cần mật khẩu Admin hiện tại và chuỗi PHUC HOI <ID>. Hệ thống đọc/kiểm tra bản sao, tạo bản dự phòng, phục hồi DB trong transaction và ghi ảnh vào đường dẫn mới. Khi lỗi DB, rollback và dọn ảnh mới. Tài khoản/mật khẩu trở lại thời điểm sao lưu; cần đăng nhập lại. HTTP mutations được tuần tự hóa trong lúc xử lý dữ liệu.

Đây là phục hồi dữ liệu ứng dụng trên schema tương thích, không phải công cụ dựng lại toàn bộ máy chủ từ thảm họa. Không tự chạy phục hồi trên dữ liệu demo chỉ để kiểm tra; dùng database riêng.
## Các chức năng

- **Cửa hàng:** trang chủ, danh mục theo phòng, hàng mới trong 14 ngày, ưu đãi theo giá so sánh thực tế; tìm tên/mô tả, lọc danh mục/phòng/chất liệu/màu/khoảng giá/còn hàng/ưu đãi, sắp xếp và phân trang.
- **Chi tiết nội thất:** ảnh bìa, gallery/phóng to, kích thước, chất liệu, màu, bảo hành, hướng dẫn bảo quản, sản phẩm liên quan.
- **Giỏ hàng:** thêm/xem/sửa/xóa; thêm và cập nhật bằng AJAX, giới hạn theo tồn kho và tối đa 100 đơn vị mỗi sản phẩm. Giỏ tự đồng bộ khi hàng bị ẩn/hết hoặc tồn kho giảm.
- **Mua hàng:** Mua ngay, checkout COD, tính phí, lưu thông tin nhận hàng khi khách chọn, gợi ý tỉnh/thành qua API; vẫn nhập địa chỉ thủ công khi API lỗi.
- **Tài khoản:** đăng ký/đăng nhập/đăng xuất, sửa hồ sơ và thông tin nhận hàng mặc định.
- **Đơn cá nhân:** xem danh sách/chi tiết và timeline, sửa thông tin nhận hàng hoặc hủy đơn khi còn chờ xác nhận; hủy cần lý do.
- **Quản lý cửa hàng:** dashboard thống kê theo khoảng ngày, đơn gần đây/hàng sắp hết; CRUD danh mục/sản phẩm, upload ảnh bìa và gallery, quản lý hiển thị/nổi bật/tồn kho, tìm/lọc đơn và cập nhật trạng thái.
- **Trải nghiệm:** Inter lưu local, responsive, toast tự đóng, hộp thoại xác nhận, hiệu ứng ngắn và hỗ trợ prefers-reduced-motion.

Phân quyền qua middleware auth/admin/manager/customer; Customer chỉ thao tác trên đơn của chính mình. Không có chức năng quên mật khẩu trong giao diện dù database có bảng password_reset_tokens.


### Bảng sản phẩm và Excel

Bố cục workspace có bộ lọc nhóm hàng/phòng/trạng thái/tồn kho/khoảng giá, tìm tên/mã/barcode, sắp xếp, phân trang, chọn dòng và bật/tắt cột. Tùy chọn cột được nhớ trong trình duyệt. Thao tác nhóm chỉ đổi trạng thái bán/ẩn, không xóa hàng loạt.

Sản phẩm có thêm mã hàng tùy chọn, barcode, đơn vị tính, giá vốn và VAT. Nếu không nhập mã riêng, hệ thống hiển thị mã SP + ID. Giá bán vẫn là giá cuối hiển thị; VAT chỉ lưu để quản lý, không tự cộng thêm vào checkout.

- **Nhập Excel:** .xlsx tối đa 5 MB, 20 sheet, 2.000 dòng dữ liệu mỗi sheet. Chọn file → chọn sheet → Kiểm tra dữ liệu → xem dòng lỗi/giá/tồn → Nhập dữ liệu.
- Bắt buộc tên hàng, mã danh mục, giá bán, tồn kho. File mẫu có sheet hướng dẫn và danh sách mã danh mục.
- Mã trống tạo mới; mã đang có cập nhật. Giữ ảnh và các trường không có trong file.
- Chặn công thức, mã/barcode trùng, dữ liệu không hợp lệ. Có lỗi thì không nhập dòng nào. Nếu sản phẩm thay đổi sau khi xem trước, phải kiểm tra lại để tránh ghi đè.
- **Xuất Excel:** theo bộ lọc trên tất cả các trang (tối đa 5.000 sản phẩm), hoặc các dòng được chọn (tối đa 500). File XLSX giữ barcode dạng text và không biến tên hàng thành công thức.
- Phiên nhập riêng tư, gắn với người dùng/session, có hiệu lực một giờ; các file staging cũ được dọn khi người dùng tải file tiếp theo.

Contract JSON cho các request của giao diện: docs/contracts/product-transfer.openapi.json.

### Biểu đồ Tổng quan

Tổng quan cửa hàng chỉ có biểu đồ và bộ lọc, không có nút thêm sản phẩm/bảng đơn hàng/thẻ tồn kho. Chọn doanh thu đơn đã giao hoặc số đơn đặt, lọc hôm nay/7 ngày/tháng này/năm nay/tùy chọn (tối đa một năm). Biểu đồ nhóm theo tháng; mặc định Năm nay và trục January–December. Có hiệu ứng vẽ từ trái sang phải, đường dóng và tooltip; hỗ trợ chạm/bàn phím và giảm chuyển động.
## Quy tắc nghiệp vụ

- Phí vận chuyển 30.000đ; miễn phí khi tiền hàng từ 800.000đ.
- Trạng thái: pending → confirmed → shipping → delivered. Quản lý cửa hàng được hủy từ pending/confirmed; Customer chỉ được sửa/hủy khi pending.
- Checkout lấy giá ở database, khóa bản ghi, tạo order/items/event và trừ tồn trong một transaction. checkout_token duy nhất xử lý gửi lặp để không tạo hai đơn/trừ tồn hai lần.
- OrderWorkflow kiểm tra lại trạng thái trong database, ghi người thao tác/thời điểm/lý do và hoàn tồn khi hủy trong cùng transaction.
- Đơn lưu tên/giá/thông tin nhận hàng tại thời điểm mua; sửa sản phẩm/hồ sơ không viết lại dữ liệu đơn cũ.
- Xóa sản phẩm có lịch sử mua sẽ ẩn sản phẩm. Không xóa danh mục đang có sản phẩm.
- Ảnh upload JPG/PNG/WebP tối đa 3 MB/file; gallery tối đa 6 ảnh. Lưu database thất bại sẽ dọn ảnh mới, giữ ảnh cũ. Ảnh catalog dùng chung không bị xóa theo sản phẩm.

## Migration và seeder

Có **10 file migration**, tạo **6 bảng nghiệp vụ**: users, categories, products, orders, order_items, order_events; bảng hệ thống backups quản lý bản sao lưu; cùng bảng hỗ trợ password_reset_tokens và bảng migrations do Laravel quản lý.

Database mới sau seed có **3 tài khoản, 5 danh mục, 16 sản phẩm nội thất/trang trí**. Seeder không tạo đơn hàng giả; tạo đơn qua luồng mua hàng để demo.

- database/seeders/DatabaseSeeder.php: tài khoản và gọi catalog seeder.
- database/seeders/FurnitureCatalogSeeder.php: danh mục, sản phẩm và chuyển dữ liệu mẫu cũ.
- database/data/furniture.php: nội dung/thông số/giá mẫu.
- public/images/catalog/sources.json: nguồn ảnh minh họa và đường dẫn local.

```powershell
docker compose exec app php artisan migrate:status
docker compose exec app php artisan migrate --seed
```

## Cấu trúc mã nguồn

| Đường dẫn | Vai trò |
| --- | --- |
| app/Http/Controllers | Cửa hàng, giỏ, checkout, tài khoản và Admin |
| app/Http/Requests | Validation form |
| app/Http/Middleware | Phân quyền Admin/Customer |
| app/Models | Eloquent và quan hệ dữ liệu |
| app/Services/OrderWorkflow.php | Trạng thái đơn, thông tin nhận hàng, hoàn kho và lịch sử |
| app/Support/Cart.php | Chuẩn hóa giỏ Session |
| resources/views | Blade, layout, components |
| public/css, public/js | CSS/JavaScript dùng trực tiếp |
| docker, compose.yaml, scripts | Môi trường và cài đặt Docker |
| tests/Feature | Bộ kiểm thử nghiệp vụ |
| docs | Báo cáo, barem và kiểm thử |

Luồng xử lý: Browser → Route → Middleware → Controller/Service → Eloquent → MySQL → Blade/JSON.

## Kiểm thử

```powershell
docker compose exec -T app php vendor/bin/phpunit
docker compose exec app php artisan route:list
docker compose exec app php artisan view:cache
```

phpunit.xml ép SQLite :memory:; tests/TestCase.php chặn khởi chạy nếu cấu hình trỏ sang database khác. Không đổi test sang MySQL demo. Kết quả và giới hạn kiểm chứng được ghi ở [docs/VERIFICATION.md](docs/VERIFICATION.md); kịch bản demo ở [docs/KIEM_THU.md](docs/KIEM_THU.md).

## Chạy trực tiếp bằng Laragon/PHP (tùy chọn)

Cần PHP 8.2+, Composer 2, MySQL; PHP có pdo_mysql, mbstring, openssl, fileinfo, XML/DOM. Kiểm thử cần pdo_sqlite/sqlite3.

1. Mở thư mục có artisan; chạy composer install.
2. Nếu chưa có .env, sao chép .env.example; cấu hình DB_HOST/DB_PORT/DB_DATABASE/DB_USERNAME/DB_PASSWORD cho MySQL đang dùng và APP_URL=http://127.0.0.1:8000.
3. Tạo database nest_market (có thể dùng database/create_database.sql).
4. Chạy:

```powershell
php artisan key:generate
php artisan config:clear
php artisan migrate --seed
php artisan storage:link
php artisan serve
```

Chỉ tạo APP_KEY khi chưa có; không ghi đè .env đã cấu hình. Truy cập http://127.0.0.1:8000. Phương án này dùng storage/vendor ở host, khác với các volume Docker.

## Lỗi thường gặp

| Hiện tượng | Kiểm tra |
| --- | --- |
| Docker không kết nối | Mở Docker Desktop, chờ engine sẵn sàng |
| Cổng bị chiếm | Đổi APP_PORT/FORWARD_DB_PORT và DOCKER_APP_URL trong .env rồi chạy lại Compose |
| Thiếu vendor hoặc APP_KEY | Chạy script docker-setup.ps1 |
| MySQL báo sai tài khoản | Đối chiếu .env với tài khoản đã khởi tạo trong volume; đổi biến môi trường không tự đổi mật khẩu database cũ |
| Thiếu bảng | Kiểm tra migrate:status rồi migrate |
| Upload không hiển thị | Docker: kiểm tra app/Nginx cùng storage volume; chạy trực tiếp: kiểm tra storage:link |
| Blade hiển thị cũ | docker compose exec app php artisan view:clear, tải lại trình duyệt |
| Lỗi 419 | Kiểm tra cookies/session và dùng thống nhất localhost hoặc 127.0.0.1 |
| API tỉnh/thành lỗi | Nhập địa chỉ thủ công; API chỉ là gợi ý không bắt buộc |

## Tài liệu và giới hạn

- [Báo cáo kỹ thuật và sơ đồ](docs/BAO_CAO.md)
- [Đối chiếu barem](docs/BAREM.md)
- [Kiểm thử thủ công](docs/KIEM_THU.md)
- [Kết quả kiểm chứng](docs/VERIFICATION.md)
- [Trạng thái xử lý review](docs/REVIEW_2026-10-27.md)

Ảnh MOHO/Unsplash chỉ dùng minh họa đồ án, có ghi nguồn; không phải chứng nhận sản phẩm hay quyền đại diện thương hiệu. Asset giao diện được đóng gói local; Internet vẫn cần cho cài dependencies lần đầu và API tỉnh/thành.

Chưa có thanh toán online, voucher, đánh giá, hoàn tiền/đổi trả tự động, biến thể SKU, nhiều kho hay đồng bộ giỏ giữa thiết bị. Trang chính sách cung cấp thông tin demo, không tạo nghiệp vụ đổi trả. Không đưa .env, database dump, uploads riêng, cache/log lên GitHub; không công khai cấu hình debug và tài khoản demo như một hệ thống production.
