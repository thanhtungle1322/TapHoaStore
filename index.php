<?php
/**
 * Trang Chủ / Cửa Hàng (Storefront Index)
 * Hiển thị Banner, Bộ lọc Danh mục, Tìm kiếm, và Danh sách Sản phẩm Grid
 */
require_once __DIR__ . '/includes/functions.php';

$searchQuery = trim($_GET['q'] ?? '');
$categoryFilter = trim($_GET['category'] ?? '');
$sort = trim($_GET['sort'] ?? 'newest');

// Lấy danh sách tất cả các danh mục có trong hệ thống
$categories = [];
try {
    $catStmt = db()->query("SELECT DISTINCT category FROM products WHERE category IS NOT NULL AND category != '' ORDER BY category ASC");
    $categories = $catStmt->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    // Trường hợp bảng chưa sẵn sàng hoặc lỗi nhẹ
}

// Xây dựng câu truy vấn lọc sản phẩm an toàn với PDO
$sql = "
    SELECT p.*, u.name AS seller_name 
    FROM products p 
    LEFT JOIN users u ON p.user_id = u.id 
    WHERE 1=1
";
$params = [];

if (!empty($searchQuery)) {
    $sql .= " AND (p.name LIKE ? OR p.description LIKE ?)";
    $params[] = "%{$searchQuery}%";
    $params[] = "%{$searchQuery}%";
}

if (!empty($categoryFilter)) {
    $sql .= " AND p.category = ?";
    $params[] = $categoryFilter;
}

// Sắp xếp
switch ($sort) {
    case 'price_asc':
        $sql .= " ORDER BY p.price ASC";
        break;
    case 'price_desc':
        $sql .= " ORDER BY p.price DESC";
        break;
    case 'stock_desc':
        $sql .= " ORDER BY p.stock DESC";
        break;
    case 'newest':
    default:
        $sql .= " ORDER BY p.id DESC";
        break;
}

$products = [];
try {
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    $products = $stmt->fetchAll();
} catch (PDOException $e) {
    set_flash('error', 'Lỗi tải danh sách sản phẩm: ' . $e->getMessage());
}

$pageTitle = 'Trang Chủ - Mua Sắm Hàng Tiêu Dùng & Tạp Hóa';
require_once __DIR__ . '/includes/header.php';
?>

<!-- Hero Banner Section -->
<section class="relative bg-gradient-to-r from-emerald-800 via-teal-700 to-emerald-900 text-white py-12 lg:py-16 overflow-hidden">
    <div class="absolute inset-0 opacity-10 bg-[radial-gradient(#fff_1px,transparent_1px)] [background-size:16px_16px]"></div>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-center">
            
            <div class="lg:col-span-7 space-y-5 text-center lg:text-left">
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-emerald-500/20 text-emerald-200 border border-emerald-400/30 text-xs font-semibold backdrop-blur-sm">
                    <i class="fa-solid fa-sparkles text-amber-300"></i> Siêu Thị Tạp Hóa Trực Tuyến Số 1
                </span>
                <h1 class="text-3xl sm:text-4xl lg:text-5xl font-extrabold tracking-tight leading-tight">
                    Thực Phẩm & Hàng Tiêu Dùng <span class="text-amber-300">Giao Tận Cửa</span>
                </h1>
                <p class="text-slate-200 text-sm sm:text-base max-w-xl mx-auto lg:mx-0 leading-relaxed font-normal">
                    Tiết kiệm thời gian mỗi ngày với hàng trăm mặt hàng thiết yếu: Gạo ngon, gia vị chuẩn, nước ngọt, đồ khô chính hãng. Đặt online - Nhận hàng nhanh chóng!
                </p>
                <div class="flex flex-wrap items-center justify-center lg:justify-start gap-3 pt-2">
                    <a href="#productsSection" class="px-6 py-3 bg-amber-400 hover:bg-amber-300 text-slate-950 font-bold rounded-xl shadow-lg shadow-amber-400/20 text-sm transition transform hover:-translate-y-0.5">
                        <i class="fa-solid fa-bag-shopping mr-1.5"></i> Khám Phá Mua Ngay
                    </a>
                    <a href="<?= base_url('products/create.php') ?>" class="px-5 py-3 bg-white/10 hover:bg-white/20 text-white font-semibold rounded-xl border border-white/20 text-sm backdrop-blur-sm transition">
                        <i class="fa-solid fa-circle-plus mr-1.5"></i> Tôi Muốn Bán Hàng
                    </a>
                </div>
            </div>

            <div class="lg:col-span-5 hidden lg:block">
                <div class="relative mx-auto max-w-sm">
                    <div class="absolute -top-4 -left-4 w-72 h-72 bg-emerald-400/20 rounded-full filter blur-3xl"></div>
                    <div class="relative bg-white/10 border border-white/20 backdrop-blur-md p-6 rounded-3xl shadow-2xl text-center">
                        <img src="https://images.unsplash.com/photo-1542838132-92c53300491e?auto=format&fit=crop&w=600&q=80" 
                             alt="Tạp Hóa Store" 
                             class="rounded-2xl object-cover h-56 w-full shadow-md mb-4">
                        <div class="grid grid-cols-3 gap-2 text-center text-xs">
                            <div class="p-2 rounded-xl bg-white/10">
                                <p class="font-bold text-amber-300 text-base">100%</p>
                                <p class="text-[10px] text-slate-300">Chính Hãng</p>
                            </div>
                            <div class="p-2 rounded-xl bg-white/10">
                                <p class="font-bold text-amber-300 text-base">2H</p>
                                <p class="text-[10px] text-slate-300">Giao Cấp Tốc</p>
                            </div>
                            <div class="p-2 rounded-xl bg-white/10">
                                <p class="font-bold text-amber-300 text-base">0 ₫</p>
                                <p class="text-[10px] text-slate-300">Đơn Từ 300k</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</section>

