<?php
/**
 * Quản Lý Tất Cả Đơn Hàng (Dành cho Quản trị viên / Chủ cửa hàng)
 * Xem danh sách, lọc theo trạng thái và cập nhật tiến độ đơn hàng
 */
require_once __DIR__ . '/../includes/functions.php';

require_admin();

// Cập nhật trạng thái đơn hàng nếu có POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    $orderId = (int)($_POST['order_id'] ?? 0);
    $newStatus = $_POST['status'] ?? '';
    
    if ($orderId > 0 && in_array($newStatus, ['pending', 'processing', 'completed', 'cancelled'])) {
        try {
            $stmt = db()->prepare("UPDATE orders SET status = ? WHERE id = ?");
            $stmt->execute([$newStatus, $orderId]);
            set_flash('success', "Đã cập nhật trạng thái đơn hàng #{$orderId} thành công!");
        } catch (PDOException $e) {
            set_flash('error', 'Lỗi cập nhật trạng thái: ' . $e->getMessage());
        }
    }
    redirect(base_url('orders/manage_orders.php' . (isset($_GET['status']) ? '?status=' . urlencode($_GET['status']) : '')));
}

$statusFilter = $_GET['status'] ?? 'all';

$sql = "
    SELECT o.*, u.email as user_email, COUNT(oi.id) as total_items 
    FROM orders o 
    LEFT JOIN users u ON o.user_id = u.id 
    LEFT JOIN order_items oi ON o.id = oi.order_id 
";
$params = [];

if ($statusFilter !== 'all' && in_array($statusFilter, ['pending', 'processing', 'completed', 'cancelled'])) {
    $sql .= " WHERE o.status = ?";
    $params[] = $statusFilter;
}

$sql .= " GROUP BY o.id ORDER BY o.id DESC";

