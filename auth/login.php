<?php
/**
 * Trang Đăng nhập Tài khoản
 */
require_once __DIR__ . '/../includes/functions.php';

if (is_logged_in()) {
    redirect(base_url('index.php'));
}

$errors = [];
$email = '';
$redirectUrl = $_GET['redirect'] ?? base_url('index.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $redirectUrl = $_POST['redirect_to'] ?? base_url('index.php');

    if (empty($email) || empty($password)) {
        $errors[] = 'Vui lòng nhập đầy đủ Email và Mật khẩu.';
    } else {
        try {
            $stmt = db()->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                // Đăng nhập thành công
                $_SESSION['user'] = [
                    'id'      => $user['id'],
                    'name'    => $user['name'],
                    'email'   => $user['email'],
                    'phone'   => $user['phone'],
                    'address' => $user['address'],
                    'role'    => $user['role']
                ];

                set_flash('success', 'Đăng nhập thành công! Chào mừng ' . $user['name'] . ' trở lại.');
                
                // Tránh open redirect vulnerability: chỉ cho phép đường dẫn nội bộ
                if (strpos($redirectUrl, 'http') === 0 && strpos($redirectUrl, $_SERVER['HTTP_HOST']) === false) {
                    $redirectUrl = base_url('index.php');
                }
                redirect($redirectUrl);
            } else {
                $errors[] = 'Email hoặc Mật khẩu không chính xác. Vui lòng kiểm tra lại.';
            }
        } catch (PDOException $e) {
            $errors[] = 'Lỗi kết nối cơ sở dữ liệu: ' . $e->getMessage();
        }
    }
}

$pageTitle = 'Đăng Nhập';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="py-12 px-4 sm:px-6 lg:px-8 max-w-md mx-auto">
    <div class="bg-white rounded-3xl shadow-xl border border-slate-100 p-8">
        
        <div class="text-center mb-8">
            <div class="w-12 h-12 bg-emerald-100 text-emerald-600 rounded-2xl flex items-center justify-center mx-auto mb-3 text-xl shadow-inner">
                <i class="fa-solid fa-right-to-bracket"></i>
            </div>
            <h1 class="text-2xl font-bold text-slate-800">Đăng Nhập Tài Khoản</h1>
            <p class="text-xs text-slate-500 mt-1">Đăng nhập để quản lý đơn mua và giỏ hàng của bạn</p>
        </div>

        <?php if (!empty($errors)): ?>
            <div class="mb-6 p-4 rounded-xl bg-rose-50 border border-rose-200 text-xs text-rose-700 space-y-1">
                <p class="font-bold flex items-center gap-1.5"><i class="fa-solid fa-circle-exclamation"></i> Không thể đăng nhập:</p>
                <ul class="list-disc list-inside space-y-0.5">
                    <?php foreach ($errors as $err): ?>
                        <li><?= e($err) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form action="<?= base_url('auth/login.php') ?>" method="POST" class="space-y-4">
            <input type="hidden" name="redirect_to" value="<?= e($redirectUrl) ?>">

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Email đăng nhập</label>
                <input type="email" name="email" value="<?= e($email) ?>" required 
                       class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:bg-white focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 focus:outline-none transition"
                       placeholder="email@example.com">
            </div>

            <div>
                <div class="flex items-center justify-between mb-1">
                    <label class="block text-xs font-semibold text-slate-700">Mật khẩu</label>
                </div>
                <input type="password" name="password" required 
                       class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:bg-white focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 focus:outline-none transition"
                       placeholder="Nhập mật khẩu">
            </div>

            <div class="pt-2">
                <button type="submit" class="w-full py-3 px-4 bg-emerald-600 hover:bg-emerald-700 text-white font-semibold rounded-xl shadow-md shadow-emerald-600/20 transition duration-150 flex items-center justify-center gap-2">
                    <i class="fa-solid fa-arrow-right-to-bracket"></i> Đăng Nhập
                </button>
            </div>
        </form>

        <!-- Khối tài khoản thử nghiệm nhanh (Demo Accounts) -->
        <div class="mt-6 p-3.5 bg-slate-50 border border-dashed border-slate-200 rounded-2xl text-[11px] text-slate-600 space-y-1.5">
            <div class="font-bold text-slate-700 flex items-center gap-1">
                <i class="fa-solid fa-key text-amber-500"></i> Tài khoản mẫu dùng thử:
            </div>
            <div class="grid grid-cols-1 gap-1 text-slate-500">
                <div>• <strong>Admin / Người bán:</strong> <code class="text-emerald-700 font-mono">admin@taphoa.vn</code> / Mật khẩu: <code class="text-emerald-700 font-mono">admin123</code></div>
                <div>• <strong>Khách hàng:</strong> <code class="text-emerald-700 font-mono">khachhang@taphoa.vn</code> / Mật khẩu: <code class="text-emerald-700 font-mono">123456</code></div>
            </div>
        </div>

        <div class="mt-6 text-center text-xs text-slate-500">
            Bạn chưa có tài khoản? 
            <a href="<?= base_url('auth/register.php') ?>" class="text-emerald-600 hover:text-emerald-700 font-semibold underline underline-offset-2">Đăng ký thành viên mới</a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
