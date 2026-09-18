<?php
/**
 * Trang Đăng ký Tài khoản Khách hàng
 */
require_once __DIR__ . '/../includes/functions.php';

if (is_logged_in()) {
    redirect(base_url('index.php'));
}

$errors = [];
$name = '';
$email = '';
$phone = '';
$address = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');

    // Validate dữ liệu
    if (empty($name)) {
        $errors[] = 'Vui lòng nhập họ và tên của bạn.';
    }
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Địa chỉ email không hợp lệ.';
    }
    if (strlen($password) < 6) {
        $errors[] = 'Mật khẩu phải có độ dài ít nhất 6 ký tự.';
    }
    if ($password !== $confirmPassword) {
        $errors[] = 'Xác nhận mật khẩu không khớp.';
    }

    // Kiểm tra trùng email trong CSDL
    if (empty($errors)) {
        try {
            $stmt = db()->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $errors[] = 'Địa chỉ email này đã được sử dụng. Vui lòng chọn email khác hoặc đăng nhập.';
            }
        } catch (PDOException $e) {
            $errors[] = 'Lỗi truy vấn cơ sở dữ liệu: ' . $e->getMessage();
        }
    }

    // Tiến hành đăng ký
    if (empty($errors)) {
        try {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $stmt = db()->prepare("
                INSERT INTO users (name, email, password, phone, address, role)
                VALUES (?, ?, ?, ?, ?, 'user')
            ");
            $stmt->execute([$name, $email, $hashedPassword, $phone, $address]);
            
            $userId = db()->lastInsertId();

            // Tự động đăng nhập sau khi đăng ký thành công
            $_SESSION['user'] = [
                'id'      => $userId,
                'name'    => $name,
                'email'   => $email,
                'phone'   => $phone,
                'address' => $address,
                'role'    => 'user'
            ];

            set_flash('success', 'Đăng ký tài khoản thành công! Chào mừng bạn đến với Tạp Hóa Store.');
            redirect(base_url('index.php'));
        } catch (PDOException $e) {
            $errors[] = 'Có lỗi xảy ra trong quá trình tạo tài khoản: ' . $e->getMessage();
        }
    }
}

$pageTitle = 'Đăng Ký Tài Khoản Mới';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="py-12 px-4 sm:px-6 lg:px-8 max-w-md mx-auto">
    <div class="bg-white rounded-3xl shadow-xl border border-slate-100 p-8">
        
        <div class="text-center mb-8">
            <div class="w-12 h-12 bg-emerald-100 text-emerald-600 rounded-2xl flex items-center justify-center mx-auto mb-3 text-xl shadow-inner">
                <i class="fa-solid fa-user-plus"></i>
            </div>
            <h1 class="text-2xl font-bold text-slate-800">Tạo Tài Khoản Mới</h1>
            <p class="text-xs text-slate-500 mt-1">Đăng ký thành viên để mua sắm và theo dõi đơn hàng tiện lợi</p>
        </div>

        <?php if (!empty($errors)): ?>
            <div class="mb-6 p-4 rounded-xl bg-rose-50 border border-rose-200 text-xs text-rose-700 space-y-1">
                <p class="font-bold flex items-center gap-1.5"><i class="fa-solid fa-triangle-exclamation"></i> Có lỗi xảy ra:</p>
                <ul class="list-disc list-inside space-y-0.5">
                    <?php foreach ($errors as $err): ?>
                        <li><?= e($err) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form action="<?= base_url('auth/register.php') ?>" method="POST" class="space-y-4">
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Họ và tên <span class="text-rose-500">*</span></label>
                <input type="text" name="name" value="<?= e($name) ?>" required 
                       class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:bg-white focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 focus:outline-none transition"
                       placeholder="Nguyễn Văn A">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Email <span class="text-rose-500">*</span></label>
                <input type="email" name="email" value="<?= e($email) ?>" required 
                       class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:bg-white focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 focus:outline-none transition"
                       placeholder="email@example.com">
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Mật khẩu <span class="text-rose-500">*</span></label>
                    <input type="password" name="password" required 
                           class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:bg-white focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 focus:outline-none transition"
                           placeholder="Ít nhất 6 ký tự">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Nhập lại mật khẩu <span class="text-rose-500">*</span></label>
                    <input type="password" name="confirm_password" required 
                           class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:bg-white focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 focus:outline-none transition"
                           placeholder="Xác nhận mật khẩu">
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Số điện thoại</label>
                <input type="tel" name="phone" value="<?= e($phone) ?>" 
                       class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:bg-white focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 focus:outline-none transition"
                       placeholder="Ví dụ: 0901234567">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Địa chỉ giao hàng</label>
                <input type="text" name="address" value="<?= e($address) ?>" 
                       class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:bg-white focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 focus:outline-none transition"
                       placeholder="Số nhà, tên đường, phường/xã, quận/huyện...">
            </div>

            <div class="pt-2">
                <button type="submit" class="w-full py-3 px-4 bg-emerald-600 hover:bg-emerald-700 text-white font-semibold rounded-xl shadow-md shadow-emerald-600/20 transition duration-150 flex items-center justify-center gap-2">
                    <i class="fa-solid fa-user-plus"></i> Đăng Ký Tài Khoản
                </button>
            </div>
        </form>

        <div class="mt-6 text-center text-xs text-slate-500">
            Bạn đã có tài khoản rồi? 
            <a href="<?= base_url('auth/login.php') ?>" class="text-emerald-600 hover:text-emerald-700 font-semibold underline underline-offset-2">Đăng nhập ngay</a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