try {
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    $orders = $stmt->fetchAll();

    // Thống kê nhanh
    $statsStmt = db()->query("
        SELECT 
            COUNT(*) as total_orders,
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_count,
            SUM(CASE WHEN status = 'processing' THEN 1 ELSE 0 END) as processing_count,
            SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_count,
            SUM(CASE WHEN status = 'completed' THEN total_amount ELSE 0 END) as total_revenue
        FROM orders
    ");
    $stats = $statsStmt->fetch();

} catch (PDOException $e) {
    set_flash('error', 'Lỗi tải danh sách đơn hàng: ' . $e->getMessage());
    $orders = [];
    $stats = [];
}

$statusMap = [
    'pending'    => ['label' => 'Chờ xác nhận', 'color' => 'bg-amber-100 text-amber-800 border-amber-300'],
    'processing' => ['label' => 'Đang giao hàng', 'color' => 'bg-sky-100 text-sky-800 border-sky-300'],
    'completed'  => ['label' => 'Đã giao thành công', 'color' => 'bg-emerald-100 text-emerald-800 border-emerald-300'],
    'cancelled'  => ['label' => 'Đã hủy', 'color' => 'bg-rose-100 text-rose-800 border-rose-300']
];

$pageTitle = 'Quản Lý Đơn Hàng (Admin)';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
    
    <div class="mb-8">
        <div class="flex items-center gap-2 text-xs text-amber-600 font-bold uppercase tracking-wider mb-1">
            <i class="fa-solid fa-user-shield"></i> Khu Vực Quản Trị Viên
        </div>
        <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight flex items-center gap-3">
            <i class="fa-solid fa-clipboard-list text-emerald-600"></i> Quản Lý Toàn Bộ Đơn Hàng
        </h1>
        <p class="text-xs text-slate-500 mt-1">Kiểm tra thông tin giao nhận, người mua và chuyển đổi trạng thái xử lý đơn</p>
    </div>

    <!-- Thống kê nhanh thẻ Card -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
        <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm">
            <div class="flex items-center justify-between">
                <span class="text-xs text-slate-500 font-medium">Tổng số đơn</span>
                <span class="w-8 h-8 rounded-lg bg-slate-100 text-slate-600 flex items-center justify-center text-xs"><i class="fa-solid fa-receipt"></i></span>
            </div>
            <p class="text-2xl font-extrabold text-slate-900 mt-2"><?= (int)($stats['total_orders'] ?? 0) ?></p>
        </div>

        <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm">
            <div class="flex items-center justify-between">
                <span class="text-xs text-amber-600 font-medium">Cần xử lý gấp</span>
                <span class="w-8 h-8 rounded-lg bg-amber-100 text-amber-600 flex items-center justify-center text-xs"><i class="fa-solid fa-hourglass-half"></i></span>
            </div>
            <p class="text-2xl font-extrabold text-amber-600 mt-2"><?= (int)($stats['pending_count'] ?? 0) ?></p>
        </div>

        <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm">
            <div class="flex items-center justify-between">
                <span class="text-xs text-sky-600 font-medium">Đang trên đường giao</span>
                <span class="w-8 h-8 rounded-lg bg-sky-100 text-sky-600 flex items-center justify-center text-xs"><i class="fa-solid fa-truck"></i></span>
            </div>
            <p class="text-2xl font-extrabold text-sky-600 mt-2"><?= (int)($stats['processing_count'] ?? 0) ?></p>
        </div>

        <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm">
            <div class="flex items-center justify-between">
                <span class="text-xs text-emerald-600 font-medium">Doanh thu hoàn tất</span>
                <span class="w-8 h-8 rounded-lg bg-emerald-100 text-emerald-600 flex items-center justify-center text-xs"><i class="fa-solid fa-sack-dollar"></i></span>
            </div>
            <p class="text-xl font-extrabold text-emerald-600 mt-2"><?= format_money($stats['total_revenue'] ?? 0) ?></p>
        </div>
    </div>

    <!-- Bộ lọc trạng thái đơn -->
    <div class="flex items-center gap-2 overflow-x-auto pb-4 mb-4">
        <a href="<?= base_url('orders/manage_orders.php?status=all') ?>" 
           class="px-3.5 py-1.5 rounded-xl text-xs font-semibold whitespace-nowrap transition <?= $statusFilter === 'all' ? 'bg-slate-900 text-white' : 'bg-white text-slate-600 hover:bg-slate-100 border border-slate-200' ?>">
            Tất cả (<?= (int)($stats['total_orders'] ?? 0) ?>)
        </a>
        <a href="<?= base_url('orders/manage_orders.php?status=pending') ?>" 
           class="px-3.5 py-1.5 rounded-xl text-xs font-semibold whitespace-nowrap transition <?= $statusFilter === 'pending' ? 'bg-amber-500 text-white' : 'bg-white text-slate-600 hover:bg-slate-100 border border-slate-200' ?>">
            Chờ xác nhận (<?= (int)($stats['pending_count'] ?? 0) ?>)
        </a>
        <a href="<?= base_url('orders/manage_orders.php?status=processing') ?>" 
           class="px-3.5 py-1.5 rounded-xl text-xs font-semibold whitespace-nowrap transition <?= $statusFilter === 'processing' ? 'bg-sky-600 text-white' : 'bg-white text-slate-600 hover:bg-slate-100 border border-slate-200' ?>">
            Đang giao hàng (<?= (int)($stats['processing_count'] ?? 0) ?>)
        </a>
        <a href="<?= base_url('orders/manage_orders.php?status=completed') ?>" 
           class="px-3.5 py-1.5 rounded-xl text-xs font-semibold whitespace-nowrap transition <?= $statusFilter === 'completed' ? 'bg-emerald-600 text-white' : 'bg-white text-slate-600 hover:bg-slate-100 border border-slate-200' ?>">
            Đã hoàn thành (<?= (int)($stats['completed_count'] ?? 0) ?>)
        </a>
        <a href="<?= base_url('orders/manage_orders.php?status=cancelled') ?>" 
           class="px-3.5 py-1.5 rounded-xl text-xs font-semibold whitespace-nowrap transition <?= $statusFilter === 'cancelled' ? 'bg-rose-600 text-white' : 'bg-white text-slate-600 hover:bg-slate-100 border border-slate-200' ?>">
            Đã hủy
        </a>
    </div>

    <!-- Bảng đơn hàng -->
    <?php if (empty($orders)): ?>
        <div class="bg-white rounded-3xl border border-slate-200/80 shadow-sm p-12 text-center my-6">
            <div class="w-16 h-16 bg-slate-100 text-slate-400 rounded-2xl flex items-center justify-center mx-auto mb-3 text-2xl">
                <i class="fa-solid fa-inbox"></i>
            </div>
            <p class="text-sm font-semibold text-slate-700">Chưa có đơn hàng nào trong danh mục này</p>
        </div>
    <?php else: ?>
        <div class="bg-white rounded-3xl border border-slate-200/80 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-slate-50 text-slate-500 font-semibold border-b border-slate-100 uppercase tracking-wider text-[11px]">
                        <tr>
                            <th class="py-4 px-5">Mã Đơn</th>
                            <th class="py-4 px-5">Khách Hàng</th>
                            <th class="py-4 px-5">Số ĐT & Địa Chỉ</th>
                            <th class="py-4 px-5">Tổng Tiền</th>
                            <th class="py-4 px-5">Thanh Toán</th>
                            <th class="py-4 px-5">Cập Nhật Trạng Thái</th>
                            <th class="py-4 px-5 text-right">Chi Tiết</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-slate-700">
                        <?php foreach ($orders as $o): 
                            $status = $statusMap[$o['status']] ?? $statusMap['pending'];
                        ?>
                            <tr class="hover:bg-slate-50/80 transition">
                                <td class="py-4 px-5">
                                    <span class="font-extrabold text-emerald-700">#<?= $o['id'] ?></span>
                                    <span class="block text-[10px] text-slate-400 mt-0.5"><?= date('d/m H:i', strtotime($o['created_at'])) ?></span>
                                </td>

                                <td class="py-4 px-5 font-semibold text-slate-900">
                                    <?= e($o['customer_name']) ?>
                                    <?php if (!empty($o['user_email'])): ?>
                                        <span class="block text-[10px] text-slate-400 font-normal"><?= e($o['user_email']) ?></span>
                                    <?php endif; ?>
                                </td>

                                <td class="py-4 px-5 max-w-xs">
                                    <div class="font-bold text-slate-800"><?= e($o['customer_phone']) ?></div>
                                    <div class="text-[11px] text-slate-500 truncate" title="<?= e($o['customer_address']) ?>">
                                        <?= e($o['customer_address']) ?>
                                    </div>
                                </td>

                                <td class="py-4 px-5">
                                    <div class="font-extrabold text-slate-900 text-sm"><?= format_money($o['total_amount']) ?></div>
                                    <div class="text-[10px] text-slate-400"><?= (int)$o['total_items'] ?> mặt hàng</div>
                                </td>

                                <td class="py-4 px-5">
                                    <?php if ($o['payment_method'] === 'bank_transfer'): ?>
                                        <span class="text-sky-600 font-semibold flex items-center gap-1"><i class="fa-solid fa-qrcode"></i> VietQR</span>
                                    <?php else: ?>
                                        <span class="text-slate-600 font-semibold flex items-center gap-1"><i class="fa-solid fa-money-bill-wave"></i> COD</span>
                                    <?php endif; ?>
                                </td>

                                <td class="py-4 px-5">
                                    <form action="<?= base_url('orders/manage_orders.php' . (isset($_GET['status']) ? '?status=' . urlencode($_GET['status']) : '')) ?>" method="POST" class="flex items-center gap-1.5">
                                        <input type="hidden" name="action" value="update_status">
                                        <input type="hidden" name="order_id" value="<?= $o['id'] ?>">
                                        
                                        <select name="status" onchange="this.form.submit()" 
                                                class="px-2.5 py-1 text-xs font-bold rounded-xl border focus:outline-none <?= $status['color'] ?> cursor-pointer">
                                            <option value="pending" <?= $o['status'] === 'pending' ? 'selected' : '' ?>>Chờ xác nhận</option>
                                            <option value="processing" <?= $o['status'] === 'processing' ? 'selected' : '' ?>>Đang giao hàng</option>
                                            <option value="completed" <?= $o['status'] === 'completed' ? 'selected' : '' ?>>Đã hoàn thành</option>
                                            <option value="cancelled" <?= $o['status'] === 'cancelled' ? 'selected' : '' ?>>Đã hủy đơn</option>
                                        </select>
                                    </form>
                                </td>

                                <td class="py-4 px-5 text-right">
                                    <a href="<?= base_url('orders/order_detail.php?id=' . $o['id']) ?>" 
                                       class="px-3 py-1.5 bg-slate-100 hover:bg-emerald-600 hover:text-white text-slate-700 rounded-lg font-semibold transition">
                                        Xem
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
