# Kết quả kiểm chứng

## Bổ sung sản phẩm/Excel và biểu đồ — 30/10/2026

- Bộ PHPUnit: **74 test / 473 assertions qua** trên SQLite :memory:.
- Test Excel: scan sheet, kiểm tra dòng, chặn lỗi toàn bộ, nhập mới/cập nhật, chống gửi lặp, file công thức, mã/barcode trùng, dữ liệu thay đổi sau preview, phiên và phân quyền.
- Đọc lại XLSX xuất để xác nhận đúng dòng theo bộ lọc/lựa chọn, barcode giữ số 0 và tên bắt đầu "=" không thành công thức.
- UI đã mở hộp chọn cột, đổi cột; upload XLSX thật, chọn sheet và kiểm tra hai dòng cập nhật hợp lệ; không commit thử lên dữ liệu demo.
- Xuất một sản phẩm được chọn trên trình duyệt tạo file XLSX tải xuống thành công.
- UI biểu đồ có 12 nhãn January–December; chọn điểm October hiển thị tooltip số đơn đúng. JavaScript có reveal animation 1,1 giây và reduced-motion.
- Migration thứ 10 thêm sku/barcode/unit/cost_price/vat_rate. Bản backup trước migration có schema cũ; cần tạo bản mới sau cập nhật.

## Bản cập nhật workspace — 30/10/2026

- PHPUnit: **64 test / 397 assertions đều qua**, SQLite :memory:.
- Đã thử quyền đọc/ghi riêng Admin/Manager, tài khoản khóa và phiên đang hoạt động, hồ sơ/mật khẩu, báo cáo theo khoảng ngày.
- Kiểm thử backup roundtrip gồm dữ liệu/ảnh, checksum bị sửa, MinIO lỗi, mật khẩu/xác nhận sai và rollback phục hồi lỗi.
- Kiểm tra riêng **MySQL thật + MinIO thật**: backup → sửa tồn → restore khôi phục đúng dữ liệu/ảnh; trigger gây lỗi restore xác nhận rollback. Database thử nghiệm và object backup thử đã được dọn.
- Endpoint S3 không kết nối được: không tạo bản ghi thành công, dữ liệu hiện tại giữ nguyên.
- Website HTTP 200; truy cập bucket ẩn danh HTTP 403; 4 container healthy; 9 migration đã áp dụng.
- UI Admin: login, menu tài khoản theo mẫu, danh sách tài khoản, tạo backup thật #1 và đọc lại checksum/cấu trúc hợp lệ.
- UI Manager: login, dashboard, lọc trạng thái đơn, menu mobile; bảng cuộn riêng không tràn trang trong khung hẹp. Chưa khẳng định thử thiết bị điện thoại thật.
- JavaScript workspace qua node --check; Blade view:cache và nginx -t thành công.
- Không thực hiện phục hồi trên database demo đang dùng. Lần kiểm tra MySQL/MinIO sử dụng database riêng.

## Ghi nhận trước khi tách vai trò
Cập nhật ngày **28/10/2026** (Asia/Saigon), theo source hiện tại. Đây là ghi nhận kiểm tra, không phải cam kết mọi lỗi đều đã hết.

## Đã chạy lại khi cập nhật tài liệu

- PHPUnit 11.5.57 trong container PHP 8.3.36: **OK (51 tests, 313 assertions)**.
- Database kiểm thử: SQLite :memory:, ép cấu hình trong phpunit.xml và có guard trong tests/TestCase.php.
- Bộ test bao phủ quyền truy cập, CRUD, tìm kiếm, giỏ/checkout, token, giá/tồn, hồ sơ, lịch sử/sửa/hủy đơn, ảnh upload/gallery và seeder.
- API tỉnh/thành trong test được giả lập bằng Http::fake(); kết quả này không xác nhận dịch vụ bên ngoài luôn hoạt động.

Lệnh:

```powershell
docker compose exec -T app php vendor/bin/phpunit
```

## Database và môi trường đã xác nhận

- Docker PHP, Nginx, MySQL đều healthy; trang chủ http://localhost:8082 trả HTTP 200.
- 8/8 migration có trạng thái Ran.
- Database demo hiện tại có 7 danh mục, 27 sản phẩm, trong đó 16 sản phẩm active, cùng hai tài khoản demo.
- Số lượng trên database đang dùng bao gồm dữ liệu mẫu cũ. Database mới theo seeder có 5 danh mục, 16 sản phẩm, 2 tài khoản, không tạo đơn giả.
- Compose cấu hình website ở 127.0.0.1:8082 và MySQL host ở 127.0.0.1:13306; Laravel kết nối mysql:3306 nội bộ.

## Các lần kiểm tra trước trong quá trình hoàn thiện

- Kiểm tra đồng thời trên MySQL thử nghiệm riêng cho checkout và hủy đơn Admin/Customer: không tạo đơn lặp/trừ tồn lặp hoặc hoàn tồn hai lần trong các ca đã chạy. Các ca này không nằm trong PHPUnit SQLite thông thường.
- UI đã kiểm tra trang chủ, bộ lọc phòng/chất liệu/giá/tồn, hero trước/sau, thumbnail/phóng to, Mua ngay đến checkout và hồ sơ điền sẵn.
- Khung trình duyệt hẹp khoảng 525 px nội dung đã kiểm tra menu, bộ lọc thu gọn, lưới 2 cột và không tràn ngang; có kiểm tra desktop rộng.
- Đã thử sửa nhận hàng và hủy pending, hiển thị lịch sử và lý do trong giao diện.
- JavaScript đã qua node --check; Blade đã biên dịch bằng artisan view:cache.
- Docker/nginx đã kiểm tra cấu hình; giới hạn upload 3 MB/file, 24 MB/request.
- Font Inter và ảnh được đóng gói local; ảnh đang tải trên trang chủ tại lần kiểm tra không có ảnh lỗi.

Những kiểm tra lịch sử trên không có nghĩa đã tự động chạy lại tất cả khi chỉ sửa tài liệu.

## Những việc vẫn cần khi bàn giao/nộp bài

- Cài từ clone sạch trên máy thành viên khác bằng script setup và lưu minh chứng.
- Rà đúng các kích thước 390/768/1440 px, bàn phím và thiết bị thực tế; không gọi khung hẹp desktop là điện thoại thật.
- Thử Province API thật tại thời điểm trình diễn; kiểm tra thêm trạng thái mất mạng và cache.
- Hoàn thành báo cáo theo mẫu trường, ảnh UI, phân công và UML Use Case đúng yêu cầu.
- Kiểm thử hiệu năng, bảo mật chuyên sâu và triển khai production nằm ngoài xác nhận hiện tại.

## Cách tái kiểm tra

```powershell
docker compose ps
docker compose exec app php artisan migrate:status
docker compose exec app php artisan route:list
docker compose exec -T app php vendor/bin/phpunit
docker compose exec app php artisan view:cache
node --check public/js/app.js
node --check public/js/storefront.js
```

Hai lệnh node cuối cần Node.js trên máy kiểm tra; Node.js không bắt buộc để chạy website. Không dùng migrate:fresh hoặc down -v để kiểm tra trên dữ liệu demo đang dùng.
