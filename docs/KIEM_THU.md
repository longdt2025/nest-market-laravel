# Checklist kiểm thử thủ công trên Laragon

Dùng cơ sở dữ liệu **demo** sau `migrate:fresh --seed`, không chạy `migrate:fresh` trên dữ liệu thật.

| ID | Thao tác | Kết quả mong đợi |
| --- | --- | --- |
| TC01 | Vào `/` | Trang chủ có hero, danh mục, sản phẩm nổi bật |
| TC02 | Vào `/san-pham?q=Kanso` | Hiển thị Bình gốm Kanso |
| TC03 | Vào sản phẩm theo danh mục | Lọc đúng, liên kết đến chi tiết đúng |
| TC04 | Admin login `admin@nest.test` | Chuyển `/admin`, thấy dashboard |
| TC05 | Customer login, mở `/admin` | Trả HTTP 403 |
| TC06 | Guest mở `/thanh-toan` | Chuyển trang đăng nhập |
| TC07 | Đăng ký email mới | Tạo User với role customer |
| TC08 | Admin thêm danh mục, thêm sản phẩm | Thấy trên trang cửa hàng khi đang bật hiển thị |
| TC09 | Nhập giá bán -10 / tồn kho -1 | Form validation báo lỗi |
| TC10 | Upload ảnh JPG hợp lệ | Ảnh lưu trong `storage/app/public/products` và hiển thị ở shop |
| TC11 | Thêm sản phẩm vào giỏ | Badge tăng mà trang không reload |
| TC12 | Cập nhật số lượng ở giỏ | Tổng tạm tính/tổng đơn cập nhật AJAX |
| TC13 | Thêm vượt stock | HTTP 422, hiển thị thông báo |
| TC14 | Checkout COD hợp lệ | Lưu order + items, stock giảm, trang lịch sử có đơn mới |
| TC15 | Checkout thiếu địa chỉ | Validation không tạo đơn |
| TC16 | Admin xác nhận rồi hủy đơn | Trạng thái chuyển hợp lệ và stock hoàn lại |
| TC17 | Admin cố hủy đơn đã giao | Hệ thống không cho phép |
| TC18 | User A truy cập đơn User B | HTTP 403 |
| TC19 | Admin xóa sản phẩm đã có order_items | Chỉ ẩn sản phẩm, không mất lịch sử |
| TC20 | Xem responsive 390px | Không tràn ngang, nút và menu sử dụng được |
| TC21 | Chọn tỉnh/thành checkout | Dữ liệu từ API hiển thị nếu có mạng; mất mạng vẫn nhập địa chỉ được |
| TC22 | Admin tìm kiếm, lọc, phân trang | Kết quả theo điều kiện đã nhập |

## Lệnh hỗ trợ

```powershell
php artisan route:list
php artisan migrate:status
php artisan test
php artisan storage:link
```

Nếu chạy PHPUnit bị lỗi SQLite, bật `extension=pdo_sqlite` và `extension=sqlite3` trong phiên bản PHP Laragon đang sử dụng. Test suite dùng SQLite **in memory**, không ảnh hưởng MySQL demo.
