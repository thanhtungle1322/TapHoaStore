<?php
/**
 * Test Endpoint chứa lỗ hổng SQL Injection (CWE-89)
 * Dành cho kiểm thử SAST / DAST / sqlmap / Code Review
 * 
 * Các đường dẫn hỗ trợ:
 *   - /test?id=1
 *   - /test.php?id=1
 *   - /test?q=admin
 *   - /test?html=1 (xem giao diện trực quan)
 */
require_once __DIR__ . '/includes/functions.php';

// Cho phép CORS khi kiểm thử qua API/Fetch
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Nhận tham số từ GET hoặc POST
$paramName = 'id';
$paramValue = '1';

if (isset($_REQUEST['q'])) {
    $paramName = 'q';
    $paramValue = $_REQUEST['q'];
    // LỖ HỔNG SQL INJECTION DẠNG CHUỖI (String-based SQLi):
    $sql = "SELECT id, name, email, phone, address, role, created_at FROM users WHERE name LIKE '%" . $paramValue . "%'";
} else {
    $paramName = 'id';
    $paramValue = $_REQUEST['id'] ?? '1';
    // LỖ HỔNG SQL INJECTION DẠNG SỐ (Numeric-based SQLi):
    // Nối chuỗi trực tiếp tham số người dùng vào câu truy vấn
    $sql = "SELECT id, name, email, phone, address, role, created_at FROM users WHERE id = " . $paramValue;
}

$response = [];
$httpCode = 200;
$startTime = microtime(true);

try {
    // Thực thi trực tiếp câu lệnh SQL không dùng Prepared Statements
    $stmt = db()->query($sql);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $executionTime = round((microtime(true) - $startTime) * 1000, 2);

    $response = [
        'status'         => 'success',
        'endpoint'       => '/test',
        'vulnerability'  => 'SQL Injection (CWE-89)',
        'parameter'      => $paramName,
        'input_value'    => $paramValue,
        'query_executed' => $sql,
        'execution_time' => "{$executionTime} ms",
        'row_count'      => count($data),
        'data'           => $data
    ];
} catch (PDOException $e) {
    $httpCode = 500;
    $executionTime = round((microtime(true) - $startTime) * 1000, 2);
    $response = [
        'status'         => 'error',
        'endpoint'       => '/test',
        'vulnerability'  => 'SQL Injection (CWE-89)',
        'parameter'      => $paramName,
        'input_value'    => $paramValue,
        'query_executed' => $sql,
        'execution_time' => "{$executionTime} ms",
        'error_message'  => $e->getMessage()
    ];
}

// Nếu có tham số ?html=1 hoặc ?view=html, hiển thị giao diện trực quan
if (isset($_GET['html']) || (isset($_GET['view']) && $_GET['view'] === 'html')) {
    http_response_code($httpCode);
    ?>
    <!DOCTYPE html>
    <html lang="vi">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>SQL Injection Test Console - /test</title>
        <script src="https://cdn.tailwindcss.com"></script>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    </head>
    <body class="bg-slate-900 text-slate-100 min-h-screen p-4 sm:p-8 font-sans">
        <div class="max-w-4xl mx-auto">
            <div class="bg-slate-800 border border-slate-700 rounded-3xl p-6 sm:p-8 shadow-2xl mb-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center gap-3">
                        <span class="w-3 h-3 rounded-full bg-rose-500 animate-pulse"></span>
                        <h1 class="text-xl font-bold text-white tracking-wide">SQL Injection Vulnerable Endpoint (/test)</h1>
                    </div>
                    <span class="px-3 py-1 bg-rose-500/20 text-rose-400 border border-rose-500/30 text-xs font-semibold rounded-full">
                        CWE-89 VULNERABLE
                    </span>
                </div>

                <p class="text-slate-400 text-xs mb-6">
                    Endpoint này phục vụ kiểm thử bảo mật (SAST / DAST / sqlmap). Dữ liệu nhập vào được nối chuỗi trực tiếp vào hàm <code class="text-amber-400 bg-slate-950 px-2 py-0.5 rounded">db()->query()</code>.
                </p>

                <!-- Form test -->
                <form method="GET" action="test.php" class="space-y-4 mb-6">
                    <input type="hidden" name="html" value="1">
                    <div>
                        <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-2">Tham số ID (Numeric Injection):</label>
                        <div class="flex gap-2">
                            <input type="text" name="id" value="<?= e($paramValue) ?>" class="flex-1 bg-slate-950 border border-slate-700 rounded-xl px-4 py-2.5 text-sm text-emerald-400 font-mono focus:outline-none focus:border-emerald-500">
                            <button type="submit" class="px-5 py-2.5 bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold rounded-xl transition">
                                Gửi Request
                            </button>
                        </div>
                    </div>
                </form>

                <!-- Payload gợi ý -->
                <div class="space-y-2 mb-6">
                    <p class="text-xs text-slate-400 font-semibold uppercase tracking-wider">Payloads kiểm thử nhanh:</p>
                    <div class="flex flex-wrap gap-2 text-xs font-mono">
                        <a href="test.php?html=1&id=1" class="px-3 py-1.5 bg-slate-950 hover:bg-slate-700 border border-slate-700 rounded-lg text-slate-300">
                            id=1 (Bình thường)
                        </a>
                        <a href="test.php?html=1&id=1 OR 1=1" class="px-3 py-1.5 bg-slate-950 hover:bg-slate-700 border border-rose-800/50 rounded-lg text-rose-300">
                            id=1 OR 1=1 (Tautology Bypass)
                        </a>
                        <a href="test.php?html=1&id=1'" class="px-3 py-1.5 bg-slate-950 hover:bg-slate-700 border border-amber-800/50 rounded-lg text-amber-300">
                            id=1' (Error-based)
                        </a>
                        <a href="test.php?html=1&id=-1 UNION SELECT 99,'Hacker','hack@taphoa.vn','0999999999','Hà Nội','admin',NOW()" class="px-3 py-1.5 bg-slate-950 hover:bg-slate-700 border border-purple-800/50 rounded-lg text-purple-300">
                            UNION SELECT (Inject row)
                        </a>
                        <a href="test.php?html=1&id=1 AND (SELECT SLEEP(2))=0" class="px-3 py-1.5 bg-slate-950 hover:bg-slate-700 border border-blue-800/50 rounded-lg text-blue-300">
                            SLEEP(2) (Time-based)
                        </a>
                    </div>
                </div>

                <!-- Query Executed -->
                <div class="mb-4">
                    <p class="text-xs text-slate-400 font-semibold uppercase tracking-wider mb-1.5">Câu SQL thực thi trên Database:</p>
                    <pre class="bg-slate-950 p-4 rounded-xl text-xs font-mono text-amber-400 border border-slate-700 overflow-x-auto"><?= e($sql) ?></pre>
                </div>

                <!-- JSON Response Preview -->
                <div>
                    <div class="flex justify-between items-center mb-1.5">
                        <p class="text-xs text-slate-400 font-semibold uppercase tracking-wider">Kết quả JSON trả về:</p>
                        <a href="test.php?id=<?= urlencode($paramValue) ?>" class="text-xs text-emerald-400 hover:underline">
                            Xem Raw JSON &rarr;
                        </a>
                    </div>
                    <pre class="bg-slate-950 p-4 rounded-xl text-xs font-mono text-slate-200 border border-slate-700 max-h-96 overflow-y-auto"><?= e(json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) ?></pre>
                </div>
            </div>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// Mặc định trả về JSON Header & Data
http_response_code($httpCode);
header('Content-Type: application/json; charset=utf-8');
echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
