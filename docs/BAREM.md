# Đối chiếu barem — Website bán hàng online

Cập nhật theo code ngày 28/10/2026. Bảng này chỉ ra minh chứng và cách demo; điểm thực tế do giảng viên đánh giá.

| Tiêu chí | Điểm tối đa | Minh chứng trong code | Trình diễn |
| --- | ---: | --- | --- |
| CRUD ít nhất 2 bảng liên quan | 1,5 | Admin CategoryController/ProductController, FK category_id | Thêm/sửa/xóa danh mục và sản phẩm; chặn xóa danh mục đang dùng |
| Đăng nhập/đăng xuất, phân quyền | 1,0 | AuthController, EnsureAdmin/EnsureCustomer, routes/web.php | Guest cần đăng nhập; Customer vào Admin nhận 403 |
| Tìm kiếm, phân trang | 1,0 | ShopController, Admin controllers, paginate/withQueryString | Tìm tên, kết hợp bộ lọc, đổi trang giữ điều kiện |
| Validation form | 0,5 | Form Requests, validate trong controllers | Sai giá/stock/email/địa chỉ/loại ảnh bị từ chối |
| Eloquent ORM | 1,0 | 6 model nghiệp vụ, scopes, relationships, eager loading | Giải thích truy vấn và quan hệ từ Controller/Service |
| Migration và Seeder | 1,0 | 8 migration, DatabaseSeeder, FurnitureCatalogSeeder | migrate:status; DB mới có 2 tài khoản, 5 danh mục, 16 sản phẩm |
| Controller, Middleware, Blade | 0,5 | Controllers, Middleware, resources/views và OrderWorkflow | Mô tả request → kiểm tra quyền → nghiệp vụ → dữ liệu → view |
| UI Bootstrap/Tailwind | 1,0 | Bootstrap 5.3.3 lưu local, app.css/storefront.css | Cửa hàng, checkout và Admin |
| Responsive | 0,5 | Grid/media queries trong CSS | Kiểm tra desktop/tablet/điện thoại và các form |
| Báo cáo, ERD, Use Case, ảnh | 0,5 | BAO_CAO.md có ERD/luồng/tác nhân | Bổ sung báo cáo theo mẫu, UML và ảnh chụp thật |
| Thuyết trình/vấn đáp | 0,5 | Câu hỏi và demo trong BAO_CAO/KIEM_THU | Thành viên giải thích phần mình làm |
| Upload file | 0,5* | ProductRequest, upload cover/gallery, public disk | Tải ảnh, thay gallery, thử file sai |
| AJAX | 0,5* | app.js và CartController | Thêm/đổi số lượng giỏ không reload |
| API ngoài | 0,5* | ProvinceController, gợi ý tỉnh/thành ở checkout | Có mạng tải gợi ý; lỗi vẫn nhập địa chỉ |
| Tối ưu khác | 0,5* | Eager loading, API cache, transaction, row lock, token | Giải thích tránh truy vấn thừa và giữ tính nhất quán |

*Nhóm Sáng tạo & Mở rộng trong barem có mức tối đa 1,0 điểm dù liệt kê bốn nội dung 0,5. Không tự cộng thành 2,0 điểm. Docker không có dòng chấm điểm riêng trong barem đã cung cấp.*

## Minh chứng nghiệp vụ bổ sung

- Bộ lọc nội thất, hàng mới 14 ngày, ưu đãi theo compare_at_price, gallery/phóng to và thông số.
- Hồ sơ, nhận hàng mặc định, sửa/hủy đơn pending và lịch sử có actor/thời gian/lý do.
- Checkout tính tiền từ DB, token chống tạo đơn trùng, hoàn tồn một lần.
- Xử lý lỗi lưu ảnh, giữ dữ liệu seed đã sửa và bảo toàn lịch sử sản phẩm.
- Toast, dialog, animation ngắn, reduced motion và asset local.

## Giới hạn cần nói đúng khi bảo vệ

COD là mô phỏng. Chưa có thanh toán online, biến thể SKU, đánh giá, nhiều kho hoặc workflow đổi trả/hoàn tiền. Chính sách là trang thông tin. Giỏ Session không đồng bộ giữa thiết bị. Có bảng password_reset_tokens nhưng chưa triển khai quên mật khẩu.

Báo cáo cần thể hiện đúng sản phẩm nội thất, stack Docker/MySQL và 6 bảng nghiệp vụ, tránh mô tả cũ về Laragon bắt buộc hoặc Bootstrap/Fonts qua CDN. Kết quả kiểm chứng xem [VERIFICATION.md](VERIFICATION.md).
