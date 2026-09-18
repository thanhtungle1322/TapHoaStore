<?php
/**
 * Các hàm tiện ích dùng chung (Helper Functions)
 * Hỗ trợ xác thực, giỏ hàng, định dạng dữ liệu, flash messages và bảo mật XSS
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';

/**
 * Lấy đối tượng kết nối CSDL
 * @return PDO
 */
function db() {
    return get_db_connection();
}

/**
 * Khử mã độc XSS an toàn khi xuất dữ liệu ra HTML
 * @param string|null $data
 * @return string
 */
function e($data) {
    return htmlspecialchars((string)$data, ENT_QUOTES, 'UTF-8');
}

/**
 * Định dạng số tiền sang chuẩn Việt Nam Đồng (VNĐ)
 * @param float|int $amount
 * @return string
 */
function format_money($amount) {
    return number_format((float)$amount, 0, ',', '.') . ' ₫';
}

/**
 * Lấy đường dẫn gốc của ứng dụng (Tự động thích ứng InfinityFree hoặc Local)
 * @param string $path
 * @return string
 */
function base_url($path = '') {
    // Xác định thư mục cha của dự án nếu nằm trong subfolder
    $scriptDir = dirname($_SERVER['SCRIPT_NAME']);
    // Chuẩn hóa dấu gạch chéo
    $scriptDir = str_replace('\\', '/', $scriptDir);
    
    // Nếu file đang chạy trong subfolder (như /auth, /products, /orders), lấy root
    $subfolders = ['/auth', '/products', '/orders'];
    foreach ($subfolders as $sub) {
        if (substr($scriptDir, -strlen($sub)) === $sub) {
            $scriptDir = substr($scriptDir, 0, -strlen($sub));
            break;
        }
    }
    
    $base = rtrim($scriptDir, '/');
    $path = ltrim($path, '/');
    
    return ($base ? $base : '') . ($path ? '/' . $path : '/');
}

/**
 * Thiết lập Flash Message thông báo (Toast message)
 * @param string $type success|error|warning|info
 * @param string $message
 */
function set_flash($type, $message) {
    if (!isset($_SESSION['flash'])) {
        $_SESSION['flash'] = [];
    }
    $_SESSION['flash'][] = [
        'type'    => $type,
        'message' => $message
    ];
}

/**
 * Lấy toàn bộ Flash Message và xóa khỏi session
 * @return array
 */
function get_flash() {
    if (!isset($_SESSION['flash']) || empty($_SESSION['flash'])) {
        return [];
    }
    $messages = $_SESSION['flash'];
    unset($_SESSION['flash']);
    return $messages;
}

/**
 * Chuyển hướng trang an toàn
 * @param string $url
 */
function redirect($url) {
    header("Location: " . $url);
    exit;
}

/**
 * Kiểm tra trạng thái đã đăng nhập
 * @return bool
 */
function is_logged_in() {
    return isset($_SESSION['user']) && !empty($_SESSION['user']['id']);
}

/**
 * Lấy thông tin người dùng hiện tại
 * @return array|null
 */
function current_user() {
    return is_logged_in() ? $_SESSION['user'] : null;
}

/**
 * Kiểm tra xem người dùng có quyền Quản trị viên (Admin/Seller) hay không
 * @return bool
 */
function is_admin() {
    return is_logged_in() && (($_SESSION['user']['role'] ?? '') === 'admin');
}

/**
 * Yêu cầu đăng nhập trước khi truy cập trang
 */
function require_login() {
    if (!is_logged_in()) {
        set_flash('warning', 'Vui lòng đăng nhập để tiếp tục thao tác!');
        redirect(base_url('auth/login.php'));
    }
}

/**
 * Yêu cầu quyền Admin trước khi truy cập
 */
function require_admin() {
    require_login();
    if (!is_admin()) {
        set_flash('error', 'Bạn không có quyền truy cập vào trang này!');
        redirect(base_url('index.php'));
    }
}

/**
 * Đếm tổng số lượng sản phẩm trong giỏ hàng
 * @return int
 */
function cart_count() {
    if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
        return 0;
    }
    $count = 0;
    foreach ($_SESSION['cart'] as $item) {
        $count += (int)($item['quantity'] ?? 0);
    }
    return $count;
}

/**
 * Tính tổng số tiền trong giỏ hàng
 * @return float
 */
function cart_total() {
    if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
        return 0;
    }
    $total = 0;
    foreach ($_SESSION['cart'] as $item) {
        $total += ((float)($item['price'] ?? 0)) * ((int)($item['quantity'] ?? 1));
    }
    return $total;
}

