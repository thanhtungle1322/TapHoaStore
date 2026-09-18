<?php
/**
 * Quản lý Giỏ Hàng (Cart)
 * Thêm, Cập nhật, Xóa món hàng trong Session
 */
require_once __DIR__ . '/includes/functions.php';

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

$action = $_GET['action'] ?? '';

// 1. Thêm sản phẩm vào giỏ hàng
if ($action === 'add' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $productId = (int)($_POST['product_id'] ?? 0);
    $quantity = max(1, (int)($_POST['quantity'] ?? 1));
    $buyNow = (int)($_POST['buy_now'] ?? 0);

    if ($productId > 0) {
        try {
            $stmt = db()->prepare("SELECT id, name, price, stock, image_url FROM products WHERE id = ? LIMIT 1");
            $stmt->execute([$productId]);
            $product = $stmt->fetch();

            if ($product) {
                if ($product['stock'] <= 0) {
                    set_flash('error', 'Sản phẩm này hiện đã hết hàng.');
                } else {
                    $currentQty = isset($_SESSION['cart'][$productId]) ? $_SESSION['cart'][$productId]['quantity'] : 0;
                    $newQty = $currentQty + $quantity;

                    if ($newQty > $product['stock']) {
                        $newQty = $product['stock'];
                        set_flash('warning', 'Số lượng đã được điều chỉnh về mức tối đa còn trong kho (' . $product['stock'] . ' sản phẩm).');
                    } else {
                        set_flash('success', 'Đã thêm "' . $product['name'] . '" vào giỏ hàng.');
                    }

                    $_SESSION['cart'][$productId] = [
                        'id'        => $product['id'],
                        'name'      => $product['name'],
                        'price'     => (float)$product['price'],
                        'stock'     => (int)$product['stock'],
                        'image_url' => $product['image_url'],
                        'quantity'  => $newQty
                    ];

                    if ($buyNow === 1) {
                        redirect(base_url('orders/checkout.php'));
                    }
                }
            } else {
                set_flash('error', 'Sản phẩm không tồn tại.');
            }
        } catch (PDOException $e) {
            set_flash('error', 'Lỗi kiểm tra sản phẩm: ' . $e->getMessage());
        }
    }

    redirect(base_url('cart.php'));
}

// 2. Cập nhật số lượng
if ($action === 'update' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $quantities = $_POST['quantities'] ?? [];
    foreach ($quantities as $pId => $qty) {
        $pId = (int)$pId;
        $qty = (int)$qty;
        if (isset($_SESSION['cart'][$pId])) {
            if ($qty <= 0) {
                unset($_SESSION['cart'][$pId]);
            } else {
                // Kiểm tra giới hạn kho
                $maxStock = $_SESSION['cart'][$pId]['stock'];
                $_SESSION['cart'][$pId]['quantity'] = min($qty, $maxStock);
            }
        }
    }
    set_flash('success', 'Đã cập nhật giỏ hàng.');
    redirect(base_url('cart.php'));
}

// 3. Xóa một sản phẩm khỏi giỏ
if ($action === 'remove') {
    $pId = (int)($_GET['id'] ?? 0);
    if (isset($_SESSION['cart'][$pId])) {
        $name = $_SESSION['cart'][$pId]['name'];
        unset($_SESSION['cart'][$pId]);
        set_flash('info', 'Đã xóa "' . $name . '" khỏi giỏ hàng.');
    }
    redirect(base_url('cart.php'));
}

// 4. Xóa trắng giỏ hàng
if ($action === 'clear') {
    $_SESSION['cart'] = [];
    set_flash('info', 'Đã dọn sạch giỏ hàng.');
    redirect(base_url('cart.php'));
}

$cartItems = $_SESSION['cart'];
$subtotal = cart_total();
$shippingFee = ($subtotal >= 300000 || $subtotal == 0) ? 0 : 30000;
$grandTotal = $subtotal + $shippingFee;

