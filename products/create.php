<?php
/**
 * Trang Đăng Bán Sản Phẩm Mới
 * Hỗ trợ upload ảnh lưu vào uploads/ hoặc nhập URL ảnh trực tiếp
 */
require_once __DIR__ . '/../includes/functions.php';

require_login();

$errors = [];
$name = '';
$category = 'Lương thực - Thực phẩm';
$price = '';
$stock = '10';
$description = '';
$imageUrl = '';

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
    $imageUrl = trim($_POST['image_url'] ?? '');

    if (empty($name)) {
        $errors[] = 'Vui lòng nhập tên sản phẩm.';
    }
    if ($price <= 0) {
        $errors[] = 'Giá bán sản phẩm phải lớn hơn 0 ₫.';
    }
    if ($stock < 0) {
        $errors[] = 'Số lượng trong kho không được âm.';
    }

    // Xử lý upload ảnh nếu người dùng chọn file
    $finalImagePath = $imageUrl;
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $uploaded = upload_product_image($_FILES['image']);
        if ($uploaded) {
            $finalImagePath = $uploaded;
        } else {
            $errors[] = 'Upload ảnh thất bại. Vui lòng kiểm tra dung lượng (<3MB) và định dạng ảnh.';
        }
    }

    if (empty($finalImagePath)) {
        $finalImagePath = 'https://images.unsplash.com/photo-1542838132-92c53300491e?auto=format&fit=crop&w=600&q=80';
    }

    if (empty($errors)) {
        try {
            $slug = slugify($name) . '-' . time();
            $userId = current_user()['id'];

            $stmt = db()->prepare("
                INSERT INTO products (user_id, name, slug, category, description, price, stock, image_url)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $userId,
                $name,
                $slug,
                $category,
                $description,
                $price,
                $stock,
                $finalImagePath
            ]);

            $newProductId = db()->lastInsertId();

            set_flash('success', 'Đăng bán sản phẩm "' . $name . '" thành công!');
            redirect(base_url('product_detail.php?id=' . $newProductId));
        } catch (PDOException $e) {
            $errors[] = 'Lỗi lưu sản phẩm vào cơ sở dữ liệu: ' . $e->getMessage();
        }
    }
}

$pageTitle = 'Đăng Bán Sản Phẩm Mới';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
    
    <div class="mb-8">
        <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight flex items-center gap-3">
            <i class="fa-solid fa-circle-plus text-emerald-600"></i> Đăng Bán Sản Phẩm Mới
        </h1>
        <p class="text-xs text-slate-500 mt-1">Đăng tải sản phẩm mới lên sàn Tạp Hóa Store để khách hàng có thể tìm thấy và mua sắm</p>
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

    <form action="<?= base_url('products/create.php') ?>" method="POST" enctype="multipart/form-data" class="bg-white rounded-3xl border border-slate-200/80 shadow-sm p-6 sm:p-8 space-y-6">
        
        <div>
            <label class="block text-xs font-semibold text-slate-700 mb-1.5">Tên sản phẩm <span class="text-rose-500">*</span></label>
            <input type="text" name="name" value="<?= e($name) ?>" required 
                   class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:bg-white focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 focus:outline-none transition"
                   placeholder="Ví dụ: Nước mắm cá cơm truyền thống 500ml">
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
                       class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:bg-white focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 focus:outline-none transition font-semibold"
                       placeholder="Ví dụ: 35000">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1.5">Số lượng kho <span class="text-rose-500">*</span></label>
                <input type="number" name="stock" value="<?= e($stock) ?>" min="0" required 
                       class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:bg-white focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 focus:outline-none transition font-semibold"
                       placeholder="Ví dụ: 50">
            </div>
        </div>

        <!-- Khối upload hình ảnh -->
        <div class="border-t border-slate-100 pt-5 space-y-4">
            <h3 class="text-xs font-bold text-slate-800 uppercase tracking-wider flex items-center gap-1.5">
                <i class="fa-solid fa-image text-emerald-600"></i> Hình Ảnh Sản Phẩm
            </h3>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <!-- Cách 1: Tải file từ máy tính -->
                <div class="p-4 rounded-2xl bg-slate-50 border border-dashed border-slate-300">
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Cách 1: Tải file từ máy tính</label>
                    <p class="text-[11px] text-slate-400 mb-2">Định dạng JPG, PNG, WEBP (Tối đa 3MB)</p>
                    <input type="file" name="image" accept="image/*" 
                           class="block w-full text-xs text-slate-500 file:mr-3 file:py-2 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-emerald-600 file:text-white hover:file:bg-emerald-700 cursor-pointer">
                </div>

                <!-- Cách 2: Dán URL hình ảnh -->
                <div class="p-4 rounded-2xl bg-slate-50 border border-dashed border-slate-300">
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Cách 2: Hoặc dán đường link (URL) ảnh</label>
                    <p class="text-[11px] text-slate-400 mb-2">Dán trực tiếp URL ảnh từ Unsplash hoặc Internet</p>
                    <input type="url" name="image_url" value="<?= e($imageUrl) ?>" 
                           class="w-full px-3 py-2 bg-white border border-slate-200 rounded-xl text-xs focus:border-emerald-500 focus:outline-none"
                           placeholder="https://images.unsplash.com/photo-...">
                </div>
            </div>
        </div>

        <!-- Mô tả chi tiết -->
        <div>
            <label class="block text-xs font-semibold text-slate-700 mb-1.5">Mô tả chi tiết sản phẩm</label>
            <textarea name="description" rows="5" 
                      class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:bg-white focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 focus:outline-none transition leading-relaxed"
                      placeholder="Thành phần, hạn sử dụng, hướng dẫn bảo quản, quy cách đóng gói..."><?= e($description) ?></textarea>
        </div>

        <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-3">
            <a href="<?= base_url('products/manage.php') ?>" class="px-5 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold rounded-xl text-xs transition">
                Hủy bỏ
            </a>
            <button type="submit" class="px-6 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-xl text-xs shadow-md shadow-emerald-600/20 transition flex items-center gap-1.5">
                <i class="fa-solid fa-cloud-arrow-up"></i> Đăng Bán Ngay
            </button>
        </div>

    </form>

</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