/**
 * Tạo slug thân thiện từ chuỗi tiếng Việt
 * @param string $string
 * @return string
 */
function slugify($string) {
    $search = ['á','à','ả','ã','ạ','ă','ắ','ằ','ẳ','ẵ','ặ','â','ấ','ầ','ẩ','ẫ','ậ',
               'é','è','ẻ','ẽ','ẹ','ê','ế','ề','ể','ễ','ệ',
               'í','ì','ỉ','ĩ','ị',
               'ó','ò','ỏ','õ','ọ','ô','ố','ồ','ổ','ỗ','ộ','ơ','ớ','ờ','ở','ỡ','ợ',
               'ú','ù','ủ','ũ','ụ','ư','ứ','ừ','ử','ữ','ự',
               'ý','ỳ','ỷ','ỹ','ỵ',
               'đ',
               'Á','À','Ả','Ã','Ạ','Ă','Ắ','Ằ','Ẳ','Ẵ','Ặ','Â','Ấ','Ầ','Ẩ','Ẫ','Ậ',
               'É','È','Ẻ','Ẽ','Ẹ','Ê','Ế','Ề','Ể','Ễ','Ệ',
               'Í','Ì','Ỉ','Ĩ','Ị',
               'Ó','Ò','Ỏ','Õ','Ọ','Ô','Ố','Ồ','Ổ','Ỗ','Ộ','Ơ','Ớ','Ờ','Ở','Ỡ','Ợ',
               'Ú','Ù','Ủ','Ũ','Ụ','Ư','Ứ','Ừ','Ử','Ữ','Ự',
               'Ý','Ỳ','Ỷ','Ỹ','Ỵ',
               'Đ'];
    $replace = ['a','a','a','a','a','a','a','a','a','a','a','a','a','a','a','a','a',
                'e','e','e','e','e','e','e','e','e','e','e',
                'i','i','i','i','i',
                'o','o','o','o','o','o','o','o','o','o','o','o','o','o','o','o','o',
                'u','u','u','u','u','u','u','u','u','u','u',
                'y','y','y','y','y',
                'd',
                'a','a','a','a','a','a','a','a','a','a','a','a','a','a','a','a','a',
                'e','e','e','e','e','e','e','e','e','e','e',
                'i','i','i','i','i',
                'o','o','o','o','o','o','o','o','o','o','o','o','o','o','o','o','o',
                'u','u','u','u','u','u','u','u','u','u','u',
                'y','y','y','y','y',
                'd'];
    $string = str_replace($search, $replace, $string);
    $string = strtolower(trim($string));
    $string = preg_replace('/[^a-z0-9-]/', '-', $string);
    $string = preg_replace('/-+/', '-', $string);
    return trim($string, '-');
}

/**
 * Xử lý tải ảnh sản phẩm lên thư mục uploads/
 * @param array $file Mảng $_FILES['image']
 * @return string|false Đường dẫn tương đối lưu vào DB, hoặc false nếu lỗi
 */
function upload_product_image($file) {
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
        return false;
    }

    // Giới hạn dung lượng: 3MB
    $maxSize = 3 * 1024 * 1024;
    if ($file['size'] > $maxSize) {
        set_flash('error', 'Kích thước file ảnh quá lớn (tối đa 3MB).');
        return false;
    }

    // Kiểm tra định dạng file
    $allowedMimes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
    $fileInfo = @getimagesize($file['tmp_name']);
    if (!$fileInfo || !in_array($fileInfo['mime'], $allowedMimes)) {
        set_flash('error', 'Chỉ chấp nhận file ảnh hợp lệ (JPG, PNG, WEBP, GIF).');
        return false;
    }

    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $ext = strtolower($ext);
    if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'])) {
        $ext = 'jpg';
    }

    // Đặt tên ngẫu nhiên chống trùng lặp và an toàn
    $newFileName = 'prod_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    
    $uploadDir = __DIR__ . '/../uploads/';
    if (!is_dir($uploadDir)) {
        @mkdir($uploadDir, 0755, true);
    }

    $destPath = $uploadDir . $newFileName;
    if (move_uploaded_file($file['tmp_name'], $destPath)) {
        return 'uploads/' . $newFileName;
    }

    set_flash('error', 'Không thể lưu file ảnh vào thư mục uploads. Vui lòng kiểm tra quyền thư mục.');
    return false;
}