$pageTitle = 'Giỏ Hàng Của Bạn';
require_once __DIR__ . '/includes/header.php';
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
    
    <div class="flex items-center justify-between mb-8">
        <div>
            <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight flex items-center gap-3">
                <i class="fa-solid fa-cart-shopping text-emerald-600"></i> Giỏ Hàng Mua Sắm
            </h1>
            <p class="text-xs text-slate-500 mt-1">Xem lại các sản phẩm bạn đã chọn trước khi thanh toán</p>
        </div>
        <?php if (!empty($cartItems)): ?>
            <a href="<?= base_url('cart.php?action=clear') ?>" 
               onclick="return confirm('Bạn có chắc chắn muốn xóa toàn bộ sản phẩm trong giỏ hàng?')"
               class="text-xs text-rose-600 hover:text-rose-700 font-semibold flex items-center gap-1.5 p-2 rounded-lg hover:bg-rose-50 transition">
                <i class="fa-regular fa-trash-can"></i> Xóa tất cả
            </a>
        <?php endif; ?>
    </div>

    <?php if (empty($cartItems)): ?>
        <!-- Giỏ Hàng Trống -->
        <div class="bg-white rounded-3xl border border-slate-200/80 shadow-sm p-12 text-center max-w-lg mx-auto my-6">
            <div class="w-20 h-20 bg-emerald-50 text-emerald-500 rounded-3xl flex items-center justify-center mx-auto mb-5 text-3xl shadow-inner">
                <i class="fa-solid fa-cart-arrow-down"></i>
            </div>
            <h2 class="text-xl font-bold text-slate-800 mb-2">Giỏ hàng của bạn đang trống!</h2>
            <p class="text-slate-500 text-xs mb-6 leading-relaxed">
                Hãy dạo quanh các quầy thực phẩm, bánh kẹo và gia vị của chúng tôi để chọn những món đồ bạn yêu thích nhé!
            </p>
            <a href="<?= base_url('index.php') ?>" class="inline-flex items-center gap-2 px-6 py-3 bg-emerald-600 hover:bg-emerald-700 text-white font-semibold rounded-xl text-sm shadow-md shadow-emerald-600/20 transition">
                <i class="fa-solid fa-basket-shopping"></i> Khám Phá Mua Sắm Ngay
            </a>
        </div>
    <?php else: ?>
        <!-- Danh Sách Giỏ Hàng & Tóm Tắt Đơn -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
            
            <!-- Cột danh sách sản phẩm (Bên trái) -->
            <div class="lg:col-span-8 bg-white rounded-3xl border border-slate-200/80 shadow-sm p-6 overflow-hidden">
                <form action="<?= base_url('cart.php?action=update') ?>" method="POST" id="cartForm">
                    <div class="divide-y divide-slate-100">
                        <?php foreach ($cartItems as $pId => $item): 
                            $img = $item['image_url'];
                            if (empty($img)) {
                                $img = 'https://images.unsplash.com/photo-1542838132-92c53300491e?auto=format&fit=crop&w=300&q=80';
                            } elseif (strpos($img, 'http') !== 0) {
                                $img = base_url($img);
                            }
                            $itemSubtotal = $item['price'] * $item['quantity'];
                        ?>
                            <div class="py-5 flex flex-col sm:flex-row items-center gap-4">
                                <!-- Ảnh món hàng -->
                                <a href="<?= base_url('product_detail.php?id=' . $pId) ?>" class="w-20 h-20 rounded-xl bg-slate-100 overflow-hidden flex-shrink-0 border border-slate-200">
                                    <img src="<?= e($img) ?>" alt="<?= e($item['name']) ?>" class="w-full h-full object-cover">
                                </a>

                                <!-- Tên & Giá đơn vị -->
                                <div class="flex-1 text-center sm:text-left">
                                    <h3 class="font-semibold text-slate-800 text-sm hover:text-emerald-600 transition">
                                        <a href="<?= base_url('product_detail.php?id=' . $pId) ?>"><?= e($item['name']) ?></a>
                                    </h3>
                                    <div class="text-xs text-slate-400 mt-1">
                                        Đơn giá: <span class="font-semibold text-slate-700"><?= format_money($item['price']) ?></span>
                                    </div>
                                </div>

                                <!-- Bộ tăng giảm số lượng -->
                                <div class="flex items-center border border-slate-200 rounded-xl overflow-hidden bg-slate-50">
                                    <button type="button" onclick="updateItemQty(<?= $pId ?>, -1, <?= $item['stock'] ?>)" class="px-2.5 py-1.5 text-slate-600 hover:bg-slate-200 transition">
                                        <i class="fa-solid fa-minus text-[10px]"></i>
                                    </button>
                                    <input type="number" id="qty_<?= $pId ?>" name="quantities[<?= $pId ?>]" value="<?= (int)$item['quantity'] ?>" min="1" max="<?= (int)$item['stock'] ?>" 
                                           class="w-12 text-center text-xs font-bold bg-transparent py-1.5 focus:outline-none" readonly>
                                    <button type="button" onclick="updateItemQty(<?= $pId ?>, 1, <?= $item['stock'] ?>)" class="px-2.5 py-1.5 text-slate-600 hover:bg-slate-200 transition">
                                        <i class="fa-solid fa-plus text-[10px]"></i>
                                    </button>
                                </div>

                                <!-- Tổng tiền mặt hàng -->
                                <div class="text-right min-w-[100px]">
                                    <span class="text-sm font-extrabold text-emerald-600 block">
                                        <?= format_money($itemSubtotal) ?>
                                    </span>
                                </div>

                                <!-- Nút xóa -->
                                <div>
                                    <a href="<?= base_url('cart.php?action=remove&id=' . $pId) ?>" 
                                       title="Xóa khỏi giỏ" 
                                       class="p-2 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition">
                                        <i class="fa-solid fa-trash-can text-sm"></i>
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="pt-5 border-t border-slate-100 flex flex-wrap items-center justify-between gap-3">
                        <a href="<?= base_url('index.php') ?>" class="text-xs font-semibold text-emerald-600 hover:text-emerald-700 flex items-center gap-1.5">
                            <i class="fa-solid fa-arrow-left"></i> Tiếp tục mua thêm sản phẩm
                        </a>
                        <button type="submit" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-semibold transition">
                            <i class="fa-solid fa-rotate mr-1"></i> Cập Nhật Giỏ Hàng
                        </button>
                    </div>
                </form>
            </div>

            <!-- Cột tóm tắt & thanh toán (Bên phải) -->
            <div class="lg:col-span-4 bg-white rounded-3xl border border-slate-200/80 shadow-sm p-6 sticky top-24">
                <h3 class="font-bold text-slate-800 text-base mb-4 pb-3 border-b border-slate-100 flex items-center gap-2">
                    <i class="fa-solid fa-receipt text-emerald-600"></i> Tóm Tắt Đơn Hàng
                </h3>

                <div class="space-y-3 text-xs text-slate-600 mb-6">
                    <div class="flex justify-between items-center">
                        <span>Tạm tính hàng hóa:</span>
                        <span class="font-semibold text-slate-800"><?= format_money($subtotal) ?></span>
                    </div>

                    <div class="flex justify-between items-center">
                        <span class="flex items-center gap-1">
                            Phí giao hàng:
                            <?php if ($shippingFee === 0): ?>
                                <span class="bg-emerald-100 text-emerald-800 text-[10px] font-bold px-1.5 py-0.5 rounded">Freeship</span>
                            <?php endif; ?>
                        </span>
                        <span class="font-semibold text-slate-800"><?= $shippingFee === 0 ? '0 ₫' : format_money($shippingFee) ?></span>
                    </div>

                    <?php if ($subtotal < 300000): ?>
                        <div class="p-2.5 bg-amber-50 rounded-xl border border-amber-200 text-[11px] text-amber-800 flex items-center gap-2">
                            <i class="fa-solid fa-circle-info text-amber-500"></i>
                            <span>Mua thêm <strong><?= format_money(300000 - $subtotal) ?></strong> để nhận <strong>Miễn phí vận chuyển</strong>!</span>
                        </div>
                    <?php endif; ?>

                    <div class="pt-3 border-t border-slate-100 flex justify-between items-center text-sm">
                        <span class="font-bold text-slate-800">Tổng thanh toán:</span>
                        <span class="text-xl font-extrabold text-emerald-600"><?= format_money($grandTotal) ?></span>
                    </div>
                </div>

                <a href="<?= base_url('orders/checkout.php') ?>" 
                   class="w-full py-3.5 px-4 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-xl shadow-lg shadow-emerald-600/20 text-center block transition duration-150 flex items-center justify-center gap-2 text-sm">
                    <span>Tiến Hành Đặt Hàng</span>
                    <i class="fa-solid fa-arrow-right"></i>
                </a>

                <div class="mt-4 pt-4 border-t border-slate-100 text-[11px] text-slate-400 space-y-1.5">
                    <p><i class="fa-solid fa-shield-halved text-emerald-500 mr-1"></i> Kiểm tra hàng thoải mái trước khi thanh toán</p>
                    <p><i class="fa-solid fa-clock text-emerald-500 mr-1"></i> Hỗ trợ đặt hàng nhanh 24/7</p>
                </div>
            </div>

        </div>
    <?php endif; ?>

</div>

<script>
    function updateItemQty(productId, delta, maxStock) {
        const input = document.getElementById('qty_' + productId);
        if (!input) return;
        let current = parseInt(input.value) || 1;
        let nextVal = current + delta;
        if (nextVal >= 1 && nextVal <= maxStock) {
            input.value = nextVal;
            document.getElementById('cartForm').submit();
        }
    }
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
