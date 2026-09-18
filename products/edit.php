<?php
/**
 * Trang Chỉnh Sửa Sản Phẩm
 */
require_once __DIR__ . '/../includes/functions.php';

require_login();

$productId = (int)($_GET['id'] ?? 0);

if ($productId <= 0) {
    set_flash('error', 'Sản phẩm không hợp lệ.');
    redirect(base_url('products/manage.php'));
}

try {
    $stmt = db()->prepare("SELECT * FROM products WHERE id = ? LIMIT 1");
    $stmt->execute([$productId]);
    $product = $stmt->fetch();

    if (!$product) {
        set_flash('error', 'Không tìm thấy sản phẩm cần sửa.');
        redirect(base_url('products/manage.php'));
    }

    // Kiểm tra quyền sở hữu
    if (!is_admin() && (int)$product['user_id'] !== (int)current_user()['id']) {
        set_flash('error', 'Bạn không có quyền chỉnh sửa sản phẩm này.');
        redirect(base_url('products/manage.php'));
    }
} catch (PDOException $e) {
    set_flash('error', 'Lỗi truy vấn: ' . $e->getMessage());
    redirect(base_url('products/manage.php'));
}

$errors = [];
$name = $product['name'];
$category = $product['category'];
$price = $product['price'];
$stock = $product['stock'];
$description = $product['description'];
$imageUrl = $product['image_url'];

$categoriesList = [
    'Lương thực - Thực phẩm',
    'Gia vị & Dầu ăn',
    'Nước giải khát & Cà phê',
    'Sữa & Bánh kẹo',
    'Hóa mỹ phẩm & Đồ gia dụng',
    'Khác'
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $category = trim($_POST['category'] ?? 'Khác');
    $price = (float)($_POST['price'] ?? 0);
    $stock = (int)($_POST['stock'] ?? 0);
    $description = trim($_POST['description'] ?? '');
    $inputImageUrl = trim($_POST['image_url'] ?? '');

    if (empty($name)) {
        $errors[] = 'Tên sản phẩm không được để trống.';
    }
    if ($price <= 0) {
        $errors[] = 'Giá bán phải lớn hơn 0 ₫.';
    }
    if ($stock < 0) {
        $errors[] = 'Số lượng kho không thể là số âm.';
    }

    // Giữ nguyên ảnh cũ mặc định
    $finalImagePath = $imageUrl;

    // Nếu người dùng nhập URL ảnh mới
    if (!empty($inputImageUrl) && $inputImageUrl !== $imageUrl) {
        $finalImagePath = $inputImageUrl;
    }

    // Nếu người dùng upload ảnh file mới
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $uploaded = upload_product_image($_FILES['image']);
        if ($uploaded) {
            $finalImagePath = $uploaded;
        } else {
            $errors[] = 'Không thể upload ảnh mới. Vui lòng kiểm tra dung lượng và định dạng.';
        }
    }

    if (empty($errors)) {
        try {
            $slug = slugify($name) . '-' . $productId;
            $updateStmt = db()->prepare("
                UPDATE products 
                SET name = ?, slug = ?, category = ?, description = ?, price = ?, stock = ?, image_url = ? 
                WHERE id = ?
            ");
            $updateStmt->execute([
                $name,
                $slug,
                $category,
                $description,
                $price,
                $stock,
                $finalImagePath,
                $productId
            ]);

            set_flash('success', 'Đã cập nhật thông tin sản phẩm thành công!');
            redirect(base_url('products/manage.php'));
        } catch (PDOException $e) {
            $errors[] = 'Lỗi cập nhật CSDL: ' . $e->getMessage();
        }
    }
}

$pageTitle = 'Chỉnh Sửa Sản Phẩm: ' . $product['name'];
require_once __DIR__ . '/../includes/header.php';
?>

