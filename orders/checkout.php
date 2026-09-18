<?php
/**
 * Trang Đặt Hàng & Thanh Toán (Checkout)
 * Xử lý Transaction lưu Orders, Order Items và cập nhật tồn kho Sản phẩm
 */
require_once __DIR__ . '/../includes/functions.php';

$cartItems = $_SESSION['cart'] ?? [];

if (empty($cartItems)) {
    set_flash('warning', 'Giỏ hàng của bạn đang trống. Vui lòng chọn sản phẩm trước khi thanh toán.');
    redirect(base_url('index.php'));
}

$currentUser = current_user();

// Giá trị ban đầu cho form (lấy từ tài khoản nếu đã đăng nhập)
$customerName = $currentUser['name'] ?? '';
$customerPhone = $currentUser['phone'] ?? '';
$customerAddress = $currentUser['address'] ?? '';
$customerNote = '';
$paymentMethod = 'cod';

$subtotal = cart_total();
$shippingFee = ($subtotal >= 300000) ? 0 : 30000;
$grandTotal = $subtotal + $shippingFee;

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $customerName = trim($_POST['customer_name'] ?? '');
    $customerPhone = trim($_POST['customer_phone'] ?? '');
    $customerAddress = trim($_POST['customer_address'] ?? '');
    $customerNote = trim($_POST['customer_note'] ?? '');
    $paymentMethod = in_array($_POST['payment_method'] ?? '', ['cod', 'bank_transfer']) ? $_POST['payment_method'] : 'cod';

    if (empty($customerName)) {
        $errors[] = 'Vui lòng nhập họ và tên người nhận hàng.';
    }
    if (empty($customerPhone)) {
        $errors[] = 'Vui lòng nhập số điện thoại người nhận hàng.';
    }
    if (empty($customerAddress)) {
        $errors[] = 'Vui lòng nhập địa chỉ giao hàng cụ thể.';
    }

    if (empty($errors)) {
        $pdo = db();
        try {
            // Khởi chạy Transaction đảm bảo tính toàn vẹn dữ liệu
            $pdo->beginTransaction();

            // 1. Kiểm tra tồn kho trước khi đặt
            foreach ($cartItems as $pId => $item) {
                $checkStmt = $pdo->prepare("SELECT stock, name FROM products WHERE id = ? FOR UPDATE");
                $checkStmt->execute([$pId]);
                $prod = $checkStmt->fetch();

                if (!$prod || $prod['stock'] < $item['quantity']) {
                    $prodName = $prod['name'] ?? $item['name'];
                    throw new Exception("Sản phẩm \"{$prodName}\" trong kho chỉ còn " . ($prod['stock'] ?? 0) . " chiếc, không đủ đáp ứng số lượng bạn đặt.");
                }
            }

            // 2. Thêm mới bản ghi vào bảng orders
            $userId = $currentUser ? $currentUser['id'] : null;
            $orderStmt = $pdo->prepare("
                INSERT INTO orders (user_id, customer_name, customer_phone, customer_address, customer_note, total_amount, payment_method, status)
                VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')
            ");
            $orderStmt->execute([
                $userId,
                $customerName,
                $customerPhone,
                $customerAddress,
                $customerNote,
                $grandTotal,
                $paymentMethod
            ]);

            $orderId = $pdo->lastInsertId();

            // 3. Thêm các món vào bảng order_items & trừ tồn kho trong products
            $itemStmt = $pdo->prepare("
                INSERT INTO order_items (order_id, product_id, product_name, price, quantity, subtotal)
                VALUES (?, ?, ?, ?, ?, ?)
            ");

            $stockStmt = $pdo->prepare("
                UPDATE products 
                SET stock = stock - ? 
                WHERE id = ?
            ");

            foreach ($cartItems as $pId => $item) {
                $itemSubtotal = $item['price'] * $item['quantity'];
                
                $itemStmt->execute([
                    $orderId,
                    $pId,
                    $item['name'],
                    $item['price'],
                    $item['quantity'],
                    $itemSubtotal
                ]);

                $stockStmt->execute([
                    $item['quantity'],
                    $pId
                ]);
            }

            // Hoàn tất Transaction
            $pdo->commit();

            // Xóa sạch giỏ hàng sau khi đặt thành công
            unset($_SESSION['cart']);

            set_flash('success', 'Chúc mừng bạn đã đặt hàng thành công! Mã đơn hàng của bạn là #' . $orderId);
            redirect(base_url('orders/order_detail.php?id=' . $orderId . '&new=1'));

        } catch (Exception $e) {
            // Rollback nếu gặp bất kỳ lỗi nào
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $errors[] = $e->getMessage();
        }
    }
}

$pageTitle = 'Thanh Toán & Đặt Hàng';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
    
    <div class="mb-8">
        <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight flex items-center gap-3">
            <i class="fa-solid fa-credit-card text-emerald-600"></i> Thông Tin Đặt Hàng & Giao Hàng
        </h1>
        <p class="text-xs text-slate-500 mt-1">Vui lòng điền thông tin chính xác để nhân viên giao hàng liên hệ thuận lợi</p>
    </div>

    <?php if (!empty($errors)): ?>
        <div class="mb-8 p-4 rounded-2xl bg-rose-50 border border-rose-200 text-xs text-rose-700 space-y-1.5 shadow-sm">
            <p class="font-bold flex items-center gap-1.5"><i class="fa-solid fa-circle-exclamation"></i> Không thể hoàn tất đơn hàng:</p>
            <ul class="list-disc list-inside space-y-0.5">
                <?php foreach ($errors as $err): ?>
                    <li><?= e($err) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form action="<?= base_url('orders/checkout.php') ?>" method="POST">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
            
            <!-- Cột điền thông tin giao hàng & phương thức thanh toán -->
            <div class="lg:col-span-7 space-y-6">
                
                <!-- Khối 1: Thông tin người nhận -->
                <div class="bg-white rounded-3xl border border-slate-200/80 shadow-sm p-6 sm:p-8 space-y-4">
                    <h2 class="text-base font-bold text-slate-800 flex items-center gap-2 pb-3 border-b border-slate-100">
                        <i class="fa-solid fa-location-dot text-emerald-600"></i> Địa Chỉ Nhận Hàng
                    </h2>

                    <?php if (!$currentUser): ?>
                        <div class="p-3 bg-emerald-50 rounded-xl border border-emerald-200 text-xs text-emerald-800 flex items-center justify-between">
                            <span>Bạn đã có tài khoản thành viên?</span>
                            <a href="<?= base_url('auth/login.php?redirect=' . urlencode(base_url('orders/checkout.php'))) ?>" class="font-bold text-emerald-700 underline">Đăng nhập ngay</a>
                        </div>
                    <?php endif; ?>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Họ và tên người nhận <span class="text-rose-500">*</span></label>
                        <input type="text" name="customer_name" value="<?= e($customerName) ?>" required 
                               class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:bg-white focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 focus:outline-none transition"
                               placeholder="Ví dụ: Nguyễn Văn A">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Số điện thoại liên hệ <span class="text-rose-500">*</span></label>
                        <input type="tel" name="customer_phone" value="<?= e($customerPhone) ?>" required 
                               class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:bg-white focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 focus:outline-none transition"
                               placeholder="Ví dụ: 0987654321">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Địa chỉ giao hàng tận nơi <span class="text-rose-500">*</span></label>
                        <textarea name="customer_address" rows="2" required 
                                  class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:bg-white focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 focus:outline-none transition"
                                  placeholder="Số nhà, ngõ/ngách, tên đường, phường/xã, quận/huyện..."><?= e($customerAddress) ?></textarea>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Ghi chú đơn hàng (Tùy chọn)</label>
                        <input type="text" name="customer_note" value="<?= e($customerNote) ?>" 
                               class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:bg-white focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 focus:outline-none transition"
                               placeholder="Ví dụ: Giao vào buổi chiều, gọi trước khi đến...">
                    </div>
                </div>

                <!-- Khối 2: Lựa chọn Phương thức thanh toán -->
                <div class="bg-white rounded-3xl border border-slate-200/80 shadow-sm p-6 sm:p-8 space-y-4">
                    <h2 class="text-base font-bold text-slate-800 flex items-center gap-2 pb-3 border-b border-slate-100">
                        <i class="fa-solid fa-wallet text-emerald-600"></i> Phương Thức Thanh Toán
                    </h2>

                    <div class="space-y-3">
                        <!-- Option COD -->
                        <label class="flex items-start gap-3.5 p-4 rounded-2xl border-2 cursor-pointer transition has-[:checked]:border-emerald-500 has-[:checked]:bg-emerald-50/50 border-slate-200 hover:border-slate-300">
                            <input type="radio" name="payment_method" value="cod" <?= $paymentMethod === 'cod' ? 'checked' : '' ?> class="mt-1 text-emerald-600 focus:ring-emerald-500">
                            <div>
                                <div class="font-bold text-sm text-slate-800 flex items-center gap-2">
                                    <i class="fa-solid fa-money-bill-wave text-emerald-600"></i> Thanh toán tiền mặt khi nhận hàng (COD)
                                </div>
                                <p class="text-xs text-slate-500 mt-0.5">Bạn chỉ thanh toán tiền cho nhân viên giao hàng sau khi đã nhận và kiểm tra kiện hàng.</p>
                            </div>
                        </label>

                        <!-- Option VietQR Chuyển khoản -->
                        <label class="flex items-start gap-3.5 p-4 rounded-2xl border-2 cursor-pointer transition has-[:checked]:border-emerald-500 has-[:checked]:bg-emerald-50/50 border-slate-200 hover:border-slate-300">
                            <input type="radio" name="payment_method" value="bank_transfer" <?= $paymentMethod === 'bank_transfer' ? 'checked' : '' ?> class="mt-1 text-emerald-600 focus:ring-emerald-500">
                            <div>
                                <div class="font-bold text-sm text-slate-800 flex items-center gap-2">
                                    <i class="fa-solid fa-qrcode text-sky-600"></i> Chuyển khoản nhanh qua VietQR (Ngân hàng / MoMo)
                                </div>
                                <p class="text-xs text-slate-500 mt-0.5">Hệ thống tạo mã QR tự động kèm số tiền và mã đơn hàng để bạn quét thanh toán tức thì.</p>
                            </div>
                        </label>
                    </div>
                </div>

            </div>

            <!-- Cột tóm tắt đơn hàng bên phải -->
            <div class="lg:col-span-5 bg-white rounded-3xl border border-slate-200/80 shadow-sm p-6 sm:p-8 sticky top-24 space-y-5">
                <h3 class="font-bold text-slate-800 text-base pb-3 border-b border-slate-100 flex items-center justify-between">
                    <span class="flex items-center gap-2">
                        <i class="fa-solid fa-basket-shopping text-emerald-600"></i> Đơn Hàng Của Bạn
                    </span>
                    <a href="<?= base_url('cart.php') ?>" class="text-xs text-emerald-600 hover:underline">Sửa</a>
                </h3>

                <!-- Danh sách món hàng tóm tắt -->
                <div class="divide-y divide-slate-100 max-h-72 overflow-y-auto pr-1">
                    <?php foreach ($cartItems as $item): 
                        $itemSubtotal = $item['price'] * $item['quantity'];
                    ?>
                        <div class="py-3 flex items-center justify-between gap-3 text-xs">
                            <div class="flex-1 pr-2">
                                <p class="font-semibold text-slate-800 truncate"><?= e($item['name']) ?></p>
                                <p class="text-slate-400"><?= format_money($item['price']) ?> × <?= (int)$item['quantity'] ?></p>
                            </div>
                            <span class="font-bold text-slate-700 whitespace-nowrap"><?= format_money($itemSubtotal) ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Bảng tổng tiền -->
                <div class="pt-4 border-t border-slate-100 space-y-2 text-xs text-slate-600">
                    <div class="flex justify-between items-center">
                        <span>Tiền hàng:</span>
                        <span class="font-semibold text-slate-800"><?= format_money($subtotal) ?></span>
                    </div>

                    <div class="flex justify-between items-center">
                        <span>Phí vận chuyển:</span>
                        <span class="font-semibold text-slate-800"><?= $shippingFee === 0 ? 'Miễn phí (0 ₫)' : format_money($shippingFee) ?></span>
                    </div>

                    <div class="pt-3 border-t border-slate-100 flex justify-between items-center text-sm">
                        <span class="font-bold text-slate-800">Tổng cộng phải trả:</span>
                        <span class="text-2xl font-extrabold text-emerald-600"><?= format_money($grandTotal) ?></span>
                    </div>
                </div>

                <!-- Nút xác nhận đặt mua -->
                <button type="submit" 
                        class="w-full py-4 px-4 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-2xl shadow-xl shadow-emerald-600/20 transition flex items-center justify-center gap-2 text-base">
                    <i class="fa-solid fa-circle-check"></i> Xác Nhận Đặt Hàng Ngay
                </button>

                <p class="text-[11px] text-center text-slate-400">
                    Bằng việc nhấn Đặt Hàng, bạn đồng ý với Điều khoản dịch vụ và Chính sách bảo mật của Tạp Hóa Store.
                </p>
            </div>

        </div>
    </form>

</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
