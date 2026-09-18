-- =======================================================
-- TAPHOASTORE - CƠ SỞ DỮ LIỆU MYSQL (MONSTERASP / INFINITYFREE)
-- Bảng: users, products, orders, order_items
-- =======================================================

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS `order_items`;
DROP TABLE IF EXISTS `orders`;
DROP TABLE IF EXISTS `products`;
DROP TABLE IF EXISTS `users`;
SET FOREIGN_KEY_CHECKS = 1;

-- -------------------------------------------------------
-- 1. BẢNG USERS (Người dùng & Quản trị viên/Người bán)
-- -------------------------------------------------------
CREATE TABLE `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(150) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `phone` VARCHAR(20) DEFAULT NULL,
    `address` VARCHAR(255) DEFAULT NULL,
    `role` ENUM('user', 'admin') DEFAULT 'user',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------
-- 2. BẢNG PRODUCTS (Sản phẩm đăng bán)
-- -------------------------------------------------------
CREATE TABLE `products` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `name` VARCHAR(200) NOT NULL,
    `slug` VARCHAR(250) NOT NULL,
    `category` VARCHAR(100) NOT NULL DEFAULT 'Khác',
    `description` TEXT DEFAULT NULL,
    `price` DECIMAL(12, 0) NOT NULL DEFAULT 0,
    `stock` INT NOT NULL DEFAULT 0,
    `image_url` VARCHAR(255) DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT `fk_products_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------
-- 3. BẢNG ORDERS (Đơn hàng)
-- -------------------------------------------------------
CREATE TABLE `orders` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT DEFAULT NULL,
    `customer_name` VARCHAR(100) NOT NULL,
    `customer_phone` VARCHAR(20) NOT NULL,
    `customer_address` VARCHAR(255) NOT NULL,
    `customer_note` TEXT DEFAULT NULL,
    `total_amount` DECIMAL(12, 0) NOT NULL DEFAULT 0,
    `payment_method` ENUM('cod', 'bank_transfer') NOT NULL DEFAULT 'cod',
    `status` ENUM('pending', 'processing', 'completed', 'cancelled') NOT NULL DEFAULT 'pending',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_orders_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------