<!-- Đặc điểm nổi bật (Feature Highlights) -->
<section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 -mt-6 relative z-20">
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
        <div class="bg-white p-4 rounded-2xl shadow-sm border border-slate-100 flex items-center gap-3">
            <div class="w-11 h-11 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-lg flex-shrink-0">
                <i class="fa-solid fa-truck-fast"></i>
            </div>
            <div>
                <h4 class="font-bold text-slate-800 text-xs sm:text-sm">Giao Hàng Siêu Tốc</h4>
                <p class="text-[11px] text-slate-400">Giao an toàn tận cửa</p>
            </div>
        </div>

        <div class="bg-white p-4 rounded-2xl shadow-sm border border-slate-100 flex items-center gap-3">
            <div class="w-11 h-11 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center text-lg flex-shrink-0">
                <i class="fa-solid fa-certificate"></i>
            </div>
            <div>
                <h4 class="font-bold text-slate-800 text-xs sm:text-sm">Đảm Bảo Chất Lượng</h4>
                <p class="text-[11px] text-slate-400">Date mới, rõ xuất xứ</p>
            </div>
        </div>

        <div class="bg-white p-4 rounded-2xl shadow-sm border border-slate-100 flex items-center gap-3">
            <div class="w-11 h-11 rounded-xl bg-sky-50 text-sky-600 flex items-center justify-center text-lg flex-shrink-0">
                <i class="fa-solid fa-shield-check"></i>
            </div>
            <div>
                <h4 class="font-bold text-slate-800 text-xs sm:text-sm">Thanh Toán Linh Hoạt</h4>
                <p class="text-[11px] text-slate-400">Tiền mặt COD hoặc VietQR</p>
            </div>
        </div>

        <div class="bg-white p-4 rounded-2xl shadow-sm border border-slate-100 flex items-center gap-3">
            <div class="w-11 h-11 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center text-lg flex-shrink-0">
                <i class="fa-solid fa-headset"></i>
            </div>
            <div>
                <h4 class="font-bold text-slate-800 text-xs sm:text-sm">Hỗ Trợ Tận Tâm</h4>
                <p class="text-[11px] text-slate-400">Hotline 1900 6868</p>
            </div>
        </div>
    </div>
</section>

