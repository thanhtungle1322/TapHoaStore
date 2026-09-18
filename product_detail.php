<?php
/**
 * Trang Chi Tiết Sản Phẩm
 */
require_once __DIR__ . '/includes/functions.php';

$productId = (int)($_GET['id'] ?? 0);

if ($productId <= 0) {
    set_flash('error', 'Sản phẩm không hợp lệ.');
    redirect(base_url('index.php'));
}

try {
    $stmt = db()->prepare("
        SELECT p.*, u.name AS seller_name, u.phone AS seller_phone 
        FROM products p 
        LEFT JOIN users u ON p.user_id = u.id 
        WHERE p.id = ? 
        LIMIT 1
    ");
    $stmt->execute([$productId]);
    $product = $stmt->fetch();

    if (!$product) {
        set_flash('error', 'Không tìm thấy sản phẩm hoặc sản phẩm đã ngừng kinh doanh.');
        redirect(base_url('index.php'));
    }

    // Lấy thêm các sản phẩm cùng danh mục liên quan
    $relStmt = db()->prepare("
        SELECT * FROM products 
        WHERE category = ? AND id != ? 
        ORDER BY id DESC 
        LIMIT 4
    ");
    $relStmt->execute([$product['category'], $product['id']]);
    $relatedProducts = $relStmt->fetchAll();

} catch (PDOException $e) {
    set_flash('error', 'Lỗi truy vấn sản phẩm: ' . $e->getMessage());
    redirect(base_url('index.php'));
}

$imgUrl = $product['image_url'];
if (empty($imgUrl)) {
    $imgUrl = 'https://images.unsplash.com/photo-1542838132-92c53300491e?auto=format&fit=crop&w=600&q=80';
} elseif (strpos($imgUrl, 'http') !== 0) {
    $imgUrl = base_url($imgUrl);
}

$isOutOfStock = ((int)$product['stock'] <= 0);

$pageTitle = $product['name'];
require_once __DIR__ . '/includes/header.php';
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    
    <!-- Breadcrumb Điều hướng -->
    <nav class="flex items-center gap-2 text-xs text-slate-500 mb-6 flex-wrap">
        <a href="<?= base_url('index.php') ?>" class="hover:text-emerald-600 transition flex items-center gap-1">
            <i class="fa-solid fa-house text-slate-400"></i> Trang chủ
        </a>
        <i class="fa-solid fa-chevron-right text-[10px] text-slate-300"></i>
        <a href="<?= base_url('index.php?category=' . urlencode($product['category'])) ?>" class="hover:text-emerald-600 transition">
            <?= e($product['category']) ?>
        </a>
        <i class="fa-solid fa-chevron-right text-[10px] text-slate-300"></i>
        <span class="text-slate-800 font-semibold truncate max-w-xs"><?= e($product['name']) ?></span>
    </nav>

    <!-- Khối Chi Tiết Sản Phẩm -->
    <div class="bg-white rounded-3xl border border-slate-200/80 shadow-sm p-6 sm:p-8 lg:p-10 mb-12">
        <div class="grid grid-cols-1 md:grid-cols-12 gap-8 lg:gap-12">
            
            <!-- Cột ảnh sản phẩm bên trái -->
            <div class="md:col-span-5 lg:col-span-5">
                <div class="sticky top-24">
                    <div class="relative aspect-square rounded-2xl overflow-hidden bg-slate-100 border border-slate-200 shadow-inner">
                        <img src="<?= e($imgUrl) ?>" alt="<?= e($product['name']) ?>" class="w-full h-full object-cover">
                        
                        <?php if ($isOutOfStock): ?>
                            <div class="absolute inset-0 bg-slate-900/40 backdrop-blur-[2px] flex items-center justify-center">
                                <span class="px-4 py-2 bg-rose-600 text-white font-bold rounded-xl text-sm uppercase tracking-wider shadow-lg">
                                    Tạm Hết Hàng
                                </span>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Cột thông tin sản phẩm bên phải -->
            <div class="md:col-span-7 lg:col-span-7 flex flex-col justify-between">
                <div>
                    <!-- Danh mục & Tình trạng kho -->
                    <div class="flex items-center gap-2 mb-3">
                        <span class="px-2.5 py-1 bg-emerald-50 text-emerald-700 font-semibold rounded-lg text-xs border border-emerald-200">
                            <?= e($product['category']) ?>
                        </span>
                        <?php if ($isOutOfStock): ?>
                            <span class="px-2.5 py-1 bg-rose-50 text-rose-600 font-medium rounded-lg text-xs border border-rose-200">
                                Hết hàng
                            </span>
                        <?php else: ?>
                            <span class="px-2.5 py-1 bg-sky-50 text-sky-700 font-medium rounded-lg text-xs border border-sky-200">
                                Còn <?= (int)$product['stock'] ?> sản phẩm trong kho
                            </span>
                        <?php endif; ?>
                    </div>

                    <!-- Tên sản phẩm -->
                    <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 leading-tight mb-4">
                        <?= e($product['name']) ?>
                    </h1>

                    <!-- Giá bán -->
                    <div class="bg-slate-50 border border-slate-100 rounded-2xl p-4 mb-6">
                        <div class="text-xs text-slate-400 mb-1">Giá bán niêm yết:</div>
                        <div class="text-3xl font-extrabold text-emerald-600">
                            <?= format_money($product['price']) ?>
                            <span class="text-xs font-normal text-slate-400 ml-1">/ cái (hoặc túi/chai)</span>
                        </div>
                    </div>

                    <!-- Thông tin người bán -->
                    <div class="flex items-center gap-3 p-3.5 bg-slate-50 rounded-2xl border border-slate-100 mb-6 text-xs text-slate-600">
                        <div class="w-10 h-10 rounded-xl bg-emerald-600 text-white font-bold flex items-center justify-center text-sm shadow-sm">
                            <i class="fa-solid fa-shop"></i>
                        </div>
                        <div>
                            <p class="font-bold text-slate-800"><?= e($product['seller_name'] ?? 'Tạp Hóa Store') ?></p>
                            <p class="text-slate-400">Người bán uy tín • Cam kết giao hàng chính hãng</p>
                        </div>
                    </div>

                    <!-- Form Thêm vào giỏ & Mua ngay -->
                    <form action="<?= base_url('cart.php?action=add') ?>" method="POST" class="space-y-6">
                        <input type="hidden" name="product_id" value="<?= $product['id'] ?>">

                        <!-- Chọn số lượng -->
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-2">Chọn số lượng:</label>
                            <div class="flex items-center gap-3">
                                <div class="flex items-center border border-slate-300 rounded-xl overflow-hidden bg-white">
                                    <button type="button" onclick="decrementQty()" class="px-3.5 py-2 text-slate-600 hover:bg-slate-100 transition focus:outline-none">
                                        <i class="fa-solid fa-minus text-xs"></i>
                                    </button>
                                    <input type="number" id="qtyInput" name="quantity" value="1" min="1" max="<?= max(1, (int)$product['stock']) ?>" 
                                           class="w-14 text-center text-sm font-semibold border-x border-slate-200 py-2 focus:outline-none" readonly>
                                    <button type="button" onclick="incrementQty(<?= (int)$product['stock'] ?>)" class="px-3.5 py-2 text-slate-600 hover:bg-slate-100 transition focus:outline-none">
                                        <i class="fa-solid fa-plus text-xs"></i>
                                    </button>
                                </div>
                                <span class="text-xs text-slate-400">(Tối đa <?= (int)$product['stock'] ?>)</span>
                            </div>
                        </div>

                        <!-- Các nút bấm hành động -->
                        <div class="flex flex-col sm:flex-row items-center gap-3 pt-2">
                            <?php if ($isOutOfStock): ?>
                                <button type="button" disabled class="w-full py-3.5 px-6 bg-slate-200 text-slate-400 font-bold rounded-xl cursor-not-allowed text-center">
                                    <i class="fa-solid fa-ban mr-2"></i> Sản Phẩm Tạm Thời Hết Hàng
                                </button>
                            <?php else: ?>
                                <button type="submit" name="buy_now" value="0" 
                                        class="w-full sm:w-1/2 py-3.5 px-6 bg-emerald-50 hover:bg-emerald-100 text-emerald-700 border border-emerald-300 font-bold rounded-xl transition flex items-center justify-center gap-2 shadow-sm">
                                    <i class="fa-solid fa-cart-plus text-base"></i> Thêm Vào Giỏ
                                </button>
                                <button type="submit" name="buy_now" value="1" 
                                        class="w-full sm:w-1/2 py-3.5 px-6 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-xl shadow-lg shadow-emerald-600/20 transition flex items-center justify-center gap-2">
                                    <i class="fa-solid fa-bolt text-base"></i> Mua Ngay
                                </button>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>

                <!-- Chính sách hỗ trợ -->
                <div class="grid grid-cols-2 gap-3 pt-8 mt-8 border-t border-slate-100 text-xs text-slate-500">
                    <div class="flex items-center gap-2">
                        <i class="fa-solid fa-rotate-left text-emerald-600"></i>
                        <span>Đổi trả trong 7 ngày nếu lỗi</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <i class="fa-solid fa-shield-check text-emerald-600"></i>
                        <span>Kiểm tra hàng trước khi nhận</span>
                    </div>
                </div>

            </div>

        </div>
    </div>

    <!-- Mô Tả Chi Tiết Sản Phẩm -->
    <div class="bg-white rounded-3xl border border-slate-200/80 shadow-sm p-6 sm:p-8 mb-12">
        <h2 class="text-lg font-bold text-slate-800 mb-4 pb-3 border-b border-slate-100 flex items-center gap-2">
            <i class="fa-solid fa-align-left text-emerald-600"></i> Mô Tả Chi Tiết Sản Phẩm
        </h2>
        <div class="prose prose-slate max-w-none text-slate-600 text-sm leading-relaxed whitespace-pre-line">
            <?= nl2br(e($product['description'] ?? 'Chưa có mô tả chi tiết cho sản phẩm này.')) ?>
        </div>
    </div>

    <!-- Sản Phẩm Cùng Danh Mục Liên Quan -->
    <?php if (!empty($relatedProducts)): ?>
        <div class="mb-12">
            <h2 class="text-xl font-bold text-slate-800 mb-6 flex items-center gap-2">
                <i class="fa-solid fa-boxes-packing text-emerald-600"></i> Sản Phẩm Cùng Danh Mục
            </h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-6">
                <?php foreach ($relatedProducts as $rel): 
                    $relImg = $rel['image_url'];
                    if (empty($relImg)) {
                        $relImg = 'https://images.unsplash.com/photo-1542838132-92c53300491e?auto=format&fit=crop&w=500&q=80';
                    } elseif (strpos($relImg, 'http') !== 0) {
                        $relImg = base_url($relImg);
                    }
                ?>
                    <div class="bg-white rounded-2xl border border-slate-200/80 hover:shadow-lg transition p-3 flex flex-col justify-between">
                        <a href="<?= base_url('product_detail.php?id=' . $rel['id']) ?>" class="block aspect-square rounded-xl overflow-hidden bg-slate-100 mb-3">
                            <img src="<?= e($relImg) ?>" alt="<?= e($rel['name']) ?>" class="w-full h-full object-cover hover:scale-105 transition duration-300">
                        </a>
                        <div>
                            <h4 class="font-semibold text-slate-800 text-xs line-clamp-2 mb-2 hover:text-emerald-600 transition">
                                <a href="<?= base_url('product_detail.php?id=' . $rel['id']) ?>"><?= e($rel['name']) ?></a>
                            </h4>
                            <div class="text-emerald-600 font-bold text-sm">
                                <?= format_money($rel['price']) ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

</div>

<script>
    function decrementQty() {
        const input = document.getElementById('qtyInput');
        let current = parseInt(input.value) || 1;
        if (current > 1) {
            input.value = current - 1;
        }
    }

    function incrementQty(maxStock) {
        const input = document.getElementById('qtyInput');
        let current = parseInt(input.value) || 1;
        if (current < maxStock) {
            input.value = current + 1;
        }
    }
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
