# Đối chiếu barem — Website Bán hàng Online (10 điểm)

> Đây là bảng **đối chiếu mã nguồn** với yêu cầu, không phải lời xác nhận đã được chấm điểm hoặc đã chạy trên máy người dùng.

| Yêu cầu | Điểm tối đa | Minh chứng trong project | Cách trình diễn |
| --- | ---: | --- | --- |
| CRUD ≥2 bảng có quan hệ | 1,5 | `CategoryController`, `ProductController`, `Category::products()`, `Product::category()` | Admin tạo danh mục, thêm/sửa/xóa sản phẩm, thử xóa danh mục đang có SP (bị chặn) |
| Đăng nhập/đăng xuất & 2 role | 1,0 | `AuthController`, `EnsureAdmin`, `EnsureCustomer`, `routes/web.php` | Login Admin/Customer rồi truy cập `/admin` bằng Customer (403) |
| Tìm kiếm & phân trang | 1,0 | `ShopController::index`, `ProductController::index`, Blade `->links()` | Tìm tên, lọc danh mục, chọn sắp xếp; xem các trang khi đủ SP |
| Validation Form | 0,5 | `CategoryRequest`, `ProductRequest`, `CheckoutRequest`, `AuthController` | Giá âm / email trùng / thiếu địa chỉ → thông báo lỗi |
| Eloquent ORM | 1,0 | Models + `with()`, `whereHas()`, `belongsTo()`, `hasMany()`, `lockForUpdate()` | Giải thích truy vấn từ Controller đến Model |
| Migration + Seeder | 1,0 | `database/migrations`, `DatabaseSeeder.php` | Chạy `php artisan migrate:fresh --seed` (chỉ trên DB demo) |
| Controller + Middleware + Blade | 0,5 | `app/Http/Controllers`, `app/Http/Middleware`, `resources/views` | Mở route và mô tả request → controller → model → view |
| Bootstrap/Tailwind UI | 1,0 | Bootstrap 5 CDN + `public/css/app.css` | Hiển thị storefront, admin, login, checkout |
| Responsive | 0,5 | Bootstrap grid + media queries 1180/920/720px/400px | Chrome DevTools các kích thước 1440, 768, 390px |
| Báo cáo, ERD, Use Case, ảnh màn hình | 0,5 | `docs/BAO_CAO.md` có mục nội dung và Mermaid; ảnh phải chụp từ ứng dụng thật | Chèn ảnh UI và chú thích, trình bày ERD |
| Thuyết trình/vấn đáp | 0,5 | `docs/BAO_CAO.md` — phần câu hỏi trả lời | Demo luồng mua hàng từ Customer đến Admin |
| Upload file | 0,5* | `Admin/ProductController`, `ProductRequest`, `storage:link` | Admin chọn ảnh, lưu, thấy ảnh mới ở trang khách |
| AJAX | 0,5* | `public/js/app.js`, `CartController` | Thêm giỏ + đổi số lượng không tải lại trang |
| API ngoài | 0,5* | `ProvinceController`, `/tinh-thanh` → Province Open API v2 | Tại Checkout chọn tỉnh/thành qua AJAX (cần kết nối mạng) |
| Tối ưu khác | 0,5* | `with()` eager loading, DB transaction, `lockForUpdate`, caching API | Giải thích tránh N+1, chống đặt vượt tồn kho |

**Lưu ý quan trọng:** trong ảnh barem, mục *Sáng tạo & Mở rộng* có 4 nội dung × 0,5 điểm = 2 điểm nhưng **điểm tối đa của tiêu chí là 1 điểm**. Cần hỏi giảng viên cách quy đổi; không cộng mặc định 2 điểm.

## Phân quyền và nghiệp vụ đã triển khai

- Guest: xem sản phẩm và tạo giỏ hàng trong Session.
- Customer: mua hàng, xem lịch sử **chỉ của mình**.
- Admin: dashboard, CRUD category/product, quản lý đơn, không dùng trang checkout.
- Mọi role tạo qua đăng ký là `customer`; chỉ Seeder demo tạo `admin`.
- Đặt hàng: kiểm tra giá/tồn kho ở server, khóa bản ghi và lưu order + items trong cùng transaction, trừ tồn khi đặt, hoàn tồn khi Admin hủy.
- Hủy chỉ từ trạng thái `pending` hoặc `confirmed`, không cho hủy khi đã giao.
- Xóa sản phẩm đã có lịch sử bán: ẩn thay vì xóa để giữ khóa ngoại và dữ liệu đơn.

## Phạm vi cố ý giới hạn

- Thanh toán COD **mô phỏng**; không tích hợp ví/thẻ thật.
- Session cart không đồng bộ giữa thiết bị.
- Không quản lý biến thể màu/size và nhiều kho.
- Ảnh từ Unsplash là ảnh minh họa; không dùng làm chứng nhận nguồn gốc sản phẩm.
- Trang chủ `is_featured` do Admin tùy chỉnh.