-- 4. BẢNG ORDER_ITEMS (Chi tiết từng món trong đơn hàng)
-- -------------------------------------------------------
CREATE TABLE `order_items` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `order_id` INT NOT NULL,
    `product_id` INT DEFAULT NULL,
    `product_name` VARCHAR(200) NOT NULL,
    `price` DECIMAL(12, 0) NOT NULL DEFAULT 0,
    `quantity` INT NOT NULL DEFAULT 1,
    `subtotal` DECIMAL(12, 0) NOT NULL DEFAULT 0,
    CONSTRAINT `fk_items_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_items_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------
-- CÁC INDEX HỖ TRỢ TRUY VẤN NHANH
-- -------------------------------------------------------
CREATE INDEX `idx_products_category` ON `products` (`category`);
CREATE INDEX `idx_products_created` ON `products` (`created_at`);
CREATE INDEX `idx_orders_status` ON `orders` (`status`);
CREATE INDEX `idx_orders_user` ON `orders` (`user_id`);

-- -------------------------------------------------------
-- DỮ LIỆU MẪU (SEED DATA)
-- Mật khẩu tài khoản admin: admin123
-- Mật khẩu tài khoản khách hàng: 123456
-- -------------------------------------------------------
INSERT INTO `users` (`id`, `name`, `email`, `password`, `phone`, `address`, `role`) VALUES
(1, 'Quản Trị Viên (Admin)', 'admin@taphoa.vn', '$2y$10$Uk8tu8GioxwJWEhoY0xvwe452Ip.F.yk4i7iNyXTSSZQBVnEZW5S2', '0901234567', 'Tầng 5, Tòa nhà Bitexco, Q1, TP.HCM', 'admin'),
(2, 'Nguyễn Văn Khách', 'khachhang@taphoa.vn', '$2y$10$Ocv52KGLBpSpSqVNAyFomebam8Q9ho8Nx8majatjfG3PsGi0XqsLW', '0987654321', 'Số 123 Đường Cầu Giấy, Hà Nội', 'user');

INSERT INTO `products` (`id`, `user_id`, `name`, `slug`, `category`, `description`, `price`, `stock`, `image_url`) VALUES
(1, 1, 'Gạo Thơm ST25 Thượng Hạng Túi 5kg', 'gao-thom-st25-thuong-hang-tui-5kg', 'Lương thực - Thực phẩm', 'Gạo ST25 đạt giải gạo ngon nhất thế giới. Hạt gạo dài, trắng trong, cơm dẻo mềm, thơm hương lá dứa tự nhiên dù để nguội.', 185000, 50, 'https://images.unsplash.com/photo-1586201375761-83865001e31c?auto=format&fit=crop&w=600&q=80'),
(2, 1, 'Dầu Đậu Nành Nguyên Chất Simply 2L', 'dau-dau-nanh-nguyen-chat-simply-2l', 'Gia vị & Dầu ăn', 'Dầu nành Simply giàu Omega 3-6-9 tốt cho tim mạch, không cholesterol. Thích hợp cho các món chiên, xào, nấu hàng ngày.', 132000, 40, 'https://images.unsplash.com/photo-1474979266404-7eaacbcd87c5?auto=format&fit=crop&w=600&q=80'),
(3, 1, 'Nước Mắm Nam Ngư Đệ Nhị Chai 900ml', 'nuoc-mam-nam-ngu-de-nhi-chai-900ml', 'Gia vị & Dầu ăn', 'Vị ngon đậm đà, mùi thơm dịu nhẹ khó quên từ nguồn cá cơm tươi Phú Quốc, phù hợp khẩu vị của hàng triệu gia đình Việt.', 36000, 80, 'https://images.unsplash.com/photo-1628088062854-d1870b4553da?auto=format&fit=crop&w=600&q=80'),
(4, 1, 'Thùng 30 Gói Mì Hảo Hảo Tôm Chua Cay', 'thung-30-goi-mi-hao-hao-tom-chua-cay', 'Lương thực - Thực phẩm', 'Hương vị chua cay trứ danh quốc dân. Sợi mì dai giòn kết hợp nước súp tôm cay nồng sảng khoái.', 125000, 30, 'https://images.unsplash.com/photo-1612927601601-6638404737ce?auto=format&fit=crop&w=600&q=80'),
(5, 1, 'Cà Phê Hòa Tan G7 3in1 Trung Nguyên (Bịch 50 gói)', 'ca-phe-hoa-tan-g7-3in1-trung-nguyen', 'Nước giải khát & Cà phê', 'Chiết xuất từ những hạt cà phê Robusta Buôn Ma Thuột tinh túy nhất. Vị đậm đà, hương thơm nồng nàn đánh thức năng lượng.', 145000, 60, 'https://images.unsplash.com/photo-1514432324607-a09d9b4aefdd?auto=format&fit=crop&w=600&q=80'),
(6, 1, 'Lốc 4 Hộp Sữa Tươi Tiệt Trùng Vinamilk 180ml', 'loc-4-hop-sua-tuoi-tiet-trung-vinamilk-180ml', 'Sữa & Bánh kẹo', 'Sữa tươi 100% tự nhiên giàu Canxi và Vitamin D3 giúp tăng cường đề kháng và chắc khỏe xương khớp.', 38000, 100, 'https://images.unsplash.com/photo-1550583724-b2692b85b150?auto=format&fit=crop&w=600&q=80'),
(7, 1, 'Bánh ChocoPie Orion Hộp 12 Cái', 'banh-chocopie-orion-hop-12-cai', 'Sữa & Bánh kẹo', 'Bánh xốp phủ socola ngọt ngào hòa quyện cùng lớp kem marshmallow mềm dẻo, mang lại cảm xúc tình cảm trọn vẹn.', 58000, 45, 'https://images.unsplash.com/photo-1558961363-fa8fdf82db35?auto=format&fit=crop&w=600&q=80'),
(8, 1, 'Lốc 6 Chai Trà Ô Long Tea+ Plus 455ml', 'loc-6-chai-tra-o-long-tea-plus-455ml', 'Nước giải khát & Cà phê', 'Chứa hoạt chất OTPP tự nhiên giúp hạn chế hấp thu chất béo, vị trà thanh mát, thơm dịu sảng khoái.', 54000, 70, 'https://images.unsplash.com/photo-1556679343-c7306c1976bc?auto=format&fit=crop&w=600&q=80');

-- -------------------------------------------------------
-- ĐƠN HÀNG MẪU (1 Đơn hàng hoàn tất ban đầu)
-- -------------------------------------------------------
INSERT INTO `orders` (`id`, `user_id`, `customer_name`, `customer_phone`, `customer_address`, `customer_note`, `total_amount`, `payment_method`, `status`, `created_at`) VALUES
(1, 2, 'Nguyễn Văn Khách', '0987654321', 'Số 123 Đường Cầu Giấy, Hà Nội', 'Giao trong giờ hành chính giúp tôi', 320000, 'cod', 'completed', NOW() - INTERVAL 1 DAY);

INSERT INTO `order_items` (`order_id`, `product_id`, `product_name`, `price`, `quantity`, `subtotal`) VALUES
(1, 1, 'Gạo Thơm ST25 Thượng Hạng Túi 5kg', 185000, 1, 185000),
(1, 5, 'Cà Phê Hòa Tan G7 3in1 Trung Nguyên (Bịch 50 gói)', 145000, 1, 145000);
