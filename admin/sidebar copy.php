<?php
// sidebar.php - Included in admin pages
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!-- Sidebar Backdrop -->
<div x-show="sidebarOpen" x-cloak @click="sidebarOpen = false" style="display: none;"
    class="fixed inset-0 bg-black/50 z-40 lg:hidden transition-opacity"></div>

<!-- Sidebar -->
<aside :class="sidebarOpen ? 'active' : ''"
    class="sidebar w-64 fixed lg:sticky top-0 h-screen bg-white border-r border-gray-100 p-6 z-50 flex flex-col shrink-0">
    <div class="flex items-center gap-3 mb-12">
        <?php
        $admin_name = $_SESSION['admin_user'] ?? 'Admin';
        $admin_initial = strtoupper(substr($admin_name, 0, 1));
        $admin_role = $_SESSION['admin_role'] ?? 'Admin';
        ?>
        <div class="w-10 h-10 bg-orange-500 rounded-xl flex items-center justify-center shadow-lg shadow-orange-500/20">
            <span class="font-black text-white text-lg"><?= $admin_initial ?></span>
        </div>
        <div class="flex flex-col pt-1">
            <h1 class="text-lg font-bold tracking-tight leading-none text-gray-900">
                <?= htmlspecialchars(ucfirst($admin_name)) ?>
            </h1>
            <span class="text-[10px] text-orange-500 font-bold uppercase tracking-widest mt-1"><?= $admin_role ?></span>
        </div>
    </div>

    <nav class="space-y-4">
        <a href="dashboard.php"
            class="flex items-center gap-3 p-3 rounded-xl <?= $current_page == 'dashboard.php' ? 'bg-orange-500/10 text-orange-500 font-semibold' : 'hover:bg-black/5 transition-colors text-gray-600' ?>">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path
                    d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z">
                </path>
            </svg>
            Dashboard
        </a>
        <a href="index.php"
            class="flex items-center gap-3 p-3 rounded-xl <?= ($current_page == 'index.php' || $current_page == 'add_item.php' || $current_page == 'edit_item.php') ? 'bg-orange-500/10 text-orange-500 font-semibold' : 'hover:bg-black/5 transition-colors text-gray-600' ?>">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path d="M4 6h16M4 12h16M4 18h16"></path>
            </svg>
            Menu Items
        </a>
        <a href="categories.php"
            class="flex items-center gap-3 p-3 rounded-xl <?= $current_page == 'categories.php' ? 'bg-orange-500/10 text-orange-500 font-semibold' : 'hover:bg-black/5 transition-colors text-gray-600' ?>">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path d="M7 7h.01M7 11h.01M7 15h.01M13 7h7M13 11h7M13 15h7"></path>
            </svg>
            Categories
        </a>
        <a href="feedback.php"
            class="flex items-center justify-between p-3 rounded-xl <?= $current_page == 'feedback.php' ? 'bg-orange-500/10 text-orange-500 font-semibold' : 'hover:bg-black/5 transition-colors text-gray-600' ?>">
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
                    class="w-5 h-5 bg-orange-500 text-white text-[10px] flex items-center justify-center rounded-full font-bold shadow-lg shadow-orange-500/20"><?= $unread_count ?></span>
            <?php endif; ?>
        </a>
        <a href="settings.php"
            class="flex items-center gap-3 p-3 rounded-xl <?= $current_page == 'settings.php' ? 'bg-orange-500/10 text-orange-500 font-semibold' : 'hover:bg-black/5 transition-colors text-gray-600' ?>">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path
                    d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z">
                </path>
            </svg>
            Settings
        </a>
        <div class="mt-auto pt-8 border-t border-gray-100">
            <a href="../menu.php"
                class="flex items-center gap-3 p-3 rounded-xl hover:bg-black/5 transition-colors text-slate-500">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
                View Site
            </a>
            <a href="logout.php"
                class="flex items-center gap-3 p-3 rounded-xl hover:bg-rose-50 transition-colors text-rose-500 mt-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1">
                    </path>
                </svg>
                Logout
            </a>
        </div>
    </nav>
</aside>