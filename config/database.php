<?php
/**
 * Cấu hình kết nối Cơ sở dữ liệu MySQL (MonsterASP / InfinityFree)
 * Sử dụng PDO (PHP Data Objects) với Prepared Statements
 */

// Đọc file .env nếu có (chuẩn Native PHP không cần Composer)
if (!function_exists('load_env_file')) {
    function load_env_file($filePath) {
        if (!file_exists($filePath)) return;
        $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line) || strpos($line, '#') === 0) continue;
            if (strpos($line, '=') !== false) {
                list($name, $value) = explode('=', $line, 2);
                $name = trim($name);
                $value = trim(trim($value), '"\'');
                if (!array_key_exists($name, $_SERVER) && !array_key_exists($name, $_ENV)) {
                    putenv("{$name}={$value}");
                    $_ENV[$name] = $value;
                    $_SERVER[$name] = $value;
                }
            }
        }
    }
}

// Nạp các biến từ .env
load_env_file(__DIR__ . '/../.env');

// Lấy thông số bảo mật từ file .env (hoặc biến môi trường hệ thống)
// Tuyệt đối không hardcode mật khẩu hay tài khoản thật ở đây để an toàn khi push lên Git
if (!defined('DB_HOST')) define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
if (!defined('DB_PORT')) define('DB_PORT', getenv('DB_PORT') ?: '3306');
if (!defined('DB_NAME')) define('DB_NAME', getenv('DB_NAME') ?: '');
if (!defined('DB_USER')) define('DB_USER', getenv('DB_USER') ?: '');
if (!defined('DB_PASS')) define('DB_PASS', getenv('DB_PASS') ?: '');
if (!defined('DB_CHARSET')) define('DB_CHARSET', getenv('DB_CHARSET') ?: 'utf8mb4');

/**
 * Lấy đối tượng kết nối PDO (Singleton pattern)
 * @return PDO
 */
function get_db_connection() {
    static $pdo = null;

    if ($pdo === null) {
        $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::ATTR_TIMEOUT            => 7, // Giới hạn timeout 7s tránh treo trang khi mạng chập chờn
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
        ];

        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            // Hiển thị giao diện thông báo lỗi kết nối chuyên nghiệp & an toàn
            http_response_code(500);
            ?>
            <!DOCTYPE html>
            <html lang="vi">
            <head>
                <meta charset="UTF-8">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <title>Lỗi Kết Nối Cơ Sở Dữ Liệu - Tạp Hóa Store</title>
                <script src="https://cdn.tailwindcss.com"></script>
                <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
            </head>
            <body class="bg-slate-50 min-h-screen flex items-center justify-center p-4">
                <div class="max-w-lg w-full bg-white rounded-2xl shadow-xl border border-red-100 p-8 text-center">
                    <div class="w-16 h-16 bg-red-100 text-red-600 rounded-full flex items-center justify-center mx-auto mb-5 text-2xl">
                        <i class="fa-solid fa-database"></i>
                    </div>
                    <h1 class="text-2xl font-bold text-slate-800 mb-2">Chưa Thể Kết Nối Cơ Sở Dữ Liệu</h1>
                    <p class="text-slate-600 text-sm mb-6">
                        Hệ thống không thể kết nối tới máy chủ MySQL MonsterASP (<strong><?= htmlspecialchars(DB_HOST) ?></strong>).
                    </p>

                    <div class="text-left bg-amber-50 border border-amber-200 rounded-xl p-4 mb-6 text-xs text-amber-900 space-y-2">
                        <p class="font-semibold text-amber-950 flex items-center gap-1.5">
                            <i class="fa-solid fa-triangle-exclamation"></i> Các bước kiểm tra nhanh:
                        </p>
                        <ul class="list-disc list-inside space-y-1 pl-1">
                            <li>Bạn đã import file <code class="bg-amber-100 px-1 py-0.5 rounded font-mono">schema.sql</code> vào phpMyAdmin của MonsterASP chưa?</li>
                            <li>Tài khoản & mật khẩu database trong file <code class="bg-amber-100 px-1 py-0.5 rounded font-mono">config/database.php</code> đã chính xác chưa?</li>
                            <li>Nếu deploy lên InfinityFree: Hãy đảm bảo MonsterASP cho phép kết nối từ ngoài qua port 3306.</li>
                        </ul>
                    </div>

                    <div class="p-3 bg-slate-100 rounded-lg text-xs font-mono text-slate-500 break-all text-left mb-6">
                        <?php 
                            $safeMsg = $e->getMessage();
                            if (defined('DB_PASS') && DB_PASS !== '') {
                                $safeMsg = str_replace(DB_PASS, '******', $safeMsg);
                            }
                        ?>
                        Chi tiết lỗi: <?= htmlspecialchars($safeMsg) ?>
                    </div>

                    <a href="javascript:location.reload()" class="inline-flex items-center justify-center gap-2 w-full py-3 px-4 bg-emerald-600 hover:bg-emerald-700 text-white font-medium rounded-xl transition duration-200">
                        <i class="fa-solid fa-rotate-right"></i> Thử Tải Lại Trang
                    </a>
                </div>
            </body>
            </html>
            <?php
            exit;
        }
    }

    return $pdo;
}