<div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
    
    <div class="mb-8 flex items-center justify-between">
        <div>
            <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight flex items-center gap-3">
                <i class="fa-solid fa-pen-to-square text-emerald-600"></i> Chỉnh Sửa Sản Phẩm #<?= $productId ?>
            </h1>
            <p class="text-xs text-slate-500 mt-1">Cập nhật thông tin giá, số lượng tồn kho và hình ảnh sản phẩm</p>
        </div>
        <a href="<?= base_url('products/manage.php') ?>" class="text-xs text-slate-500 hover:text-slate-800 font-semibold flex items-center gap-1">
            <i class="fa-solid fa-arrow-left"></i> Quay lại
        </a>
    </div>

    <?php if (!empty($errors)): ?>
        <div class="mb-6 p-4 rounded-2xl bg-rose-50 border border-rose-200 text-xs text-rose-700 space-y-1">
            <p class="font-bold flex items-center gap-1.5"><i class="fa-solid fa-circle-exclamation"></i> Có lỗi xảy ra:</p>
            <ul class="list-disc list-inside space-y-0.5">
                <?php foreach ($errors as $err): ?>
                    <li><?= e($err) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form action="<?= base_url('products/edit.php?id=' . $productId) ?>" method="POST" enctype="multipart/form-data" class="bg-white rounded-3xl border border-slate-200/80 shadow-sm p-6 sm:p-8 space-y-6">
        
        <div>
            <label class="block text-xs font-semibold text-slate-700 mb-1.5">Tên sản phẩm <span class="text-rose-500">*</span></label>
            <input type="text" name="name" value="<?= e($name) ?>" required 
                   class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:bg-white focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 focus:outline-none transition">
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1.5">Danh mục hàng hóa <span class="text-rose-500">*</span></label>
                <select name="category" required 
                        class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:bg-white focus:border-emerald-500 focus:outline-none font-medium">
                    <?php foreach ($categoriesList as $cat): ?>
                        <option value="<?= e($cat) ?>" <?= $category === $cat ? 'selected' : '' ?>><?= e($cat) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1.5">Giá bán (VNĐ) <span class="text-rose-500">*</span></label>
                <input type="number" name="price" value="<?= e($price) ?>" min="1000" step="500" required 
                       class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:bg-white focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 focus:outline-none transition font-semibold">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1.5">Số lượng kho <span class="text-rose-500">*</span></label>
                <input type="number" name="stock" value="<?= e($stock) ?>" min="0" required 
                       class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:bg-white focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 focus:outline-none transition font-semibold">
            </div>
        </div>

        <!-- Khối chỉnh sửa hình ảnh -->
        <div class="border-t border-slate-100 pt-5 space-y-4">
            <h3 class="text-xs font-bold text-slate-800 uppercase tracking-wider flex items-center gap-1.5">
                <i class="fa-solid fa-image text-emerald-600"></i> Hình Ảnh Sản Phẩm
            </h3>

            <!-- Ảnh hiện tại -->
            <div class="flex items-center gap-4 p-3.5 bg-slate-50 rounded-2xl border border-slate-200">
                <?php 
                    $currentPreview = $imageUrl;
                    if (strpos($currentPreview, 'http') !== 0) {
                        $currentPreview = base_url($currentPreview);
                    }
                ?>
                <img src="<?= e($currentPreview) ?>" alt="Ảnh hiện tại" class="w-16 h-16 rounded-xl object-cover border border-slate-300">
                <div class="text-xs text-slate-500">
                    <p class="font-bold text-slate-700 mb-0.5">Hình ảnh đang sử dụng</p>
                    <p class="truncate max-w-md font-mono text-[11px]"><?= e($imageUrl) ?></p>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="p-4 rounded-2xl bg-slate-50 border border-dashed border-slate-300">
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Tải ảnh mới thay thế</label>
                    <input type="file" name="image" accept="image/*" 
                           class="block w-full text-xs text-slate-500 file:mr-3 file:py-2 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-emerald-600 file:text-white hover:file:bg-emerald-700 cursor-pointer">
                </div>

                <div class="p-4 rounded-2xl bg-slate-50 border border-dashed border-slate-300">
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Hoặc đổi URL hình ảnh</label>
                    <input type="url" name="image_url" value="<?= (strpos($imageUrl, 'http') === 0) ? e($imageUrl) : '' ?>" 
                           class="w-full px-3 py-2 bg-white border border-slate-200 rounded-xl text-xs focus:border-emerald-500 focus:outline-none"
                           placeholder="https://images.unsplash.com/...">
                </div>
            </div>
        </div>

        <div>
            <label class="block text-xs font-semibold text-slate-700 mb-1.5">Mô tả chi tiết sản phẩm</label>
            <textarea name="description" rows="5" 
                      class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:bg-white focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 focus:outline-none transition leading-relaxed"><?= e($description) ?></textarea>
        </div>

        <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-3">
            <a href="<?= base_url('products/manage.php') ?>" class="px-5 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold rounded-xl text-xs transition">
                Hủy bỏ
            </a>
            <button type="submit" class="px-6 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-xl text-xs shadow-md shadow-emerald-600/20 transition flex items-center gap-1.5">
                <i class="fa-solid fa-floppy-disk"></i> Lưu Thay Đổi
            </button>
        </div>

    </form>

</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
