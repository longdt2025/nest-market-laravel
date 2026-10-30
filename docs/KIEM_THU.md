# Kịch bản kiểm thử và demo

> Cập nhật 30/10/2026: hệ thống có 3 vai trò. Admin hệ thống ở /admin chỉ quản lý tài khoản và sao lưu/phục hồi MinIO. Các nghiệp vụ bán hàng trước đây mang tên Admin nay thuộc Quản lý cửa hàng ở /quan-ly. Customer giữ luồng mua hàng. Account demo: admin/admin123, manager/Password@123, customer@nest.test/Password@123.
>
> Có 9 migration, 6 bảng nghiệp vụ và bảng hệ thống backups. MinIO Console localhost:19001, bucket riêng tư nest-market-backups. Sao lưu dữ liệu/ảnh upload được mã hóa bằng APP_KEY; phục hồi yêu cầu mật khẩu, xác nhận, bản dự phòng và transaction. Menu tài khoản có Đổi mật khẩu, Cập nhật thông tin, Đăng xuất.

Cập nhật 28/10/2026. Môi trường chính: Docker tại http://localhost:8082, MySQL host 127.0.0.1:13306. Không cần Laragon. Tài khoản demo và cài đặt xem [README](../README.md).

Các dòng dưới là **kịch bản cần kiểm tra**, không phải khẳng định từng dòng đã chạy thủ công. Kết quả đã xác nhận xem [VERIFICATION.md](VERIFICATION.md). Dùng dữ liệu demo, không reset database đang có dữ liệu cần giữ.

## Cửa hàng, giỏ và checkout

| ID | Thao tác | Kết quả mong đợi |
| --- | --- | --- |
| TC01 | Mở trang chủ | Hero, phòng, ưu đãi và sản phẩm từ database hiển thị |
| TC02 | Tìm Kanso | Thấy Ly gốm Kanso khi sản phẩm còn hiển thị |
| TC03 | Kết hợp phòng/chất liệu/màu/giá/còn hàng | Kết quả thỏa tất cả điều kiện, giữ điều kiện khi phân trang |
| TC04 | Nhập giá tối đa nhỏ hơn tối thiểu | Validation báo lỗi |
| TC05 | Mở /hang-moi | Chỉ hàng visible được tạo trong 14 ngày gần đây; không có thì có empty state |
| TC06 | Mở /uu-dai | Chỉ hàng có compare_at_price lớn hơn price |
| TC07 | Xem chi tiết, đổi thumbnail, phóng to | Ảnh đúng lựa chọn, thông số/stock/bảo hành hiển thị |
| TC08 | Thêm hàng bằng nút trên card | Badge và toast cập nhật, trang không reload |
| TC09 | Sửa số lượng giỏ | Tạm tính/phí/tổng cập nhật theo server |
| TC10 | Thêm vượt tồn hoặc cộng dồn quá 100 | Bị từ chối, không lưu lượng vượt giới hạn |
| TC11 | Giảm stock hoặc ẩn hàng đã có trong giỏ | Giỏ Session đồng bộ theo lượng hợp lệ và thông báo thay đổi |
| TC12 | Xóa hàng cuối trong giỏ | Empty state; checkout không tạo đơn trống |
| TC13 | Chọn Mua ngay | Thêm lượng hợp lệ rồi đến checkout; Guest đăng nhập trước |
| TC14 | Checkout thiếu/sai số điện thoại hoặc địa chỉ | Validation không tạo order và không trừ tồn |
| TC15 | Đặt COD hợp lệ | Tạo order/items/event, trừ đúng tồn, chuyển chi tiết đơn |
| TC16 | Kiểm tra dưới và từ 800.000đ tiền hàng | Phí lần lượt 30.000đ và 0đ |
| TC17 | Gửi lại cùng checkout_token | Trả đơn đã tạo, không tạo/trừ tồn lần hai |
| TC18 | Giá/stock đổi trước khi gửi checkout | Server tính theo DB, không nhận tổng tiền giả hoặc bán vượt tồn |
| TC19 | Tải/chọn tỉnh thành ở checkout | Có gợi ý nếu API hoạt động; lỗi vẫn nhập địa chỉ thủ công |

## Tài khoản và tự phục vụ