<!-- Bộ Lọc & Danh Sách Sản Phẩm -->
<section id="productsSection" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
    
    <!-- Thanh công cụ Lọc & Tìm Kiếm -->
    <div class="bg-white p-5 rounded-2xl shadow-sm border border-slate-200/80 mb-8 space-y-4">
        
        <!-- Hàng 1: Tabs Danh mục -->
        <div class="flex items-center gap-2 overflow-x-auto pb-1 scrollbar-none">
            <a href="<?= base_url('index.php?' . http_build_query(array_merge($_GET, ['category' => '']))) ?>" 
               class="px-4 py-2 rounded-xl text-xs font-semibold whitespace-nowrap transition <?= empty($categoryFilter) ? 'bg-emerald-600 text-white shadow-sm' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' ?>">
                <i class="fa-solid fa-layer-group mr-1"></i> Tất cả sản phẩm
            </a>
            <?php foreach ($categories as $cat): ?>
                <a href="<?= base_url('index.php?' . http_build_query(array_merge($_GET, ['category' => $cat]))) ?>" 
                   class="px-4 py-2 rounded-xl text-xs font-semibold whitespace-nowrap transition <?= ($categoryFilter === $cat) ? 'bg-emerald-600 text-white shadow-sm' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' ?>">
                    <?= e($cat) ?>
                </a>
            <?php endforeach; ?>
        </div>

        <!-- Hàng 2: Bộ lọc chi tiết & Sắp xếp -->
        <div class="flex flex-col sm:flex-row justify-between items-center gap-3 pt-2 border-t border-slate-100">
            <div class="text-xs text-slate-500 font-medium w-full sm:w-auto">
                <?php if (!empty($searchQuery)): ?>
                    Kết quả tìm kiếm cho: <span class="font-bold text-emerald-700">"<?= e($searchQuery) ?>"</span> 
                    (<?= count($products) ?> sản phẩm)
                    <a href="<?= base_url('index.php') ?>" class="text-rose-500 hover:underline ml-2 text-xs">Xóa tìm kiếm</a>
                <?php else: ?>
                    Hiển thị <span class="font-bold text-slate-800"><?= count($products) ?></span> mặt hàng
                <?php endif; ?>
            </div>

            <!-- Form Sắp xếp -->
            <form action="<?= base_url('index.php') ?>" method="GET" class="flex items-center gap-2 w-full sm:w-auto justify-end">
                <?php if (!empty($searchQuery)): ?>
                    <input type="hidden" name="q" value="<?= e($searchQuery) ?>">
                <?php endif; ?>
                <?php if (!empty($categoryFilter)): ?>
                    <input type="hidden" name="category" value="<?= e($categoryFilter) ?>">
                <?php endif; ?>
                
                <label class="text-xs text-slate-500 whitespace-nowrap">Sắp xếp theo:</label>
                <select name="sort" onchange="this.form.submit()" class="bg-slate-50 border border-slate-200 text-slate-700 text-xs rounded-xl px-3 py-1.5 focus:outline-none focus:border-emerald-500 font-medium">
                    <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>Mới nhất</option>
                    <option value="price_asc" <?= $sort === 'price_asc' ? 'selected' : '' ?>>Giá tăng dần</option>
                    <option value="price_desc" <?= $sort === 'price_desc' ? 'selected' : '' ?>>Giá giảm dần</option>
                    <option value="stock_desc" <?= $sort === 'stock_desc' ? 'selected' : '' ?>>Còn nhiều hàng nhất</option>
                </select>
            </form>
        </div>

    </div>

    <!-- Danh Sách Sản Phẩm (Grid Cards) -->
    <?php if (empty($products)): ?>
        <div class="bg-white rounded-3xl border border-slate-200 p-12 text-center max-w-md mx-auto my-8">
            <div class="w-16 h-16 bg-slate-100 text-slate-400 rounded-full flex items-center justify-center mx-auto mb-4 text-2xl">
                <i class="fa-solid fa-box-open"></i>
            </div>
            <h3 class="text-lg font-bold text-slate-800 mb-1">Không tìm thấy sản phẩm nào</h3>
            <p class="text-xs text-slate-500 mb-5">Vui lòng thử tìm kiếm với từ khóa khác hoặc chọn lại danh mục hàng hóa.</p>
            <a href="<?= base_url('index.php') ?>" class="inline-flex items-center gap-1.5 px-4 py-2 bg-emerald-600 text-white rounded-xl text-xs font-semibold hover:bg-emerald-700 transition">
                <i class="fa-solid fa-rotate-left"></i> Xem Tất Cả Sản Phẩm
            </a>
        </div>
    <?php else: ?>
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
            <?php foreach ($products as $product): 
                // Xử lý ảnh sản phẩm: URL ngoài hoặc file local trong uploads/
                $imgUrl = $product['image_url'];
                if (empty($imgUrl)) {
                    $imgUrl = 'https://images.unsplash.com/photo-1542838132-92c53300491e?auto=format&fit=crop&w=500&q=80';
                } elseif (strpos($imgUrl, 'http') !== 0) {
                    $imgUrl = base_url($imgUrl);
                }
                $isOutOfStock = ((int)$product['stock'] <= 0);
            ?>
                <div class="group bg-white rounded-2xl border border-slate-200/80 hover:border-emerald-300 hover:shadow-xl transition-all duration-300 flex flex-col overflow-hidden relative">
                    
                    <!-- Khung ảnh sản phẩm -->
                    <div class="relative overflow-hidden bg-slate-100 aspect-square">
                        <a href="<?= base_url('product_detail.php?id=' . $product['id']) ?>" class="block w-full h-full">
                            <img src="<?= e($imgUrl) ?>" 
                                 alt="<?= e($product['name']) ?>" 
                                 loading="lazy"
                                 class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                        </a>

                        <!-- Badge tình trạng tồn kho -->
                        <div class="absolute top-2.5 left-2.5">
                            <?php if ($isOutOfStock): ?>
                                <span class="px-2.5 py-1 rounded-lg bg-rose-600/90 backdrop-blur-sm text-white text-[10px] font-bold uppercase tracking-wider shadow-sm">
                                    Hết hàng
                                </span>
                            <?php else: ?>
                                <span class="px-2.5 py-1 rounded-lg bg-emerald-600/90 backdrop-blur-sm text-white text-[10px] font-semibold shadow-sm">
                                    Còn <?= (int)$product['stock'] ?> sp
                                </span>
                            <?php endif; ?>
                        </div>

                        <!-- Danh mục badge -->
                        <div class="absolute bottom-2.5 left-2.5">
                            <span class="px-2 py-0.5 rounded-md bg-white/90 backdrop-blur-sm text-slate-700 text-[10px] font-medium border border-slate-200 shadow-sm">
                                <?= e($product['category']) ?>
                            </span>
                        </div>
                    </div>

                    <!-- Phần thông tin nội dung -->
                    <div class="p-4 flex-1 flex flex-col justify-between">
                        <div>
                            <div class="text-[11px] text-slate-400 mb-1 flex items-center gap-1">
                                <i class="fa-solid fa-shop text-[10px]"></i>
                                <span class="truncate"><?= e($product['seller_name'] ?? 'Tạp Hóa Store') ?></span>
                            </div>
                            
                            <h3 class="font-semibold text-slate-900 text-sm leading-snug line-clamp-2 hover:text-emerald-600 transition mb-2">
                                <a href="<?= base_url('product_detail.php?id=' . $product['id']) ?>">
                                    <?= e($product['name']) ?>
                                </a>
                            </h3>
                        </div>

                        <div class="pt-3 border-t border-slate-100 flex items-center justify-between gap-2">
                            <div>
                                <span class="text-xs text-slate-400 block -mb-0.5">Giá bán:</span>
                                <span class="text-base font-extrabold text-emerald-600">
                                    <?= format_money($product['price']) ?>
                                </span>
                            </div>

                            <!-- Nút Thêm vào giỏ hàng -->
                            <?php if ($isOutOfStock): ?>
                                <button disabled class="p-2.5 rounded-xl bg-slate-100 text-slate-400 text-xs font-semibold cursor-not-allowed">
                                    <i class="fa-solid fa-ban"></i>
                                </button>
                            <?php else: ?>
                                <form action="<?= base_url('cart.php?action=add') ?>" method="POST">
                                    <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                                    <input type="hidden" name="quantity" value="1">
                                    <button type="submit" title="Thêm vào giỏ hàng" 
                                            class="w-10 h-10 rounded-xl bg-emerald-50 hover:bg-emerald-600 text-emerald-600 hover:text-white border border-emerald-200 hover:border-transparent flex items-center justify-center transition shadow-sm">
                                        <i class="fa-solid fa-cart-plus text-sm"></i>
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>

                    </div>

                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
