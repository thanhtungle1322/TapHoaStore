# 🛒 Tạp Hóa Store - Website Bán Hàng E-Commerce Native PHP & MySQL

Website thương mại điện tử / bán hàng tạp hóa hoàn chỉnh, được xây dựng bằng **PHP thuần (Native PHP 7.4/8.x)** và **MySQL (PDO)**, kết hợp giao diện hiện đại, chuẩn Responsive bằng **TailwindCSS CDN** và **FontAwesome 6**.

Hệ thống được tối ưu hóa đặc biệt để **deploy ngay lập tức** lên **InfinityFree** (Web Hosting) và **MonsterASP** (Database Hosting) mà không cần Composer hay quyền SSH.

---

## 📁 Cấu Trúc Thư Mục Dự Án

```
TapHoaStore/
│
├── config/
│   └── database.php         # Kết nối PDO tới MonsterASP MySQL (có giao diện báo lỗi thân thiện)
│
├── includes/
│   ├── functions.php        # Bộ hàm tiện ích: Auth, Session, Cart, Upload ảnh, XSS sanitize
│   ├── header.php           # Header chung: Navbar, Thanh tìm kiếm, Badge giỏ hàng, Mobile menu
│   └── footer.php           # Footer chung: Thông tin liên hệ, chính sách, bản quyền, JS toggle
│
├── uploads/
│   ├── .gitkeep             # Giữ thư mục trên Git
│   └── .htaccess            # Cấm thực thi script PHP trái phép trong thư mục ảnh
│
├── auth/
│   ├── register.php         # Trang Đăng ký (Mã hóa mật khẩu bằng password_hash)
│   ├── login.php            # Trang Đăng nhập (Xác thực bằng password_verify)
│   └── logout.php           # Đăng xuất và xóa phiên session
│
├── products/
│   ├── create.php           # Form Đăng bán sản phẩm (hỗ trợ upload ảnh hoặc dán URL ảnh)
│   ├── manage.php           # Quản lý danh sách sản phẩm của người bán
│   ├── edit.php             # Chỉnh sửa giá, tồn kho, mô tả và đổi ảnh sản phẩm
│   └── delete.php           # Xóa sản phẩm và tự động dọn dẹp file ảnh trong uploads/
│
├── orders/
│   ├── checkout.php         # Trang thanh toán (Hỗ trợ COD & Quét mã VietQR ngân hàng)
│   ├── my_orders.php        # Lịch sử mua hàng của khách hàng kèm trạng thái đơn
│   ├── order_detail.php     # Chi tiết đơn hàng, tự động tạo mã QR VietQR theo số tiền
│   └── manage_orders.php    # Quản trị viên/Người bán xem doanh thu & cập nhật tiến độ đơn
│
├── index.php                # Trang chủ: Banner, lọc danh mục, tìm kiếm, Grid thẻ sản phẩm
├── product_detail.php       # Xem chi tiết sản phẩm, chọn số lượng, thêm giỏ / mua ngay
├── cart.php                 # Quản lý giỏ hàng (tăng/giảm số lượng, xóa món, tính tổng tiền)
├── schema.sql               # Script SQL tạo bảng (users, products, orders, order_items) & seed data
├── .htaccess                # Cấu hình bảo mật Apache, mã hóa UTF-8, chặn file nhạy cảm
└── README.md                # Tài liệu hướng dẫn sử dụng & triển khai
```

---

## 🔑 Tài Khoản Mẫu Trải Nghiệm (Đã có sẵn trong `schema.sql`)

| Vai trò | Email đăng nhập | Mật khẩu | Quyền hạn |
| :--- | :--- | :--- | :--- |
| **Admin / Người bán** | `admin@taphoa.vn` | `admin123` | Đăng sản phẩm, sửa/xóa mọi sản phẩm, quản lý tất cả đơn hàng & cập nhật trạng thái |
| **Khách hàng** | `khachhang@taphoa.vn` | `123456` | Mua sắm, thêm giỏ hàng, đặt hàng, xem lịch sử đơn mua |

---

## 🚀 Hướng Dẫn Deploy Lên MonsterASP & InfinityFree (Từng Bước Chi Tiết)

### BƯỚC 1: Khởi Tạo Cơ Sở Dữ Liệu Trên MonsterASP

