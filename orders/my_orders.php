<?php
/**
 * Lịch Sử Đơn Hàng Của Khách Hàng (My Orders)
 */
require_once __DIR__ . '/../includes/functions.php';

require_login();

$currentUser = current_user();

try {
    $stmt = db()->prepare("
        SELECT o.*, COUNT(oi.id) as total_items 
        FROM orders o 
        LEFT JOIN order_items oi ON o.id = oi.order_id 
        WHERE o.user_id = ? 
        GROUP BY o.id 
        ORDER BY o.id DESC
    ");
    $stmt->execute([$currentUser['id']]);
    $myOrders = $stmt->fetchAll();
} catch (PDOException $e) {
    set_flash('error', 'Lỗi truy vấn lịch sử đơn hàng: ' . $e->getMessage());
    $myOrders = [];
}

$statusMap = [
    'pending'    => ['label' => 'Chờ xác nhận', 'color' => 'bg-amber-100 text-amber-800 border-amber-300'],
    'processing' => ['label' => 'Đang giao hàng', 'color' => 'bg-sky-100 text-sky-800 border-sky-300'],
    'completed'  => ['label' => 'Đã giao thành công', 'color' => 'bg-emerald-100 text-emerald-800 border-emerald-300'],
    'cancelled'  => ['label' => 'Đã hủy đơn', 'color' => 'bg-rose-100 text-rose-800 border-rose-300']
];

$pageTitle = 'Đơn Hàng Của Tôi';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
    
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-8">
        <div>
            <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight flex items-center gap-3">
                <i class="fa-solid fa-box text-emerald-600"></i> Đơn Hàng Của Tôi
            </h1>
            <p class="text-xs text-slate-500 mt-1">Theo dõi quá trình vận chuyển và trạng thái các đơn hàng đã đặt</p>
        </div>
        <a href="<?= base_url('index.php') ?>" class="px-4 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-semibold rounded-xl text-xs shadow-md shadow-emerald-600/20 transition flex items-center gap-2">
            <i class="fa-solid fa-cart-shopping"></i> Mua sắm thêm
        </a>
    </div>

    <?php if (empty($myOrders)): ?>
        <div class="bg-white rounded-3xl border border-slate-200/80 shadow-sm p-12 text-center max-w-md mx-auto my-6">
            <div class="w-16 h-16 bg-slate-100 text-slate-400 rounded-3xl flex items-center justify-center mx-auto mb-4 text-2xl">
                <i class="fa-solid fa-receipt"></i>
            </div>
            <h3 class="text-lg font-bold text-slate-800 mb-1">Bạn chưa có đơn hàng nào</h3>
            <p class="text-xs text-slate-500 mb-6">Hãy đặt hàng ngay hôm nay để nhận nhiều ưu đãi hấp dẫn từ Tạp Hóa Store nhé!</p>
            <a href="<?= base_url('index.php') ?>" class="inline-flex items-center gap-2 px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-semibold shadow-md shadow-emerald-600/20 transition">
                <i class="fa-solid fa-basket-shopping"></i> Mua Sắm Ngay
            </a>
        </div>
    <?php else: ?>
        <div class="bg-white rounded-3xl border border-slate-200/80 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-slate-50 text-slate-500 font-semibold border-b border-slate-100 uppercase tracking-wider text-[11px]">
                        <tr>
                            <th class="py-4 px-6">Mã Đơn</th>
                            <th class="py-4 px-6">Ngày Đặt</th>
                            <th class="py-4 px-6">Số Lượng Món</th>
                            <th class="py-4 px-6">Tổng Tiền</th>
                            <th class="py-4 px-6">Hình Thức</th>
                            <th class="py-4 px-6">Trạng Thái</th>
                            <th class="py-4 px-6 text-right">Thao Tác</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-slate-700">
                        <?php foreach ($myOrders as $o): 
                            $status = $statusMap[$o['status']] ?? $statusMap['pending'];
                        ?>
                            <tr class="hover:bg-slate-50/80 transition">
                                <td class="py-4 px-6 font-bold text-emerald-700">
                                    #<?= $o['id'] ?>
                                </td>
                                <td class="py-4 px-6 text-slate-500">
                                    <?= date('H:i - d/m/Y', strtotime($o['created_at'])) ?>
                                </td>
                                <td class="py-4 px-6">
                                    <span class="px-2 py-0.5 bg-slate-100 rounded-md font-medium text-slate-600">
                                        <?= (int)$o['total_items'] ?> sản phẩm
                                    </span>
                                </td>
                                <td class="py-4 px-6 font-bold text-slate-900 text-sm">
                                    <?= format_money($o['total_amount']) ?>
                                </td>
                                <td class="py-4 px-6">
                                    <?php if ($o['payment_method'] === 'bank_transfer'): ?>
                                        <span class="inline-flex items-center gap-1 text-sky-700 font-medium">
                                            <i class="fa-solid fa-qrcode text-sky-500"></i> Chuyển khoản
                                        </span>
                                    <?php else: ?>
                                        <span class="inline-flex items-center gap-1 text-slate-600">
                                            <i class="fa-solid fa-money-bill-wave text-emerald-500"></i> Tiền mặt
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="py-4 px-6">
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full font-bold border text-[11px] <?= $status['color'] ?>">
                                        <?= $status['label'] ?>
                                    </span>
                                </td>
                                <td class="py-4 px-6 text-right">
                                    <a href="<?= base_url('orders/order_detail.php?id=' . $o['id']) ?>" 
                                       class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-emerald-50 hover:bg-emerald-600 text-emerald-700 hover:text-white rounded-lg font-semibold transition border border-emerald-200 hover:border-transparent shadow-sm">
                                        <span>Chi tiết</span>
                                        <i class="fa-solid fa-chevron-right text-[10px]"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>

</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
