<?php
/**
 * Quản Lý Sản Phẩm Đã Đăng (Sản phẩm của tôi / Toàn bộ sản phẩm nếu là Admin)
 */
require_once __DIR__ . '/../includes/functions.php';

require_login();

$currentUser = current_user();
$isAdmin = is_admin();

try {
    if ($isAdmin) {
        $stmt = db()->prepare("
            SELECT p.*, u.name as seller_name 
            FROM products p 
            LEFT JOIN users u ON p.user_id = u.id 
            ORDER BY p.id DESC
        ");
        $stmt->execute();
    } else {
        $stmt = db()->prepare("
            SELECT p.*, u.name as seller_name 
            FROM products p 
            LEFT JOIN users u ON p.user_id = u.id 
            WHERE p.user_id = ? 
            ORDER BY p.id DESC
        ");
        $stmt->execute([$currentUser['id']]);
    }
    $products = $stmt->fetchAll();
} catch (PDOException $e) {
    set_flash('error', 'Lỗi truy vấn sản phẩm: ' . $e->getMessage());
    $products = [];
}

$pageTitle = 'Quản Lý Sản Phẩm';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
    
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-8">
        <div>
            <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight flex items-center gap-3">
                <i class="fa-solid fa-boxes-stacked text-emerald-600"></i> Quản Lý Danh Sách Sản Phẩm
            </h1>
            <p class="text-xs text-slate-500 mt-1">
                <?= $isAdmin ? 'Toàn bộ mặt hàng đang được đăng bán trên hệ thống' : 'Các mặt hàng do chính bạn đăng bán' ?>
            </p>
        </div>
        <a href="<?= base_url('products/create.php') ?>" class="px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-xl text-xs shadow-md shadow-emerald-600/20 transition flex items-center gap-2">
            <i class="fa-solid fa-circle-plus"></i> Đăng Bán Sản Phẩm Mới
        </a>
    </div>

    <?php if (empty($products)): ?>
        <div class="bg-white rounded-3xl border border-slate-200/80 shadow-sm p-12 text-center max-w-md mx-auto my-6">
            <div class="w-16 h-16 bg-slate-100 text-slate-400 rounded-3xl flex items-center justify-center mx-auto mb-4 text-2xl">
                <i class="fa-solid fa-box-open"></i>
            </div>
            <h3 class="text-lg font-bold text-slate-800 mb-1">Bạn chưa đăng bán sản phẩm nào</h3>
            <p class="text-xs text-slate-500 mb-6">Hãy bắt đầu tạo sản phẩm đầu tiên để tiếp cận người mua ngay bây giờ!</p>
            <a href="<?= base_url('products/create.php') ?>" class="inline-flex items-center gap-2 px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-semibold shadow-md shadow-emerald-600/20 transition">
                <i class="fa-solid fa-circle-plus"></i> Đăng Sản Phẩm Ngay
            </a>
        </div>
    <?php else: ?>
        <div class="bg-white rounded-3xl border border-slate-200/80 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-slate-50 text-slate-500 font-semibold border-b border-slate-100 uppercase tracking-wider text-[11px]">
                        <tr>
                            <th class="py-4 px-6">Sản Phẩm</th>
                            <th class="py-4 px-6">Danh Mục</th>
                            <th class="py-4 px-6">Giá Bán</th>
                            <th class="py-4 px-6">Tồn Kho</th>
                            <?php if ($isAdmin): ?>
                                <th class="py-4 px-6">Người Đăng</th>
                            <?php endif; ?>
                            <th class="py-4 px-6">Ngày Tạo</th>
                            <th class="py-4 px-6 text-right">Thao Tác</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-slate-700">
                        <?php foreach ($products as $p): 
                            $img = $p['image_url'];
                            if (empty($img)) {
                                $img = 'https://images.unsplash.com/photo-1542838132-92c53300491e?auto=format&fit=crop&w=200&q=80';
                            } elseif (strpos($img, 'http') !== 0) {
                                $img = base_url($img);
                            }
                            $stock = (int)$p['stock'];
                        ?>
                            <tr class="hover:bg-slate-50/80 transition">
                                <td class="py-4 px-6">
                                    <div class="flex items-center gap-3">
                                        <div class="w-12 h-12 rounded-xl bg-slate-100 overflow-hidden border border-slate-200 flex-shrink-0">
                                            <img src="<?= e($img) ?>" alt="<?= e($p['name']) ?>" class="w-full h-full object-cover">
                                        </div>
                                        <div class="max-w-xs">
                                            <a href="<?= base_url('product_detail.php?id=' . $p['id']) ?>" class="font-bold text-slate-900 hover:text-emerald-600 transition block truncate">
                                                <?= e($p['name']) ?>
                                            </a>
                                            <span class="text-[10px] text-slate-400 font-mono">ID: #<?= $p['id'] ?></span>
                                        </div>
                                    </div>
                                </td>

                                <td class="py-4 px-6">
                                    <span class="px-2.5 py-1 rounded-lg bg-slate-100 text-slate-700 font-medium">
                                        <?= e($p['category']) ?>
                                    </span>
                                </td>

                                <td class="py-4 px-6 font-extrabold text-emerald-600 text-sm">
                                    <?= format_money($p['price']) ?>
                                </td>

                                <td class="py-4 px-6">
                                    <?php if ($stock <= 0): ?>
                                        <span class="px-2 py-0.5 rounded-md bg-rose-100 text-rose-700 font-bold">Hết hàng</span>
                                    <?php elseif ($stock < 10): ?>
                                        <span class="px-2 py-0.5 rounded-md bg-amber-100 text-amber-700 font-bold">Còn <?= $stock ?> sp (Sắp hết)</span>
                                    <?php else: ?>
                                        <span class="px-2 py-0.5 rounded-md bg-emerald-100 text-emerald-700 font-semibold">Còn <?= $stock ?> sp</span>
                                    <?php endif; ?>
                                </td>

                                <?php if ($isAdmin): ?>
                                    <td class="py-4 px-6 text-slate-600 font-medium">
                                        <?= e($p['seller_name'] ?? 'Tạp Hóa Store') ?>
                                    </td>
                                <?php endif; ?>

                                <td class="py-4 px-6 text-slate-400">
                                    <?= date('d/m/Y', strtotime($p['created_at'])) ?>
                                </td>

                                <td class="py-4 px-6 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <a href="<?= base_url('products/edit.php?id=' . $p['id']) ?>" 
                                           class="p-2 text-slate-500 hover:text-emerald-600 hover:bg-emerald-50 rounded-lg transition" title="Chỉnh sửa">
                                            <i class="fa-solid fa-pen-to-square text-sm"></i>
                                        </a>
                                        <a href="<?= base_url('products/delete.php?id=' . $p['id']) ?>" 
                                           onclick="return confirm('Bạn có chắc chắn muốn xóa sản phẩm \"<?= addslashes(e($p['name'])) ?>\"?')"
                                           class="p-2 text-slate-500 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition" title="Xóa sản phẩm">
                                            <i class="fa-solid fa-trash-can text-sm"></i>
                                        </a>
                                    </div>
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
