<?php
/**
 * Test Endpoint 2: Chứa các lỗ hổng SQL Injection nâng cao (CWE-89)
 * Dành cho kiểm thử SAST / DAST / sqlmap / Code Review & Đào tạo an toàn thông tin
 * 
 * Các kịch bản kiểm thử:
 *   1. Kịch bản Đăng nhập (Authentication Bypass SQLi):
 *      /test2.php?scenario=login&email=admin@taphoa.vn'&password=xxx
 *   2. Kịch bản Sắp xếp & Lọc (ORDER BY & Filter SQLi):
 *      /test2.php?scenario=sort&category=all&sort=price&order=DESC
 *   3. Giao diện trực quan:
 *      /test2.php?html=1
 */

require_once __DIR__ . '/includes/functions.php';

// Cấu hình CORS phục vụ kiểm thử API qua Fetch / Postman / công cụ quét
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Xác định kịch bản kiểm thử (mặc định: login)
$scenario = $_REQUEST['scenario'] ?? (isset($_REQUEST['sort']) || isset($_REQUEST['category']) ? 'sort' : 'login');

$startTime = microtime(true);
$response = [];
$httpCode = 200;
$sql = '';
$testedParams = [];

try {
    if ($scenario === 'sort') {
        // =========================================================================
        // KỊCH BẢN 2: LỖ HỔNG SQL INJECTION TRONG MỆNH ĐỀ "ORDER BY" & "WHERE"
        // =========================================================================
        // Lỗ hổng: PDO Prepared Statements không hỗ trợ tham số hóa tên cột hoặc chiều sắp xếp.
        // Lỗi thường gặp: Nối chuỗi trực tiếp giá trị người dùng nhập vào câu truy vấn.
        $category = $_REQUEST['category'] ?? 'all';
        $sortBy   = $_REQUEST['sort'] ?? 'price';
        $orderDir = $_REQUEST['order'] ?? 'ASC';

        $testedParams = [
            'category' => $category,
            'sort'     => $sortBy,
            'order'    => $orderDir
        ];

        $whereClause = "WHERE 1=1";
        if ($category !== 'all' && $category !== '') {
            // Nối chuỗi trực tiếp tham số lọc category (String SQLi)
            $whereClause .= " AND category = '" . $category . "'";
        }

        // Nối chuỗi trực tiếp tham số sắp xếp (ORDER BY SQLi)
        $sql = "SELECT id, name, category, price, stock, created_at FROM products {$whereClause} ORDER BY {$sortBy} {$orderDir}";

        $stmt = db()->query($sql);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $executionTime = round((microtime(true) - $startTime) * 1000, 2);

        $response = [
            'status'         => 'success',
            'endpoint'       => '/test2.php',
            'scenario'       => 'ORDER BY & Filter SQL Injection',
            'vulnerability'  => 'CWE-89: Improper Neutralization of Special Elements used in an SQL Command',
            'parameters'     => $testedParams,
            'query_executed' => $sql,
            'execution_time' => "{$executionTime} ms",
            'row_count'      => count($data),
            'data'           => $data
        ];
    } else {
        // =========================================================================
        // KỊCH BẢN 1: LỖ HỔNG XÁC THỰC (AUTHENTICATION BYPASS SQL INJECTION)
        // =========================================================================
        // Lỗ hổng kinh điển: Kiểm tra thông tin đăng nhập bằng phép nối chuỗi.
        // Kẻ tấn công có thể dùng "' OR '1'='1" hoặc comment "-- " để vượt qua mật khẩu.
        $scenario = 'login';
        $email    = $_REQUEST['email'] ?? 'admin@taphoa.vn';
        $password = $_REQUEST['password'] ?? 'admin123';

        $testedParams = [
            'email'    => $email,
            'password' => $password
        ];

        // Câu truy vấn nối chuỗi không an toàn
        $sql = "SELECT id, name, email, phone, address, role, created_at FROM users WHERE email = '" . $email . "' AND password = '" . $password . "'";

        $stmt = db()->query($sql);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        $executionTime = round((microtime(true) - $startTime) * 1000, 2);

        $isAuthenticated = !empty($user);

        $response = [
            'status'          => 'success',
            'endpoint'        => '/test2.php',
            'scenario'        => 'Authentication Bypass SQL Injection',
            'vulnerability'   => 'CWE-89: SQL Injection in Authentication Routine',
            'authenticated'   => $isAuthenticated,
            'login_message'   => $isAuthenticated ? "Đăng nhập THÀNH CÔNG với quyền [{$user['role']}]!" : "Đăng nhập THẤT BẠI: Sai email hoặc mật khẩu.",
            'parameters'      => $testedParams,
            'query_executed'  => $sql,
            'execution_time'  => "{$executionTime} ms",
            'user_logged_in'  => $user ?: null
        ];
    }
} catch (PDOException $e) {
    $httpCode = 500;
    $executionTime = round((microtime(true) - $startTime) * 1000, 2);
    $response = [
        'status'         => 'error',
        'endpoint'       => '/test2.php',
        'scenario'       => $scenario === 'sort' ? 'ORDER BY & Filter SQL Injection' : 'Authentication Bypass SQL Injection',
        'vulnerability'  => 'CWE-89: SQL Injection',
        'parameters'     => $testedParams,
        'query_executed' => $sql,
        'execution_time' => "{$executionTime} ms",
        'error_code'     => $e->getCode(),
        'error_message'  => $e->getMessage()
    ];
}