1. Đăng nhập vào bảng điều khiển **MonsterASP Control Panel**.
2. Tìm đến mục **MySQL Databases** và mở công cụ **phpMyAdmin** của database `db68961`.
3. Chọn database **`db68961`** ở cột bên trái.
4. Nhấn vào tab **Import** (Nhập) trên thanh menu ngang.
5. Nhấn **Choose File** và chọn file [`schema.sql`](file:///d:/TapHoaStore/schema.sql) trong thư mục dự án.
6. Nhấn nút **Import** (hoặc **Go**) ở cuối trang.
   - *Kết quả:* Hệ thống sẽ tạo thành công 4 bảng: `users`, `products`, `orders`, `order_items` cùng toàn bộ dữ liệu sản phẩm mẫu phong phú.

---

### BƯỚC 2: Kiểm Tra File Cấu Hình Kết Nối

Mở file [`config/database.php`](file:///d:/TapHoaStore/config/database.php). Các thông số kết nối MonsterASP của bạn đã được cấu hình sẵn:

```php
define('DB_HOST', '');
define('DB_PORT', '3306');
define('DB_NAME', '');
define('DB_USER', '');
define('DB_PASS', '');
```

> **Lưu ý:** Cổng kết nối là `3306` (chuẩn của MySQL). InfinityFree cho phép kết nối ra CSDL bên ngoài qua cổng 3306 này.

---

### BƯỚC 3: Tải Mã Nguồn Lên Web Hosting InfinityFree

Bạn có thể tải code lên theo 1 trong 2 cách sau:

#### Cách 1: Dùng File Manager trên InfinityFree (Nhanh nhất)
1. Nén toàn bộ nội dung trong thư mục `TapHoaStore` thành file `.zip` (Lưu ý: nén các file và thư mục bên trong, không nén cả thư mục cha).
2. Đăng nhập vào tài khoản **InfinityFree**, vào mục **Control Panel** -> mở **Online File Manager**.
3. Mở thư mục **`htdocs/`** (thư mục gốc chứa website).
4. Nhấn biểu tượng **Upload Zip** và chọn file zip vừa nén để upload. Hệ thống sẽ tự động giải nén tất cả các file ngay trong `htdocs/`.

#### Cách 2: Dùng phần mềm FTP (FileZilla hoặc WinSCP)
1. Lấy thông tin FTP trong trang quản lý tài khoản InfinityFree:
   - **FTP Hostname:** `ftpupload.net`
   - **FTP Username:** (Mã tài khoản dạng `epiz_xxxxxxx`)
   - **FTP Password:** (Mật khẩu tài khoản hosting)
   - **Port:** `21`
2. Mở FileZilla, kết nối tới server.
3. Ở cửa sổ bên phải (Remote site), điều hướng vào thư mục **`htdocs/`**.
4. Kéo toàn bộ các file và thư mục của dự án thả vào `htdocs/`.

---

### BƯỚC 4: Phân Quyền Thư Mục Upload Ảnh

1. Trong File Manager của InfinityFree, tìm thư mục **`uploads`**.
2. Nhấp chuột phải vào thư mục `uploads` -> chọn **Permissions** (hoặc **Chmod**).
3. Đặt quyền thành **`755`** (hoặc **`777`**) và tích chọn áp dụng cho các thư mục con để đảm bảo PHP có quyền lưu ảnh sản phẩm khi người dùng tải lên.

---

### BƯỚC 5: Trải Nghiệm Website

Truy cập vào tên miền miễn phí do InfinityFree cung cấp (Ví dụ: `http://tencuaban.infinityfreeapp.com`):
- Màn hình trang chủ hiển thị danh sách sản phẩm với giá bán, tồn kho, bộ lọc theo danh mục.
- Thử nghiệm đăng ký tài khoản mới hoặc đăng nhập tài khoản mẫu `admin@taphoa.vn` / `admin123`.
- Bấm vào sản phẩm, thêm vào giỏ hàng, tiến hành thanh toán chọn hình thức chuyển khoản VietQR để thấy mã QR thanh toán ngân hàng tự động.
- Vào menu Admin để cập nhật trạng thái đơn hàng.

---

## 🛡️ Các Tính Năng Bảo Mật Đã Tích Hợp

1. **Phòng chống SQL Injection:** Sử dụng 100% **PDO Prepared Statements** kết hợp tham số ẩn danh (`?`), tuyệt đối không ghép chuỗi SQL trực tiếp.
2. **Phòng chống XSS (Cross-Site Scripting):** Mọi dữ liệu do người dùng nhập khi hiển thị ra HTML đều được xử lý qua hàm `e()` (`htmlspecialchars(..., ENT_QUOTES, 'UTF-8')`).
3. **Bảo mật mật khẩu:** Dùng hàm băm chuẩn công nghiệp `password_hash($pass, PASSWORD_DEFAULT)` và kiểm tra bằng `password_verify()`.
4. **Bảo mật thư mục Uploads:** File `uploads/.htaccess` chặn thực thi mọi file script có đuôi `.php`, `.phtml`, `.cgi`, tránh tin tặc upload shell mã độc.
5. **Giao dịch toàn vẹn (Database Transactions):** Khi khách đặt hàng, hệ thống dùng `beginTransaction()`, `commit()`, `rollBack()` để đảm bảo tiền hàng, chi tiết đơn và số lượng trừ kho luôn chính xác, không bị lỗi dữ liệu nửa chừng.

---

## ❓ Câu Hỏi Thường Gặp (Troubleshooting)

- **Hỏi: Trang web báo lỗi "Chưa Thể Kết Nối Cơ Sở Dữ Liệu"?**
  - **Trả lời:** Kiểm tra lại bước 1 xem bạn đã import file `schema.sql` vào MonsterASP chưa. Đảm bảo tên database `db68961` và mật khẩu trong `config/database.php` khớp chính xác.
- **Hỏi: Tôi có thể dùng CSDL MySQL tích hợp sẵn của InfinityFree không?**
  - **Trả lời:** Hoàn toàn được! Nếu bạn muốn dùng MySQL của InfinityFree thay vì MonsterASP, bạn chỉ cần vào Control Panel InfinityFree -> MySQL Databases -> Tạo database và đổi 4 dòng thông số trong `config/database.php` (DB_HOST, DB_NAME, DB_USER, DB_PASS) theo thông số InfinityFree cấp là xong.
