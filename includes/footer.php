<?php
/**
 * Chân trang (Footer) chung cho toàn bộ giao diện
 */
?>
    </main>

    <!-- Chân trang (Footer) -->
    <footer class="bg-slate-900 text-slate-300 mt-16 pt-12 pb-8 border-t border-slate-800 text-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8 mb-10">
                
                <!-- Cột 1: Thông tin thương hiệu -->
                <div class="space-y-4">
                    <div class="flex items-center gap-2.5">
                        <div class="w-9 h-9 rounded-xl bg-emerald-500 flex items-center justify-center text-white text-lg">
                            <i class="fa-solid fa-basket-shopping"></i>
                        </div>
                        <span class="text-xl font-bold text-white tracking-tight">Tạp Hóa<span class="text-emerald-400">Store</span></span>
                    </div>
                    <p class="text-slate-400 text-xs leading-relaxed">
                        Nền tảng mua sắm tạp hóa online tiện lợi, cam kết hàng chính hãng 100%, giao nhanh tận cửa nhà, giá cả bình ổn và phục vụ tận tâm.
                    </p>
                    <div class="flex items-center gap-3 pt-2 text-slate-400">
                        <a href="#" class="w-8 h-8 rounded-full bg-slate-800 flex items-center justify-center hover:bg-emerald-600 hover:text-white transition"><i class="fa-brands fa-facebook-f"></i></a>
                        <a href="#" class="w-8 h-8 rounded-full bg-slate-800 flex items-center justify-center hover:bg-emerald-600 hover:text-white transition"><i class="fa-brands fa-tiktok"></i></a>
                        <a href="#" class="w-8 h-8 rounded-full bg-slate-800 flex items-center justify-center hover:bg-emerald-600 hover:text-white transition"><i class="fa-brands fa-youtube"></i></a>
                    </div>
                </div>

                <!-- Cột 2: Liên kết nhanh -->
                <div class="space-y-3">
                    <h4 class="text-white font-semibold text-sm uppercase tracking-wider">Khám Phá Nhanh</h4>
                    <ul class="space-y-2 text-xs text-slate-400">
                        <li><a href="<?= base_url('index.php') ?>" class="hover:text-emerald-400 transition">Trang chủ & Khuyến mãi</a></li>
                        <li><a href="<?= base_url('cart.php') ?>" class="hover:text-emerald-400 transition">Giỏ hàng của bạn</a></li>
                        <li><a href="<?= base_url('products/create.php') ?>" class="hover:text-emerald-400 transition">Đăng bán sản phẩm mới</a></li>
                        <li><a href="<?= base_url('orders/my_orders.php') ?>" class="hover:text-emerald-400 transition">Tra cứu đơn mua</a></li>
                    </ul>
                </div>

                <!-- Cột 3: Hỗ trợ khách hàng & Thanh toán -->
                <div class="space-y-3">
                    <h4 class="text-white font-semibold text-sm uppercase tracking-wider">Phương Thức Thanh Toán</h4>
                    <div class="flex flex-wrap gap-2 text-xs">
                        <span class="px-2.5 py-1.5 rounded-lg bg-slate-800 border border-slate-700 text-slate-300 flex items-center gap-1.5">
                            <i class="fa-solid fa-money-bill-wave text-emerald-400"></i> Tiền mặt (COD)
                        </span>
                        <span class="px-2.5 py-1.5 rounded-lg bg-slate-800 border border-slate-700 text-slate-300 flex items-center gap-1.5">
                            <i class="fa-solid fa-qrcode text-sky-400"></i> Quét mã VietQR
                        </span>
                    </div>
                    <p class="text-xs text-slate-400 pt-2">
                        <i class="fa-solid fa-shield-halved text-emerald-400 mr-1"></i> Bảo mật thanh toán và kiểm tra hàng trước khi nhận.
                    </p>
                </div>

                <!-- Cột 4: Liên hệ & Địa chỉ -->
                <div class="space-y-3">
                    <h4 class="text-white font-semibold text-sm uppercase tracking-wider">Thông Tin Liên Hệ</h4>
                    <ul class="space-y-2 text-xs text-slate-400">
                        <li class="flex items-start gap-2">
                            <i class="fa-solid fa-location-dot text-emerald-400 mt-0.5"></i>
                            <span>123 Đường Cầu Giấy, P. Quan Hoa, TP. Hà Nội</span>
                        </li>
                        <li class="flex items-center gap-2">
                            <i class="fa-solid fa-phone text-emerald-400"></i>
                            <span>1900 6868 (8:00 - 22:00)</span>
                        </li>
                        <li class="flex items-center gap-2">
                            <i class="fa-solid fa-envelope text-emerald-400"></i>
                            <span>support@taphoa.vn</span>
                        </li>
                    </ul>
                </div>

            </div>

            <div class="border-t border-slate-800 pt-6 flex flex-col sm:flex-row justify-between items-center gap-3 text-xs text-slate-500">
                <p>&copy; <?= date('Y') ?> Tạp Hóa Store. Hệ thống Bán hàng Trực tuyến Native PHP & MySQL.</p>
                <p class="flex items-center gap-1">
                    Deploy sẵn sàng trên <span class="text-slate-300 font-medium">InfinityFree</span> & <span class="text-slate-300 font-medium">MonsterASP</span>
                </p>
            </div>
        </div>
    </footer>

    <!-- Script điều khiển giao diện -->
    <script>
        // Menu Người dùng Dropdown (Desktop)
        const userMenuBtn = document.getElementById('userMenuBtn');
        const userDropdown = document.getElementById('userDropdown');
        if (userMenuBtn && userDropdown) {
            userMenuBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                userDropdown.classList.toggle('hidden');
            });
            document.addEventListener('click', (e) => {
                if (!userDropdown.contains(e.target) && !userMenuBtn.contains(e.target)) {
                    userDropdown.classList.add('hidden');
                }
            });
        }

        // Mobile Menu Toggle
        const mobileMenuBtn = document.getElementById('mobileMenuBtn');
        const mobileMenu = document.getElementById('mobileMenu');
        if (mobileMenuBtn && mobileMenu) {
            mobileMenuBtn.addEventListener('click', () => {
                mobileMenu.classList.toggle('hidden');
            });
        }

        // Tự động làm mờ và ẩn Toast sau 5 giây
        setTimeout(() => {
            document.querySelectorAll('.flash-toast').forEach(toast => {
                toast.style.opacity = '0';
                setTimeout(() => toast.remove(), 300);
            });
        }, 5000);
    </script>
</body>
</html>
