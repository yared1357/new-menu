<?php
// sidebar.php - Included in admin pages
$current_page = basename($_SERVER['PHP_SELF']);
$admin_name = $_SESSION['admin_user'] ?? 'Admin';
$admin_initial = strtoupper(substr($admin_name, 0, 1));
$admin_role = $_SESSION['admin_role'] ?? 'Admin';
$is_super_admin = false;

if (isset($_SESSION['admin_id'])) {
    $current_admin_query = $pdo->prepare('SELECT username, full_name, role FROM users WHERE id = ?');
    $current_admin_query->execute([$_SESSION['admin_id']]);
    $current_admin = $current_admin_query->fetch();
    if ($current_admin) {
        $admin_name = $current_admin['full_name'] ?: $current_admin['username'];
        $admin_initial = strtoupper(substr($admin_name, 0, 1));
        $admin_role = $current_admin['role'] ?? 'Admin';
        $_SESSION['admin_user'] = $admin_name;
        $_SESSION['admin_role'] = $admin_role;
        $is_super_admin = strtolower(trim($admin_name)) === 'yared' && $admin_role === 'Super Admin';
    }
}
?>
<!-- Sidebar Backdrop -->
<div x-show="sidebarOpen" x-cloak @click="sidebarOpen = false" style="display: none;"
    class="fixed inset-0 bg-black/50 z-40 lg:hidden transition-opacity"></div>