// Hiển thị giao diện trực quan nếu có tham số ?html=1 hoặc ?view=html
if (isset($_GET['html']) || (isset($_GET['view']) && $_GET['view'] === 'html')) {
    http_response_code($httpCode);
    $curScenario = $scenario;
    ?>
    <!DOCTYPE html>
    <html lang="vi">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>SQL Injection Test Console #2 - /test2.php</title>
        <script src="https://cdn.tailwindcss.com"></script>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    </head>
    <body class="bg-slate-950 text-slate-100 min-h-screen p-4 sm:p-8 font-sans antialiased selection:bg-rose-500 selection:text-white">
        <div class="max-w-5xl mx-auto space-y-6">
            
            <!-- Header Bar -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-slate-900 border border-slate-800 rounded-2xl p-5 shadow-xl">
                <div class="flex items-center gap-3">
                    <span class="w-3.5 h-3.5 rounded-full bg-rose-500 animate-ping"></span>
                    <div>
                        <h1 class="text-lg sm:text-xl font-bold text-white flex items-center gap-2">
                            SQL Injection Test Lab #2 
                            <span class="text-xs px-2 py-0.5 rounded-full bg-slate-800 text-slate-300 font-mono border border-slate-700">/test2.php</span>
                        </h1>
                        <p class="text-xs text-slate-400">Endpoint mô phỏng lỗi SQL Injection phục vụ SAST/DAST, Code Review & Benchmark bảo mật</p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <a href="test1.php?html=1" class="px-3 py-1.5 bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs font-semibold rounded-xl border border-slate-700 transition">
                        <i class="fa-solid fa-arrow-left mr-1"></i> Sang Test #1 (Numeric & LIKE)
                    </a>
                    <span class="px-3 py-1 bg-rose-500/10 text-rose-400 border border-rose-500/30 text-xs font-semibold rounded-xl">
                        CWE-89 VULNERABLE
                    </span>
                </div>
            </div>

            <!-- Tab Switcher -->
            <div class="grid grid-cols-2 gap-3 bg-slate-900/60 p-1.5 rounded-2xl border border-slate-800 text-xs font-bold">
                <a href="test2.php?html=1&scenario=login" 
                   class="flex items-center justify-center gap-2 py-3 rounded-xl transition <?= $curScenario === 'login' ? 'bg-rose-600 text-white shadow-lg shadow-rose-600/30' : 'text-slate-400 hover:text-white hover:bg-slate-800' ?>">
                    <i class="fa-solid fa-key"></i> Kịch bản 1: Authentication Bypass (Login SQLi)
                </a>
                <a href="test2.php?html=1&scenario=sort" 
                   class="flex items-center justify-center gap-2 py-3 rounded-xl transition <?= $curScenario === 'sort' ? 'bg-amber-600 text-white shadow-lg shadow-amber-600/30' : 'text-slate-400 hover:text-white hover:bg-slate-800' ?>">
                    <i class="fa-solid fa-arrow-down-wide-short"></i> Kịch bản 2: ORDER BY & Filter SQLi
                </a>
            </div>

            <!-- Main Test Panel -->
            <div class="bg-slate-900 border border-slate-800 rounded-3xl p-6 sm:p-8 shadow-2xl space-y-6">
                
                <?php if ($curScenario === 'login'): ?>
                    <!-- SCENARIO 1: AUTH BYPASS -->
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <h2 class="text-base font-bold text-white flex items-center gap-2">
                                <i class="fa-solid fa-shield-halved text-rose-400"></i>
                                Kiểm thử Vượt qua Đăng nhập (Authentication Bypass)
                            </h2>
                            <span class="text-xs text-rose-400 font-mono">WHERE email = '...' AND password = '...'</span>
                        </div>
                        <p class="text-xs text-slate-400 mb-5">
                            Khi ứng dụng nối chuỗi thông tin người dùng nhập vào câu lệnh SQL xác thực tài khoản, kẻ tấn công có thể chèn các mệnh đề logic luôn đúng (<code class="text-rose-300 bg-slate-950 px-1.5 py-0.5 rounded">' OR 1=1 -- </code>) hoặc đóng dấu nháy để đăng nhập tài khoản bất kỳ mà không cần biết mật khẩu.
                        </p>

                        <!-- Form Login -->
                        <form method="GET" action="test2.php" class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-6">
                            <input type="hidden" name="html" value="1">
                            <input type="hidden" name="scenario" value="login">

                            <div>
                                <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-2">
                                    Email / Tài khoản:
                                </label>
                                <input type="text" name="email" value="<?= e($testedParams['email']) ?>" 
                                       class="w-full bg-slate-950 border border-slate-700 rounded-xl px-4 py-2.5 text-sm text-emerald-400 font-mono focus:outline-none focus:border-rose-500">
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-2">
                                    Mật khẩu (Password):
                                </label>
                                <div class="flex gap-2">
                                    <input type="text" name="password" value="<?= e($testedParams['password']) ?>" 
                                           class="flex-1 bg-slate-950 border border-slate-700 rounded-xl px-4 py-2.5 text-sm text-emerald-400 font-mono focus:outline-none focus:border-rose-500">
                                    <button type="submit" class="px-5 py-2.5 bg-rose-600 hover:bg-rose-500 text-white text-xs font-bold rounded-xl transition shadow-lg shadow-rose-600/30 flex items-center gap-1.5">
                                        <i class="fa-solid fa-paper-plane"></i> Test Login
                                    </button>
                                </div>
                            </div>
                        </form>

                        <!-- Quick Payloads for Login -->
                        <div class="space-y-2 mb-6">
                            <p class="text-xs text-slate-400 font-semibold uppercase tracking-wider">Payloads kiểm thử nhanh:</p>
                            <div class="flex flex-wrap gap-2 text-xs font-mono">
                                <a href="test2.php?html=1&scenario=login&email=admin@taphoa.vn&password=admin123" 
                                   class="px-3 py-1.5 bg-slate-950 hover:bg-slate-800 border border-slate-700 rounded-lg text-slate-300">
                                    Đăng nhập hợp lệ (admin / admin123)
                                </a>
                                <a href="test2.php?html=1&scenario=login&email=admin@taphoa.vn' -- &password=anything" 
                                   class="px-3 py-1.5 bg-slate-950 hover:bg-slate-800 border border-rose-800/60 rounded-lg text-rose-300">
                                    admin@taphoa.vn' -- (Bypass không cần pass)
                                </a>
                                <a href="test2.php?html=1&scenario=login&email=' OR 1=1 -- &password=anything" 
                                   class="px-3 py-1.5 bg-slate-950 hover:bg-slate-800 border border-rose-800/60 rounded-lg text-rose-300">
                                    ' OR 1=1 -- (Tautology Bypass vào dòng đầu)
                                </a>
                                <a href="test2.php?html=1&scenario=login&email=' OR '1'='1&password=' OR '1'='1" 
                                   class="px-3 py-1.5 bg-slate-950 hover:bg-slate-800 border border-purple-800/60 rounded-lg text-purple-300">
                                    ' OR '1'='1 (Cặp dấu nháy cân bằng)
                                </a>
                            </div>
                        </div>

                        <!-- Auth Status Card -->
                        <div class="p-4 rounded-2xl border <?= ($response['authenticated'] ?? false) ? 'bg-emerald-950/40 border-emerald-700/60 text-emerald-300' : 'bg-slate-950 border-slate-800 text-slate-400' ?> flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <i class="fa-solid <?= ($response['authenticated'] ?? false) ? 'fa-circle-check text-emerald-400 text-xl' : 'fa-circle-xmark text-slate-500 text-xl' ?>"></i>
                                <div>
                                    <div class="text-xs font-bold uppercase tracking-wider">Trạng thái xác thực:</div>
                                    <div class="text-sm font-semibold <?= ($response['authenticated'] ?? false) ? 'text-white' : 'text-slate-300' ?>">
                                        <?= e($response['login_message'] ?? '') ?>
                                    </div>
                                </div>
                            </div>
                            <?php if (!empty($response['user_logged_in'])): ?>
                                <div class="text-right text-xs">
                                    <span class="px-2.5 py-1 rounded-full bg-emerald-500/20 text-emerald-300 font-bold uppercase border border-emerald-500/30">
                                        Role: <?= e($response['user_logged_in']['role']) ?>
                                    </span>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                <?php else: ?>
                    <!-- SCENARIO 2: ORDER BY & FILTER -->
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <h2 class="text-base font-bold text-white flex items-center gap-2">
                                <i class="fa-solid fa-arrow-down-wide-short text-amber-400"></i>
                                Kiểm thử Lỗ hổng Mệnh đề ORDER BY & Dynamic Filter
                            </h2>
                            <span class="text-xs text-amber-400 font-mono">ORDER BY $sort $order</span>
                        </div>
                        <p class="text-xs text-slate-400 mb-5">
                            Nhiều lập trình viên lầm tưởng dùng Prepared Statement là đủ, nhưng trong PDO, mệnh đề <code class="text-amber-300 bg-slate-950 px-1.5 py-0.5 rounded">ORDER BY ?</code> không nhận tham số dạng tên cột. Nếu không Whitelist tên cột mà nối chuỗi trực tiếp, kẻ tấn công có thể khai thác SQL Injection dạng Time-based hoặc Error-based.
                        </p>

                        <!-- Form Filter & Sort -->
                        <form method="GET" action="test2.php" class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
                            <input type="hidden" name="html" value="1">
                            <input type="hidden" name="scenario" value="sort">

                            <div>
                                <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-2">
                                    Danh mục (Category):
                                </label>
                                <input type="text" name="category" value="<?= e($testedParams['category']) ?>" 
                                       placeholder="all, Gia vị & Dầu ăn..."
                                       class="w-full bg-slate-950 border border-slate-700 rounded-xl px-4 py-2.5 text-sm text-emerald-400 font-mono focus:outline-none focus:border-amber-500">
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-2">
                                    Cột Sắp xếp (Sort By):
                                </label>
                                <input type="text" name="sort" value="<?= e($testedParams['sort']) ?>" 
                                       placeholder="price, name, stock..."
                                       class="w-full bg-slate-950 border border-slate-700 rounded-xl px-4 py-2.5 text-sm text-emerald-400 font-mono focus:outline-none focus:border-amber-500">
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-2">
                                    Chiều (Order Dir):
                                </label>
                                <div class="flex gap-2">
                                    <input type="text" name="order" value="<?= e($testedParams['order']) ?>" 
                                           placeholder="ASC hoặc DESC"
                                           class="flex-1 bg-slate-950 border border-slate-700 rounded-xl px-4 py-2.5 text-sm text-emerald-400 font-mono focus:outline-none focus:border-amber-500">
                                    <button type="submit" class="px-5 py-2.5 bg-amber-600 hover:bg-amber-500 text-white text-xs font-bold rounded-xl transition shadow-lg shadow-amber-600/30 flex items-center gap-1.5">
                                        <i class="fa-solid fa-play"></i> Lọc
                                    </button>
                                </div>
                            </div>
                        </form>

                        <!-- Quick Payloads for Sort -->
                        <div class="space-y-2 mb-6">
                            <p class="text-xs text-slate-400 font-semibold uppercase tracking-wider">Payloads kiểm thử nhanh:</p>
                            <div class="flex flex-wrap gap-2 text-xs font-mono">
                                <a href="test2.php?html=1&scenario=sort&category=all&sort=price&order=ASC" 
                                   class="px-3 py-1.5 bg-slate-950 hover:bg-slate-800 border border-slate-700 rounded-lg text-slate-300">
                                    Bình thường: sort=price ASC
                                </a>
                                <a href="test2.php?html=1&scenario=sort&category=all&sort=(CASE WHEN (1=1) THEN price ELSE stock END)&order=ASC" 
                                   class="px-3 py-1.5 bg-slate-950 hover:bg-slate-800 border border-amber-800/60 rounded-lg text-amber-300">
                                    Conditional Sort: CASE WHEN (1=1)
                                </a>
                                <a href="test2.php?html=1&scenario=sort&category=all&sort=price, (SELECT SLEEP(2))&order=ASC" 
                                   class="px-3 py-1.5 bg-slate-950 hover:bg-slate-800 border border-blue-800/60 rounded-lg text-blue-300">
                                    Time-based: SLEEP(2) trong ORDER BY
                                </a>
                                <a href="test2.php?html=1&scenario=sort&category=' OR 1=1 -- &sort=price&order=ASC" 
                                   class="px-3 py-1.5 bg-slate-950 hover:bg-slate-800 border border-rose-800/60 rounded-lg text-rose-300">
                                    Filter Bypass: category=' OR 1=1 --
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Query Executed Display -->
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <p class="text-xs text-slate-400 font-semibold uppercase tracking-wider flex items-center gap-1.5">
                            <i class="fa-solid fa-terminal text-amber-400"></i> Câu SQL thực thi trên MySQL:
                        </p>
                        <span class="text-xs font-mono text-slate-400">Thời gian thực thi: <span class="text-emerald-400 font-bold"><?= e($response['execution_time'] ?? '0 ms') ?></span></span>
                    </div>
                    <pre class="bg-slate-950 p-4 rounded-2xl text-xs font-mono text-amber-400 border border-slate-800 overflow-x-auto shadow-inner"><?= e($sql) ?></pre>
                </div>

                <!-- JSON Response Preview -->
                <div>
                    <div class="flex justify-between items-center mb-2">
                        <p class="text-xs text-slate-400 font-semibold uppercase tracking-wider flex items-center gap-1.5">
                            <i class="fa-solid fa-code text-cyan-400"></i> Dữ liệu JSON trả về (Raw API):
                        </p>
                        <a href="test2.php?<?= http_build_query(array_merge(['scenario' => $scenario], $testedParams)) ?>" 
                           target="_blank"
                           class="text-xs text-emerald-400 hover:text-emerald-300 flex items-center gap-1">
                            Mở Raw JSON API <i class="fa-solid fa-arrow-up-right-from-square text-[10px]"></i>
                        </a>
                    </div>
                    <pre class="bg-slate-950 p-4 rounded-2xl text-xs font-mono text-slate-300 border border-slate-800 max-h-72 overflow-y-auto shadow-inner"><?= e(json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) ?></pre>
                </div>

                <!-- Secure Coding Remediation Section -->
                <div class="mt-8 pt-6 border-t border-slate-800/80">
                    <h3 class="text-sm font-bold text-emerald-400 flex items-center gap-2 mb-3">
                        <i class="fa-solid fa-shield-virus"></i>
                        Cách khắc phục an toàn theo tiêu chuẩn OWASP & CWE-89:
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs font-mono">
                        <div class="bg-rose-950/20 border border-rose-900/40 p-4 rounded-2xl space-y-2">
                            <div class="text-rose-400 font-bold uppercase tracking-wider flex items-center gap-1.5">
                                <i class="fa-solid fa-triangle-exclamation"></i> Không an toàn (Vulnerable):
                            </div>
                            <pre class="text-slate-300 overflow-x-auto leading-relaxed">// 1. Nối chuỗi trực tiếp tham số đăng nhập
$sql = "SELECT * FROM users WHERE email = '$email' AND password = '$password'";
$stmt = db()->query($sql);

// 2. Nối chuỗi cột ORDER BY
$sql = "... ORDER BY $sortBy $orderDir";</pre>
                        </div>

                        <div class="bg-emerald-950/20 border border-emerald-900/40 p-4 rounded-2xl space-y-2">
                            <div class="text-emerald-400 font-bold uppercase tracking-wider flex items-center gap-1.5">
                                <i class="fa-solid fa-circle-check"></i> An toàn (Remediated):
                            </div>
                            <pre class="text-slate-300 overflow-x-auto leading-relaxed">// 1. Dùng Prepared Statement & password_verify()
$stmt = db()->prepare("SELECT * FROM users WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch();
if ($user && password_verify($password, $user['password'])) { ... }

// 2. Dùng Whitelist cho tên cột ORDER BY
$allowed = ['price', 'name', 'stock'];
$sort = in_array($sortBy, $allowed) ? $sortBy : 'price';</pre>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Footer -->
            <div class="text-center text-xs text-slate-500 py-2">
                TapHoaStore Security Testing Lab • Endpoint phục vụ nghiên cứu & kiểm thử an toàn thông tin
            </div>

        </div>
    </body>
    </html>
    <?php
    exit;
}

// Mặc định trả về định dạng JSON thuần
http_response_code($httpCode);
header('Content-Type: application/json; charset=utf-8');
echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
