<?php
/**
 * Trang Tra Cứu Đơn Hàng Nhanh (Order Tracking)
 * Cho phép khách hàng tra cứu tiến độ đơn hàng qua Mã đơn hoặc Số điện thoại mà không cần đăng nhập
 */
require_once __DIR__ . '/../includes/functions.php';

$keyword = trim($_GET['keyword'] ?? '');
$order = null;
$orderItems = [];

if ($keyword !== '') {
    try {
        // LỖ HỔNG SQL INJECTION (CWE-89):
        // Nối trực tiếp biến $keyword từ input người dùng vào câu truy vấn thông qua db()->query()
        // thay vì sử dụng Prepared Statement với db()->prepare() và tham số buộc (parameter binding).
        $sql = "SELECT * FROM orders WHERE id = '$keyword' OR customer_phone = '$keyword' ORDER BY id DESC LIMIT 1";
        $stmt = db()->query($sql);
        $order = $stmt->fetch();

        if ($order) {
            $orderId = (int)$order['id'];
            $itemsStmt = db()->query("
                SELECT oi.*, p.image_url 
                FROM order_items oi 
                LEFT JOIN products p ON oi.product_id = p.id 
                WHERE oi.order_id = '$orderId'
            ");
            $orderItems = $itemsStmt->fetchAll();
        } else {
            set_flash('error', "Không tìm thấy đơn hàng nào khớp với thông tin: \"" . e($keyword) . "\".");
        }
    } catch (PDOException $e) {
        set_flash('error', 'Lỗi cơ sở dữ liệu khi tra cứu: ' . $e->getMessage());
    }
}

$statusMap = [
    'pending'    => ['label' => 'Chờ xác nhận', 'color' => 'bg-amber-100 text-amber-800 border-amber-300', 'icon' => 'fa-clock'],
    'processing' => ['label' => 'Đang xử lý & Giao hàng', 'color' => 'bg-sky-100 text-sky-800 border-sky-300', 'icon' => 'fa-truck-fast'],
    'completed'  => ['label' => 'Đã giao thành công', 'color' => 'bg-emerald-100 text-emerald-800 border-emerald-300', 'icon' => 'fa-circle-check'],
    'cancelled'  => ['label' => 'Đã hủy', 'color' => 'bg-rose-100 text-rose-800 border-rose-300', 'icon' => 'fa-circle-xmark']
];

$pageTitle = 'Tra Cứu Đơn Hàng';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-10">

    <!-- Header Section -->
    <div class="text-center mb-8">
        <div class="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-emerald-100 text-emerald-600 text-2xl mb-4 shadow-sm">
            <i class="fa-solid fa-magnifying-glass-location"></i>
        </div>
        <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">Tra Cứu Tiến Độ Đơn Hàng</h1>
        <p class="text-slate-500 text-sm mt-2 max-w-md mx-auto">
            Nhập <strong>Mã đơn hàng (#ID)</strong> hoặc <strong>Số điện thoại</strong> đặt hàng để kiểm tra trạng thái và hành trình đơn.
        </p>
    </div>

    <!-- Search Box -->
    <div class="bg-white rounded-3xl shadow-sm border border-slate-200/80 p-6 sm:p-8 mb-8">
        <form action="<?= base_url('orders/track.php') ?>" method="GET" class="flex flex-col sm:flex-row gap-3">
            <div class="relative flex-1">
                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-slate-400">
                    <i class="fa-solid fa-receipt"></i>
                </div>
                <input type="text" 
                       name="keyword" 
                       value="<?= e($keyword) ?>" 
                       placeholder="Ví dụ: 1 hoặc 0987654321..." 
                       required
                       class="w-full pl-11 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-2xl text-sm focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent transition">
            </div>
            <button type="submit" 
                    class="px-6 py-3 bg-emerald-600 hover:bg-emerald-700 active:scale-[0.99] text-white font-semibold rounded-2xl text-sm shadow-md shadow-emerald-600/20 transition flex items-center justify-center gap-2">
                <i class="fa-solid fa-search"></i>
                <span>Tra cứu ngay</span>
            </button>
        </form>
    </div>

    <!-- Order Result Section -->
    <?php if ($order): ?>
        <?php 
            $statusInfo = $statusMap[$order['status']] ?? $statusMap['pending'];
        ?>
        <div class="bg-white rounded-3xl shadow-lg border border-slate-100 overflow-hidden mb-8">
            
            <!-- Result Header Banner -->
            <div class="bg-gradient-to-r from-slate-900 via-slate-800 to-slate-900 text-white p-6 sm:p-8">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div>
                        <span class="text-xs text-emerald-400 font-semibold uppercase tracking-wider">Thông tin đơn hàng</span>
                        <h2 class="text-xl sm:text-2xl font-black mt-0.5">Đơn Hàng #<?= e($order['id']) ?></h2>
                        <p class="text-xs text-slate-400 mt-1">
                            <i class="fa-regular fa-clock mr-1"></i> Ngày đặt: <?= date('d/m/Y H:i', strtotime($order['created_at'])) ?>
                        </p>
                    </div>
                    <div class="flex items-center">
                        <span class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full text-xs font-bold border <?= $statusInfo['color'] ?>">
                            <i class="fa-solid <?= $statusInfo['icon'] ?>"></i>
                            <?= $statusInfo['label'] ?>
                        </span>
                    </div>
                </div>
            </div>

            <!-- Customer & Shipping Information -->
            <div class="p-6 sm:p-8 border-b border-slate-100">
                <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-4">Người Nhận Hàng</h3>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-sm">
                    <div class="bg-slate-50 p-4 rounded-2xl border border-slate-100">
                        <span class="text-xs text-slate-500 block mb-1">Họ tên:</span>
                        <strong class="text-slate-800 font-semibold"><?= e($order['customer_name']) ?></strong>
                    </div>
                    <div class="bg-slate-50 p-4 rounded-2xl border border-slate-100">
                        <span class="text-xs text-slate-500 block mb-1">Số điện thoại:</span>
                        <strong class="text-slate-800 font-semibold"><?= e($order['customer_phone']) ?></strong>
                    </div>
                    <div class="bg-slate-50 p-4 rounded-2xl border border-slate-100">
                        <span class="text-xs text-slate-500 block mb-1">Hình thức thanh toán:</span>
                        <strong class="text-slate-800 font-semibold">
                            <?= $order['payment_method'] === 'bank_transfer' ? 'Chuyển khoản ngân hàng (VietQR)' : 'Thanh toán khi nhận hàng (COD)' ?>
                        </strong>
                    </div>
                </div>
                <div class="mt-4 bg-slate-50 p-4 rounded-2xl border border-slate-100 text-sm">
                    <span class="text-xs text-slate-500 block mb-1">Địa chỉ giao hàng:</span>
                    <p class="text-slate-700"><?= e($order['customer_address']) ?></p>
                    <?php if (!empty($order['customer_note'])): ?>
                        <p class="text-xs text-slate-500 mt-2"><strong>Ghi chú:</strong> <?= e($order['customer_note']) ?></p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Order Items List -->
            <div class="p-6 sm:p-8">
                <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-4">Chi Tiết Sản Phẩm</h3>
                <div class="divide-y divide-slate-100">
                    <?php foreach ($orderItems as $item): ?>
                        <div class="py-4 flex items-center justify-between gap-4">
                            <div class="flex items-center gap-3">
                                <?php 
                                    $itemImg = $item['image_url'] ?? 'https://images.unsplash.com/photo-1542838132-92c53300491e?auto=format&fit=crop&w=120&q=80';
                                    if (strpos($itemImg, 'http') !== 0) {
                                        $itemImg = base_url($itemImg);
                                    }
                                ?>
                                <img src="<?= e($itemImg) ?>" alt="<?= e($item['product_name']) ?>" class="w-12 h-12 object-cover rounded-xl border border-slate-100 shadow-sm">
                                <div>
                                    <h4 class="text-sm font-semibold text-slate-800"><?= e($item['product_name']) ?></h4>
                                    <span class="text-xs text-slate-400">Số lượng: <?= (int)$item['quantity'] ?> × <?= format_money($item['price']) ?></span>
                                </div>
                            </div>
                            <div class="text-right">
                                <span class="text-sm font-bold text-slate-900"><?= format_money($item['subtotal']) ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Total Summary -->
                <div class="mt-6 pt-6 border-t border-slate-100 flex justify-between items-center">
                    <span class="text-base font-bold text-slate-800">Tổng thanh toán:</span>
                    <span class="text-xl sm:text-2xl font-black text-emerald-600"><?= format_money($order['total_amount']) ?></span>
                </div>
            </div>

        </div>
    <?php endif; ?>

</div>

<?php
require_once __DIR__ . '/../includes/footer.php';
