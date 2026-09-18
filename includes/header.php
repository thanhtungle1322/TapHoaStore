<?php
/**
 * Header chung cho toàn bộ giao diện
 */
require_once __DIR__ . '/functions.php';

$currentUser = current_user();
$cartItemsCount = cart_count();
$pageTitle = isset($pageTitle) ? $pageTitle . ' - Tạp Hóa Store' : 'Tạp Hóa Store - Cửa Hàng Tiện Lợi Online';
?>
<!DOCTYPE html>
<html lang="vi" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?></title>
    
    <!-- Google Fonts: Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Font Awesome 6 Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                    },
                    colors: {
                        brand: {
                            50: '#ecfdf5',
                            100: '#d1fae5',
                            500: '#10b981',
                            600: '#059669',
                            700: '#047857',
                        }
                    }
                }
            }
        }
    </script>
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
    </style>
</head>
<body class="bg-slate-50 text-slate-800 flex flex-col min-h-screen antialiased">

    <!-- Thanh thông báo đầu trang (Top Bar) -->
    <header class="bg-emerald-800 text-emerald-50 text-xs py-2 px-4 border-b border-emerald-900/20">
        <div class="max-w-7xl mx-auto flex flex-wrap justify-between items-center gap-2">
            <div class="flex items-center gap-4">
                <span><i class="fa-solid fa-truck-fast text-emerald-300 mr-1.5"></i> Miễn phí vận chuyển cho đơn hàng từ 300.000 ₫</span>
                <span class="hidden sm:inline-block text-emerald-400">|</span>
                <span class="hidden sm:inline-block"><i class="fa-solid fa-headset text-emerald-300 mr-1"></i> Hotline: 1900 6868</span>
            </div>
            <div class="flex items-center gap-3">
                <?php if ($currentUser): ?>
                    <span class="text-emerald-200">Xin chào, <strong class="text-white"><?= e($currentUser['name']) ?></strong></span>
                    <?php if (is_admin()): ?>
                        <span class="bg-amber-400 text-amber-950 font-bold px-2 py-0.5 rounded text-[10px]">ADMIN / SELLER</span>
                    <?php endif; ?>
                <?php else: ?>
                    <a href="<?= base_url('auth/login.php') ?>" class="hover:text-white transition">Đăng nhập</a>
                    <span class="text-emerald-400">|</span>
                    <a href="<?= base_url('auth/register.php') ?>" class="hover:text-white transition">Đăng ký</a>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <!-- Thanh điều hướng chính (Main Navbar) -->
    <nav class="bg-white border-b border-slate-200 sticky top-0 z-40 shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16 gap-4">
                
                <!-- Logo thương hiệu -->
                <div class="flex-shrink-0">
                    <a href="<?= base_url('index.php') ?>" class="flex items-center gap-2.5">
                        <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-emerald-600 to-teal-500 flex items-center justify-center text-white text-xl shadow-md shadow-emerald-500/20">
                            <i class="fa-solid fa-basket-shopping"></i>
                        </div>
                        <div>
                            <span class="text-xl font-extrabold tracking-tight text-slate-900 block leading-tight">Tạp Hóa<span class="text-emerald-600">Store</span></span>
                            <span class="text-[10px] text-slate-400 font-medium tracking-wide uppercase block -mt-1">Tiện Lợi & Tươi Mới</span>
                        </div>
                    </a>
                </div>

                <!-- Form tìm kiếm sản phẩm (Desktop) -->
                <div class="hidden md:flex flex-1 max-w-lg mx-6">
                    <form action="<?= base_url('index.php') ?>" method="GET" class="w-full relative">
                        <input type="text" name="q" value="<?= e($_GET['q'] ?? '') ?>" 
                               placeholder="Tìm kiếm gạo, dầu ăn, mì gói, nước ngọt..." 
                               class="w-full pl-10 pr-24 py-2 bg-slate-100 focus:bg-white text-sm rounded-full border border-slate-200 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 focus:outline-none transition duration-150">
                        <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-sm"></i>
                        <button type="submit" class="absolute right-1 top-1/2 -translate-y-1/2 px-4 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold rounded-full transition duration-150">
                            Tìm kiếm
                        </button>
                    </form>
                </div>

                <!-- Menu điều hướng bên phải -->
                <div class="flex items-center gap-2 sm:gap-3">
                    
                    <!-- Nút Đăng Bán Sản Phẩm -->
                    <a href="<?= base_url('products/create.php') ?>" class="hidden sm:inline-flex items-center gap-1.5 text-xs font-semibold px-3 py-2 text-emerald-700 bg-emerald-50 hover:bg-emerald-100 rounded-lg border border-emerald-200 transition">
                        <i class="fa-solid fa-circle-plus text-emerald-600"></i> Đăng bán
                    </a>

                    <!-- Nút Giỏ Hàng -->
                    <a href="<?= base_url('cart.php') ?>" class="relative inline-flex items-center justify-center p-2 text-slate-600 hover:text-emerald-600 hover:bg-slate-100 rounded-xl transition duration-150">
                        <i class="fa-solid fa-cart-shopping text-xl"></i>
                        <span id="cartBadge" class="absolute -top-1 -right-1 bg-rose-500 text-white text-[11px] font-bold h-5 min-w-[20px] px-1 rounded-full flex items-center justify-center shadow-sm <?= $cartItemsCount > 0 ? '' : 'hidden' ?>">
                            <?= $cartItemsCount ?>
                        </span>
                    </a>

                    <!-- Menu Người dùng (Dropdown) -->
                    <?php if ($currentUser): ?>
                        <div class="relative" id="userMenuContainer">
                            <button type="button" id="userMenuBtn" class="flex items-center gap-2 p-1.5 text-sm rounded-xl hover:bg-slate-100 transition focus:outline-none">
                                <div class="w-8 h-8 rounded-full bg-emerald-600 text-white font-bold text-xs flex items-center justify-center shadow-sm">
                                    <?= mb_substr(strtoupper($currentUser['name']), 0, 1) ?>
                                </div>
                                <span class="hidden lg:inline-block font-medium text-slate-700 text-xs"><?= e($currentUser['name']) ?></span>
                                <i class="fa-solid fa-chevron-down text-slate-400 text-[10px]"></i>
                            </button>

                            <!-- Dropdown List -->
                            <div id="userDropdown" class="hidden absolute right-0 mt-2 w-56 bg-white rounded-2xl shadow-xl border border-slate-100 py-2 z-50 text-sm">
                                <div class="px-4 py-2 border-b border-slate-100">
                                    <p class="text-xs text-slate-400">Đăng nhập với email</p>
                                    <p class="font-semibold text-slate-800 truncate text-xs"><?= e($currentUser['email']) ?></p>
                                </div>
                                <a href="<?= base_url('orders/my_orders.php') ?>" class="flex items-center gap-2 px-4 py-2 text-slate-700 hover:bg-slate-50 hover:text-emerald-600 transition">
                                    <i class="fa-solid fa-box text-slate-400 w-4"></i> Đơn hàng của tôi
                                </a>
                                <a href="<?= base_url('products/manage.php') ?>" class="flex items-center gap-2 px-4 py-2 text-slate-700 hover:bg-slate-50 hover:text-emerald-600 transition">
                                    <i class="fa-solid fa-boxes-stacked text-slate-400 w-4"></i> Quản lý sản phẩm đã đăng
                                </a>
                                <?php if (is_admin()): ?>
                                    <div class="border-t border-slate-100 my-1"></div>
                                    <a href="<?= base_url('orders/manage_orders.php') ?>" class="flex items-center gap-2 px-4 py-2 text-amber-700 hover:bg-amber-50 transition">
                                        <i class="fa-solid fa-clipboard-check text-amber-500 w-4"></i> Quản lý tất cả đơn hàng
                                    </a>
                                <?php endif; ?>
                                <div class="border-t border-slate-100 my-1"></div>
                                <a href="<?= base_url('auth/logout.php') ?>" class="flex items-center gap-2 px-4 py-2 text-red-600 hover:bg-red-50 transition">
                                    <i class="fa-solid fa-arrow-right-from-bracket w-4"></i> Đăng xuất
                                </a>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="hidden sm:flex items-center gap-2">
                            <a href="<?= base_url('auth/login.php') ?>" class="text-xs font-semibold px-3 py-2 text-slate-700 hover:text-emerald-600 hover:bg-slate-100 rounded-lg transition">Đăng nhập</a>
                            <a href="<?= base_url('auth/register.php') ?>" class="text-xs font-semibold px-3 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg shadow-sm transition">Đăng ký</a>
                        </div>
                    <?php endif; ?>

                    <!-- Nút Mobile Menu Toggle -->
                    <button type="button" id="mobileMenuBtn" class="md:hidden p-2 text-slate-600 hover:text-emerald-600 rounded-lg focus:outline-none">
                        <i class="fa-solid fa-bars text-xl"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- Mobile Drawer / Dropdown -->
        <div id="mobileMenu" class="hidden md:hidden border-t border-slate-200 bg-white px-4 py-3 space-y-3">
            <form action="<?= base_url('index.php') ?>" method="GET" class="relative">
                <input type="text" name="q" value="<?= e($_GET['q'] ?? '') ?>" placeholder="Tìm kiếm sản phẩm..." class="w-full pl-9 pr-4 py-2 bg-slate-100 text-sm rounded-lg border border-slate-200">
                <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
            </form>
            <div class="flex flex-col gap-1 text-sm font-medium">
                <a href="<?= base_url('index.php') ?>" class="py-2 px-3 rounded-lg hover:bg-slate-100 text-slate-700">Trang chủ</a>
                <a href="<?= base_url('products/create.php') ?>" class="py-2 px-3 rounded-lg hover:bg-emerald-50 text-emerald-700 font-semibold">
                    <i class="fa-solid fa-circle-plus mr-1"></i> Đăng bán sản phẩm mới
                </a>
                <?php if ($currentUser): ?>
                    <a href="<?= base_url('orders/my_orders.php') ?>" class="py-2 px-3 rounded-lg hover:bg-slate-100 text-slate-700">Đơn hàng của tôi</a>
                    <a href="<?= base_url('products/manage.php') ?>" class="py-2 px-3 rounded-lg hover:bg-slate-100 text-slate-700">Quản lý sản phẩm</a>
                    <?php if (is_admin()): ?>
                        <a href="<?= base_url('orders/manage_orders.php') ?>" class="py-2 px-3 rounded-lg hover:bg-amber-50 text-amber-700">Quản lý đơn hàng (Admin)</a>
                    <?php endif; ?>
                    <a href="<?= base_url('auth/logout.php') ?>" class="py-2 px-3 rounded-lg hover:bg-red-50 text-red-600">Đăng xuất</a>
                <?php else: ?>
                    <div class="grid grid-cols-2 gap-2 pt-2 border-t border-slate-100">
                        <a href="<?= base_url('auth/login.php') ?>" class="text-center py-2 px-3 bg-slate-100 text-slate-700 rounded-lg text-xs font-semibold">Đăng nhập</a>
                        <a href="<?= base_url('auth/register.php') ?>" class="text-center py-2 px-3 bg-emerald-600 text-white rounded-lg text-xs font-semibold">Đăng ký</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <!-- Hiển thị Flash Toast Messages -->
    <?php $flashList = get_flash(); if (!empty($flashList)): ?>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-4 w-full">
            <div class="space-y-2">
                <?php foreach ($flashList as $flash): 
                    $colorMap = [
                        'success' => 'bg-emerald-50 border-emerald-300 text-emerald-800 icon-fa-circle-check text-emerald-600',
                        'error'   => 'bg-rose-50 border-rose-300 text-rose-800 icon-fa-circle-xmark text-rose-600',
                        'warning' => 'bg-amber-50 border-amber-300 text-amber-800 icon-fa-triangle-exclamation text-amber-600',
                        'info'    => 'bg-sky-50 border-sky-300 text-sky-800 icon-fa-circle-info text-sky-600'
                    ];
                    $styling = $colorMap[$flash['type']] ?? $colorMap['info'];
                    $iconClass = explode('icon-', $styling)[1] ?? 'fa-circle-info text-sky-600';
                ?>
                    <div class="flash-toast flex items-center justify-between p-3.5 rounded-xl border text-sm <?= $styling ?> shadow-sm transition-all duration-300" role="alert">
                        <div class="flex items-center gap-2.5">
                            <i class="fa-solid <?= $iconClass ?> text-base"></i>
                            <span class="font-medium"><?= e($flash['message']) ?></span>
                        </div>
                        <button type="button" onclick="this.parentElement.remove()" class="text-slate-400 hover:text-slate-700 p-1">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Thân trang bắt đầu -->
    <main class="flex-grow">