<!-- Sidebar -->
<aside :class="sidebarOpen ? 'active' : ''"
    class="sidebar w-64 fixed lg:sticky top-0 bg-gradient-to-b from-[#1f322e] via-[#173b34] to-[#122c27] border-r border-white/10 px-5 pt-5 pb-[2px] sm:px-6 sm:pt-6 z-50 flex flex-col shrink-0 text-white shadow-2xl shadow-[#102720]/20">
    <div class="mb-6 flex shrink-0 items-center gap-3 rounded-2xl border border-white/10 bg-white/5 p-3 sm:mb-8">
        <div class="w-10 h-10 bg-gradient-to-br from-amber-300 to-orange-400 rounded-xl flex items-center justify-center shadow-lg shadow-orange-950/30">
            <span class="font-black text-[#26352d] text-lg"><?= htmlspecialchars($admin_initial) ?></span>
        </div>
        <div class="flex flex-col pt-1">
            <h1 class="text-base font-bold tracking-tight leading-none text-white">
                <?= htmlspecialchars(ucfirst($admin_name)) ?>
            </h1>
            <span class="text-[10px] text-amber-200 font-bold uppercase tracking-widest mt-1"><?= htmlspecialchars($admin_role) ?></span>
        </div>
    </div>

    <nav class="flex min-h-0 flex-1 flex-col space-y-2 overflow-y-auto overscroll-contain pr-1">
        <a href="dashboard.php"
            class="group flex items-center gap-3 rounded-xl border px-3 py-3 transition-all duration-200 <?= $current_page == 'dashboard.php' ? 'border-amber-200/20 bg-gradient-to-r from-amber-300 to-orange-300 font-semibold text-[#25342d] shadow-lg shadow-black/10' : 'border-transparent text-white/75 hover:border-white/10 hover:bg-white/10 hover:text-white' ?>">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path
                    d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z">
                </path>
            </svg>
            Dashboard
        </a>
        <a href="index.php"
            class="group flex items-center gap-3 rounded-xl border px-3 py-3 transition-all duration-200 <?= ($current_page == 'index.php' || $current_page == 'add_item.php' || $current_page == 'edit_item.php') ? 'border-amber-200/20 bg-gradient-to-r from-amber-300 to-orange-300 font-semibold text-[#25342d] shadow-lg shadow-black/10' : 'border-transparent text-white/75 hover:border-white/10 hover:bg-white/10 hover:text-white' ?>">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path d="M4 6h16M4 12h16M4 18h16"></path>
            </svg>
            Menu Items
        </a>
        <a href="categories.php"
            class="group flex items-center gap-3 rounded-xl border px-3 py-3 transition-all duration-200 <?= $current_page == 'categories.php' ? 'border-amber-200/20 bg-gradient-to-r from-amber-300 to-orange-300 font-semibold text-[#25342d] shadow-lg shadow-black/10' : 'border-transparent text-white/75 hover:border-white/10 hover:bg-white/10 hover:text-white' ?>">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path d="M7 7h.01M7 11h.01M7 15h.01M13 7h7M13 11h7M13 15h7"></path>
            </svg>
            Categories
        </a>
        <a href="payment_accounts.php"
            class="group flex items-center gap-3 rounded-xl border px-3 py-3 transition-all duration-200 <?= $current_page == 'payment_accounts.php' ? 'border-amber-200/20 bg-gradient-to-r from-amber-300 to-orange-300 font-semibold text-[#25342d] shadow-lg shadow-black/10' : 'border-transparent text-white/75 hover:border-white/10 hover:bg-white/10 hover:text-white' ?>">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                    d="M3 7h18M5 4h14a2 2 0 012 2v12a2 2 0 01-2 2H5a2 2 0 01-2-2V6a2 2 0 012-2zm1 13h5" />
            </svg>
            Payments
        </a>
        <a href="social_links.php"
            class="group flex items-center gap-3 rounded-xl border px-3 py-3 transition-all duration-200 <?= $current_page == 'social_links.php' ? 'border-amber-200/20 bg-gradient-to-r from-amber-300 to-orange-300 font-semibold text-[#25342d] shadow-lg shadow-black/10' : 'border-transparent text-white/75 hover:border-white/10 hover:bg-white/10 hover:text-white' ?>">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M18 8a3 3 0 1 0-2.83-4M6 14a3 3 0 1 0 0-4m12 11a3 3 0 1 0-2.83-4M8.7 10.7l6.6-3.4m-6.6 6 6.6 3.4" />
            </svg>
            Social Links
        </a>
        <a href="feedback.php"
            class="group flex items-center justify-between rounded-xl border px-3 py-3 transition-all duration-200 <?= $current_page == 'feedback.php' ? 'border-amber-200/20 bg-gradient-to-r from-amber-300 to-orange-300 font-semibold text-[#25342d] shadow-lg shadow-black/10' : 'border-transparent text-white/75 hover:border-white/10 hover:bg-white/10 hover:text-white' ?>">
            <div class="flex items-center gap-3">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path
                        d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z">
                    </path>
                </svg>
                Feedback
            </div>
            <?php
            $unread_count = $pdo->query("SELECT COUNT(*) FROM feedback WHERE is_read = 0")->fetchColumn();
            if ($unread_count > 0):
                ?>
                <span
                    class="w-5 h-5 bg-orange-400 text-[#26352d] text-[10px] flex items-center justify-center rounded-full font-bold shadow-lg shadow-black/20"><?= $unread_count ?></span>
            <?php endif; ?>
        </a>
        <a href="settings.php"
            class="group flex items-center gap-3 rounded-xl border px-3 py-3 transition-all duration-200 <?= $current_page == 'settings.php' ? 'border-amber-200/20 bg-gradient-to-r from-amber-300 to-orange-300 font-semibold text-[#25342d] shadow-lg shadow-black/10' : 'border-transparent text-white/75 hover:border-white/10 hover:bg-white/10 hover:text-white' ?>">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 8a4 4 0 1 0 0 8 4 4 0 0 0 0-8Zm0-6v2m0 16v2M4.93 4.93l1.42 1.42m11.3 11.3 1.42 1.42M2 12h2m16 0h2M4.93 19.07l1.42-1.42m11.3-11.3 1.42-1.42" />
            </svg>
            Settings
        </a>
        <a href="change_password.php"
            class="group flex items-center gap-3 rounded-xl border px-3 py-3 transition-all duration-200 <?= $current_page == 'change_password.php' ? 'border-amber-200/20 bg-gradient-to-r from-amber-300 to-orange-300 font-semibold text-[#25342d] shadow-lg shadow-black/10' : 'border-transparent text-white/75 hover:border-white/10 hover:bg-white/10 hover:text-white' ?>">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M7 10V8a5 5 0 0 1 10 0v2m-11 0h12a2 2 0 0 1 2 2v7a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2v-7a2 2 0 0 1 2-2Zm6 4v3" />
            </svg>
            Change Password
        </a>
        <?php if ($is_super_admin): ?>
            <a href="admin_users.php"
                class="group flex items-center gap-3 rounded-xl border px-3 py-3 transition-all duration-200 <?= $current_page == 'admin_users.php' ? 'border-amber-200/20 bg-gradient-to-r from-amber-300 to-orange-300 font-semibold text-[#25342d] shadow-lg shadow-black/10' : 'border-transparent text-white/75 hover:border-white/10 hover:bg-white/10 hover:text-white' ?>">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M16 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2m6-10a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm10 10v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75" />
                </svg>
                Manage Admins
            </a>
            <a href="password_reset_requests.php"
                class="group flex items-center gap-3 rounded-xl border px-3 py-3 transition-all duration-200 <?= $current_page == 'password_reset_requests.php' ? 'border-amber-200/20 bg-gradient-to-r from-amber-300 to-orange-300 font-semibold text-[#25342d] shadow-lg shadow-black/10' : 'border-transparent text-white/75 hover:border-white/10 hover:bg-white/10 hover:text-white' ?>">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 8v4l3 2m6-2a9 9 0 1 1-18 0 9 9 0 0 1 18 0ZM8 3.5l-2 2m10-2 2 2"/></svg>
                Password Requests
            </a>
        <?php endif; ?>
    </nav>
    <div class="mt-auto shrink-0 space-y-2 border-t border-white/10 pt-4">
        <a href="../menu.php" target="_blank" rel="noopener noreferrer"
            class="flex items-center gap-3 rounded-xl border border-white/10 bg-white/5 p-3 text-sm font-medium text-white/75 transition-all hover:border-amber-200/20 hover:bg-white/10 hover:text-white">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                    d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12Zm10 3a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z" />
            </svg>
            View Site
        </a>
        <a href="logout.php" @click.prevent="requestConfirmation('Are you sure you want to log out?', () => window.location.href = 'logout.php')"
            class="flex items-center gap-3 rounded-xl border border-rose-200/15 bg-rose-400/10 p-3 text-sm font-semibold text-rose-100 transition-all hover:border-rose-200/30 hover:bg-rose-400/20 hover:text-white">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1">
                </path>
            </svg>
            Logout
        </a>
    </div>
</aside>