<?php
/**
 * Xử lý Đăng xuất tài khoản
 */
require_once __DIR__ . '/../includes/functions.php';

if (isset($_SESSION['user'])) {
    unset($_SESSION['user']);
}

set_flash('info', 'Bạn đã đăng xuất khỏi hệ thống thành công.');
redirect(base_url('index.php'));
