<?php
/**
 * Xử Lý Xóa Sản Phẩm
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
        set_flash('error', 'Không tìm thấy sản phẩm cần xóa.');
        redirect(base_url('products/manage.php'));
    }

    // Kiểm tra quyền: Phải là chủ sản phẩm hoặc Admin
    if (!is_admin() && (int)$product['user_id'] !== (int)current_user()['id']) {
        set_flash('error', 'Bạn không có quyền xóa sản phẩm này.');
        redirect(base_url('products/manage.php'));
    }

    // Xóa file ảnh local nếu có
    $img = $product['image_url'];
    if (!empty($img) && strpos($img, 'uploads/') === 0) {
        $filePath = __DIR__ . '/../' . $img;
        if (file_exists($filePath)) {
            @unlink($filePath);
        }
    }

    // Xóa bản ghi trong CSDL
    $delStmt = db()->prepare("DELETE FROM products WHERE id = ?");
    $delStmt->execute([$productId]);

    set_flash('success', 'Đã xóa sản phẩm "' . $product['name'] . '" thành công.');

} catch (PDOException $e) {
    set_flash('error', 'Lỗi khi xóa sản phẩm: ' . $e->getMessage());
}

redirect(base_url('products/manage.php'));