| ID | Thao tác | Kết quả mong đợi |
| --- | --- | --- |
| TC20 | Đăng ký email mới; thử email trùng/mật khẩu yếu | Hợp lệ tạo Customer; dữ liệu sai bị chặn |
| TC21 | Guest mở checkout; Customer mở /quan-ly | Lần lượt chuyển login và trả 403 |
| TC22 | Sửa hồ sơ/thông tin mặc định | Chỉ cập nhật trường cho phép, checkout điền sẵn |
| TC23 | Đặt đơn có/không chọn lưu địa chỉ | Chỉ cập nhật mặc định khi khách chọn |
| TC24 | Customer A mở/sửa/hủy đơn Customer B | Bị chặn 403 |
| TC25 | Sửa nhận hàng khi pending | Lưu thông tin và event; không đổi tổng tiền hoặc tự sửa hồ sơ |
| TC26 | Gửi lại thông tin nhận hàng giống cũ | Không tạo event dư |
| TC27 | Hủy pending với lý do hợp lệ | Đơn cancelled, hoàn tồn một lần, có lý do trong timeline |
| TC28 | Hủy thiếu lý do hoặc sau khi Quản lý cửa hàng xác nhận | Bị từ chối, đơn/kho không đổi |

## Quản lý cửa hàng và dữ liệu

| ID | Thao tác | Kết quả mong đợi |
| --- | --- | --- |
| TC29 | Quản lý cửa hàng đăng nhập | Đến dashboard, có số liệu/đơn gần đây/cảnh báo hàng sắp hết |
| TC30 | CRUD danh mục, thử xóa danh mục có sản phẩm | Thao tác hợp lệ lưu được; danh mục đang dùng không xóa được |
| TC31 | CRUD sản phẩm; tìm/lọc/phân trang | Kết quả đúng dữ liệu và quyền |
| TC32 | Upload cover và gallery, thay/xóa gallery | Ảnh mới hiển thị; cover giữ nguyên nếu không thay |
| TC33 | Upload ảnh sai loại/quá 3 MB, gallery trên 6 ảnh | Validation từ chối |
| TC34 | Quản lý cửa hàng đổi trạng thái đúng/sai quy trình | Chỉ nhận chuyển hợp lệ, ghi actor/thời gian/history |
| TC35 | Quản lý cửa hàng hủy pending/confirmed rồi thử hủy lại | Hoàn tồn một lần; không hủy đơn shipping/delivered |
| TC36 | Xóa sản phẩm đã có order_items | Chỉ ẩn, giữ lịch sử đơn |
| TC37 | Chạy lại db:seed sau khi sửa dữ liệu mẫu | Giữ chỉnh sửa mật khẩu/giá/tồn/ảnh/thông số |
| TC38 | Tắt rồi bật container | Dữ liệu/orders/uploads còn trong volumes |

## UI, cài đặt và trình diễn

| ID | Thao tác | Kết quả mong đợi |
| --- | --- | --- |
| TC39 | Kiểm tra tại 390, 768 và 1440 px | Không tràn; menu, form và bảng thao tác được |
| TC40 | Toast, dialog, chuyển trang và đổi ảnh | Hiệu ứng ngắn; đóng bằng nút phù hợp, không cản thao tác |
| TC41 | Bật prefers-reduced-motion | Hạn chế hiệu ứng theo cấu hình |
| TC42 | Chạy source trên máy mới bằng script Docker | Cài dependency, migrate, seed DB mới và mở website được |
| TC43 | Mở các trang khi mất Internet | Asset local hiển thị; gợi ý API có thể không tải |

## Lệnh kiểm tra

Chạy từ thư mục chứa compose.yaml:

```powershell
docker compose ps
docker compose exec app php artisan migrate:status
docker compose exec app php artisan route:list
docker compose exec -T app php vendor/bin/phpunit
docker compose exec app php artisan view:cache
```

Bộ PHPUnit dùng SQLite :memory: và có guard trong tests/TestCase.php. Các test có thể gây lỗi database/upload có chủ đích trong môi trường tách biệt, không áp dụng lên DB demo. Không đổi cấu hình test sang MySQL demo để kiểm tra concurrency; cần database thử nghiệm riêng.

Khi nộp báo cáo, ghi kết quả thực tế từng ca, môi trường, ngày chạy và ảnh minh chứng. Không dùng migrate:fresh để chuẩn bị demo trên database đang dùng.

## Kịch bản bảo vệ ngắn

1. Guest tìm/lọc nội thất, xem gallery và thông số.
2. Customer đăng nhập, mua COD, xem timeline và hồ sơ.
3. Tạo đơn demo khác để sửa thông tin rồi hủy khi pending.
4. Quản lý cửa hàng thêm sản phẩm có ảnh, xác nhận/giao/hoàn tất một đơn.
5. Giải thích kiểm soát tồn, chống gửi lặp, ownership và rollback.
6. Chạy migrate:status/PHPUnit; trình bày ERD, Middleware, Eloquent và Docker.
