<?php
/**
 * Trang Chi Tiết Đơn Hàng & Hóa Đơn Thanh Toán
 * Tự động tạo mã VietQR nếu chọn hình thức chuyển khoản
 */
require_once __DIR__ . '/../includes/functions.php';

$orderId = (int)($_GET['id'] ?? 0);
$isNew = isset($_GET['new']);

if ($orderId <= 0) {
    set_flash('error', 'Mã đơn hàng không hợp lệ.');
    redirect(base_url('index.php'));
}

try {
    $stmt = db()->prepare("SELECT * FROM orders WHERE id = ? LIMIT 1");
    $stmt->execute([$orderId]);
    $order = $stmt->fetch();

    if (!$order) {
        set_flash('error', 'Không tìm thấy đơn hàng yêu cầu.');
        redirect(base_url('index.php'));
    }

    $currentUser = current_user();

    // Kiểm tra quyền xem đơn hàng
    // Chỉ cho phép xem nếu: (1) vừa đặt đơn mới xong, hoặc (2) là chủ đơn hàng, hoặc (3) là Admin
    if (!$isNew && !is_admin()) {
        if (!$currentUser || (int)$order['user_id'] !== (int)$currentUser['id']) {
            set_flash('error', 'Bạn không có quyền xem thông tin đơn hàng này.');
            redirect(base_url('index.php'));
        }
    }

    // Lấy danh sách sản phẩm trong đơn hàng
    $itemsStmt = db()->prepare("
        SELECT oi.*, p.image_url 
        FROM order_items oi 
        LEFT JOIN products p ON oi.product_id = p.id 
        WHERE oi.order_id = ?
    ");
    $itemsStmt->execute([$orderId]);
    $orderItems = $itemsStmt->fetchAll();

} catch (PDOException $e) {
    set_flash('error', 'Lỗi truy vấn dữ liệu đơn hàng: ' . $e->getMessage());
    redirect(base_url('index.php'));
}

$statusMap = [
    'pending'    => ['label' => 'Chờ xác nhận', 'color' => 'bg-amber-100 text-amber-800 border-amber-300', 'icon' => 'fa-clock'],
    'processing' => ['label' => 'Đang giao hàng', 'color' => 'bg-sky-100 text-sky-800 border-sky-300', 'icon' => 'fa-truck-ramp-box'],
    'completed'  => ['label' => 'Đã giao thành công', 'color' => 'bg-emerald-100 text-emerald-800 border-emerald-300', 'icon' => 'fa-circle-check'],
    'cancelled'  => ['label' => 'Đã hủy đơn', 'color' => 'bg-rose-100 text-rose-800 border-rose-300', 'icon' => 'fa-circle-xmark']
];
$currentStatus = $statusMap[$order['status']] ?? $statusMap['pending'];

// Tạo URL mã VietQR thanh toán tự động
$bankId = 'MB'; // Ngân hàng MB Bank
$accountNo = '0345678999'; // Số tài khoản cửa hàng
$accountName = 'TAP HOA STORE';
$transferMemo = 'DH' . $order['id'];
$vietQrUrl = "https://img.vietqr.io/image/{$bankId}-{$accountNo}-compact2.png?amount={$order['total_amount']}&addInfo=" . urlencode($transferMemo) . "&accountName=" . urlencode($accountName);

$pageTitle = 'Chi Tiết Đơn Hàng #' . $orderId;
require_once __DIR__ . '/../includes/header.php';
?>

<div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
    
    <!-- Tiêu đề & Thông báo chúc mừng nếu vừa đặt -->
    <?php if ($isNew): ?>
        <div class="bg-gradient-to-r from-emerald-500 to-teal-600 rounded-3xl p-6 sm:p-8 text-white shadow-xl shadow-emerald-500/10 mb-8 flex flex-col sm:flex-row items-center justify-between gap-4">
            <div class="flex items-center gap-4 text-center sm:text-left">
                <div class="w-14 h-14 rounded-2xl bg-white/20 backdrop-blur-md flex items-center justify-center text-3xl flex-shrink-0">
                    <i class="fa-solid fa-circle-check text-white"></i>
                </div>
                <div>
                    <h2 class="text-xl sm:text-2xl font-extrabold">Đặt Hàng Thành Công!</h2>
                    <p class="text-emerald-100 text-xs sm:text-sm mt-0.5">Cảm ơn bạn đã tin tưởng mua sắm tại Tạp Hóa Store. Đơn hàng đang được chuẩn bị đóng gói.</p>
                </div>
            </div>
            <a href="<?= base_url('index.php') ?>" class="px-5 py-2.5 bg-white text-emerald-800 hover:bg-emerald-50 font-bold rounded-xl text-xs whitespace-nowrap shadow transition">
                Tiếp Tục Mua Sắm
            </a>
        </div>
    <?php endif; ?>

    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
        <div>
            <div class="flex items-center gap-3">
                <h1 class="text-2xl font-bold text-slate-900">Đơn Hàng #<?= $order['id'] ?></h1>
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold border <?= $currentStatus['color'] ?>">
                    <i class="fa-solid <?= $currentStatus['icon'] ?>"></i>
                    <?= $currentStatus['label'] ?>
                </span>
            </div>
            <p class="text-xs text-slate-400 mt-1">
                Thời gian đặt: <strong class="text-slate-600"><?= date('H:i - d/m/Y', strtotime($order['created_at'])) ?></strong>
            </p>
        </div>

        <div class="flex items-center gap-2">
            <button onclick="window.print()" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-semibold transition flex items-center gap-1.5">
                <i class="fa-solid fa-print"></i> In đơn hàng
            </button>
            <a href="<?= base_url('orders/my_orders.php') ?>" class="px-4 py-2 bg-emerald-50 hover:bg-emerald-100 text-emerald-700 border border-emerald-200 rounded-xl text-xs font-semibold transition">
                <i class="fa-solid fa-list mr-1"></i> Danh sách đơn của tôi
            </a>
        </div>
    </div>

    <!-- Khối chuyển khoản VietQR nếu chọn hình thức Bank Transfer -->
    <?php if ($order['payment_method'] === 'bank_transfer' && $order['status'] !== 'completed'): ?>
        <div class="bg-gradient-to-br from-sky-50 to-indigo-50 border border-sky-200 rounded-3xl p-6 sm:p-8 mb-8 shadow-sm">
            <div class="grid grid-cols-1 md:grid-cols-12 gap-6 items-center">
                
                <div class="md:col-span-7 space-y-3">
                    <span class="px-3 py-1 bg-sky-600 text-white font-bold text-[10px] rounded-full uppercase tracking-wider">
                        Thanh Toán Chuyển Khoản
                    </span>
                    <h3 class="text-lg font-bold text-slate-900">Quét Mã VietQR Thanh Toán Nhanh</h3>
                    <p class="text-xs text-slate-600 leading-relaxed">
                        Mở ứng dụng ngân hàng bất kỳ (Vietcombank, MB, Techcombank, BIDV...) hoặc ví MoMo, chọn tính năng <strong>Quét mã QR</strong> để thanh toán tự động không cần nhập thông tin.
                    </p>

                    <div class="bg-white/80 backdrop-blur-sm rounded-2xl p-4 border border-sky-100 space-y-2 text-xs text-slate-700">
                        <div class="flex justify-between">
                            <span class="text-slate-400">Ngân hàng thụ hưởng:</span>
                            <span class="font-bold">MB Bank (Ngân Hàng Quân Đội)</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-400">Số tài khoản:</span>
                            <span class="font-mono font-bold text-sky-700 text-sm"><?= $accountNo ?></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-400">Tên người nhận:</span>
                            <span class="font-bold"><?= $accountName ?></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-400">Số tiền cần chuyển:</span>
                            <span class="font-bold text-emerald-600 text-sm"><?= format_money($order['total_amount']) ?></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-400">Nội dung chuyển khoản:</span>
                            <span class="font-mono font-bold text-rose-600 bg-rose-50 px-2 py-0.5 rounded"><?= $transferMemo ?></span>
                        </div>
                    </div>
                </div>

                <div class="md:col-span-5 text-center">
                    <div class="inline-block p-3 bg-white rounded-2xl shadow-lg border border-sky-200">
                        <img src="<?= e($vietQrUrl) ?>" alt="VietQR Thanh Toán" class="w-48 sm:w-56 mx-auto rounded-xl">
                        <p class="text-[11px] text-slate-500 mt-2 font-medium">Quét bằng App Ngân Hàng để thanh toán</p>
                    </div>
                </div>

            </div>
        </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 md:grid-cols-12 gap-8 items-start">
        
        <!-- Bảng danh sách mặt hàng (Bên trái) -->
        <div class="md:col-span-8 bg-white rounded-3xl border border-slate-200/80 shadow-sm overflow-hidden p-6">
            <h3 class="font-bold text-slate-800 text-base mb-4 pb-3 border-b border-slate-100 flex items-center gap-2">
                <i class="fa-solid fa-boxes-stacked text-emerald-600"></i> Các Sản Phẩm Đã Mua
            </h3>

            <div class="divide-y divide-slate-100">
                <?php foreach ($orderItems as $item): 
                    $itemImg = $item['image_url'];
                    if (empty($itemImg)) {
                        $itemImg = 'https://images.unsplash.com/photo-1542838132-92c53300491e?auto=format&fit=crop&w=200&q=80';
                    } elseif (strpos($itemImg, 'http') !== 0) {
                        $itemImg = base_url($itemImg);
                    }
                ?>
                    <div class="py-4 flex items-center justify-between gap-4">
                        <div class="flex items-center gap-3">
                            <div class="w-14 h-14 rounded-xl bg-slate-100 overflow-hidden border border-slate-200 flex-shrink-0">
                                <img src="<?= e($itemImg) ?>" alt="<?= e($item['product_name']) ?>" class="w-full h-full object-cover">
                            </div>
                            <div>
                                <h4 class="font-semibold text-slate-800 text-xs sm:text-sm"><?= e($item['product_name']) ?></h4>
                                <p class="text-xs text-slate-400 mt-0.5">
                                    Đơn giá: <?= format_money($item['price']) ?> × <strong class="text-slate-700"><?= (int)$item['quantity'] ?></strong>
                                </p>
                            </div>
                        </div>
                        <div class="text-right">
                            <span class="font-bold text-slate-800 text-sm"><?= format_money($item['subtotal']) ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="pt-6 mt-4 border-t border-slate-100 space-y-2 text-xs text-slate-600">
                <div class="flex justify-between">
                    <span>Tổng tiền hàng:</span>
                    <span class="font-semibold text-slate-800"><?= format_money($order['total_amount']) ?></span>
                </div>
                <div class="flex justify-between">
                    <span>Phí vận chuyển:</span>
                    <span class="font-semibold text-emerald-600">Miễn phí</span>
                </div>
                <div class="pt-3 border-t border-slate-100 flex justify-between text-sm">
                    <span class="font-bold text-slate-800">Tổng thanh toán:</span>
                    <span class="text-xl font-extrabold text-emerald-600"><?= format_money($order['total_amount']) ?></span>
                </div>
            </div>
        </div>

        <!-- Thông tin giao nhận bên phải -->
        <div class="md:col-span-4 space-y-6">
            <div class="bg-white rounded-3xl border border-slate-200/80 shadow-sm p-6 space-y-4 text-xs">
                <h3 class="font-bold text-slate-800 text-sm pb-3 border-b border-slate-100 flex items-center gap-2">
                    <i class="fa-solid fa-user-check text-emerald-600"></i> Người Nhận Hàng
                </h3>

                <div class="space-y-2.5 text-slate-600">
                    <div>
                        <span class="text-slate-400 block">Họ và tên:</span>
                        <strong class="text-slate-800 text-sm"><?= e($order['customer_name']) ?></strong>
                    </div>

                    <div>
                        <span class="text-slate-400 block">Số điện thoại:</span>
                        <strong class="text-slate-800"><?= e($order['customer_phone']) ?></strong>
                    </div>

                    <div>
                        <span class="text-slate-400 block">Địa chỉ giao:</span>
                        <p class="text-slate-700 leading-relaxed font-medium"><?= e($order['customer_address']) ?></p>
                    </div>

                    <?php if (!empty($order['customer_note'])): ?>
                        <div>
                            <span class="text-slate-400 block">Ghi chú giao hàng:</span>
                            <p class="italic text-slate-500 bg-slate-50 p-2.5 rounded-xl border border-slate-100"><?= e($order['customer_note']) ?></p>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="pt-4 border-t border-slate-100">
                    <span class="text-slate-400 block mb-1">Hình thức thanh toán:</span>
                    <?php if ($order['payment_method'] === 'bank_transfer'): ?>
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-sky-50 text-sky-700 font-bold border border-sky-200">
                            <i class="fa-solid fa-qrcode"></i> Chuyển khoản VietQR
                        </span>
                    <?php else: ?>
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-emerald-50 text-emerald-700 font-bold border border-emerald-200">
                            <i class="fa-solid fa-money-bill-wave"></i> Thanh toán tiền mặt (COD)
                        </span>
                    <?php endif; ?>
                </div>
            </div>
        </div>

    </div>

</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
