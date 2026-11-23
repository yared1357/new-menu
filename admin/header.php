<?php
$admin_flash = $_SESSION['admin_flash'] ?? null;
unset($_SESSION['admin_flash']);
$admin_brand_name = get_app_setting('brand_name', 'Tasty Pizza & Burger');
$admin_brand_logo = get_app_setting('brand_logo');
$admin_brand_initial = strtoupper(mb_substr(trim($admin_brand_name), 0, 1, 'UTF-8') ?: 'A');
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>
        <?= isset($page_title) ? $page_title . " | " : "Admin Panel | " ?><?= htmlspecialchars(get_app_setting('brand_name', 'Tasty Pizza & Burger')) ?>
    </title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="../assets/js/alpine.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --admin-footer-height: 72px;
        }

        body {
            font-family: 'Outfit', sans-serif;
            background: #f7f7f2;
            color: #20332b;
        }

        .glass-card {
            background: rgba(255, 255, 255, 0.94);
            border: 1px solid rgba(31, 50, 46, 0.08);
            box-shadow: 0 12px 36px rgba(31, 50, 46, 0.07);
        }

        .sidebar {
            height: calc(100vh - var(--admin-footer-height));
            height: calc(100dvh - var(--admin-footer-height));
        }

        .admin-page-content {
            padding-bottom: calc(var(--admin-footer-height) + 2rem);
        }

        .admin-footer {
            height: var(--admin-footer-height);
        }

        @media (max-width: 640px) {
            :root {
                --admin-footer-height: 88px;
            }
        }

        @media (max-width: 1024px) {
            .sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s ease-in-out;
            }

            .sidebar.active {
                transform: translateX(0);
            }
        }

        [x-cloak] {
            display: none !important;
        }
    </style>
</head>

<body class="bg-[#f7f7f2]" x-data="{ sidebarOpen: false, search: '', confirmOpen: false, confirmMessage: '', confirmAction: null, confirmDanger: true, requestConfirmation(message, action, danger = true) { this.confirmMessage = message; this.confirmAction = action; this.confirmDanger = danger; this.confirmOpen = true; } }">
    <?php if ($admin_flash): ?>
        <div x-data="{ visible: true }" x-show="visible" x-cloak x-init="setTimeout(() => visible = false, 2800)"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 translate-y-3 sm:translate-y-0 sm:translate-x-4"
            x-transition:enter-end="opacity-100 translate-y-0 sm:translate-x-0"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            role="status" aria-live="polite"
            class="fixed right-4 top-4 z-[200] w-[calc(100%-2rem)] max-w-sm overflow-hidden rounded-2xl border border-emerald-100 bg-white shadow-2xl shadow-emerald-950/15 sm:right-6 sm:top-6">
            <div class="flex items-start gap-3 p-4">
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-emerald-50 text-emerald-700">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="m5 12 4 4L19 6" />
                    </svg>
                </span>
                <div class="min-w-0 flex-1 pt-0.5">
                    <p class="font-bold text-[#20332b]"><?= htmlspecialchars($admin_flash['title'], ENT_QUOTES, 'UTF-8') ?></p>
                    <p class="mt-1 text-sm leading-5 text-gray-600"><?= htmlspecialchars($admin_flash['message'], ENT_QUOTES, 'UTF-8') ?></p>
                </div>
                <button type="button" @click="visible = false" aria-label="Dismiss notification"
                    class="rounded-lg p-1 text-gray-400 transition hover:bg-gray-100 hover:text-gray-700">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m6 6 12 12M18 6 6 18" />
                    </svg>
                </button>
            </div>
            <div class="h-1 bg-emerald-50">
                <div class="h-full origin-left bg-emerald-500" style="animation: admin-toast-progress 3s linear forwards"></div>
            </div>
        </div>
    <?php endif; ?>

    <div x-show="confirmOpen" x-cloak x-transition.opacity @keydown.escape.window="confirmOpen = false"
        @click.self="confirmOpen = false" class="fixed inset-0 z-[210] flex items-center justify-center bg-[#102720]/55 p-4 backdrop-blur-sm">
        <section role="dialog" aria-modal="true" aria-labelledby="admin-confirm-title"
            class="w-full max-w-md rounded-3xl border border-white/60 bg-white p-6 text-center shadow-2xl sm:p-7">
            <div class="mx-auto mb-5 flex h-12 w-12 items-center justify-center rounded-2xl bg-rose-50 text-rose-600">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                        d="M12 9v3m0 4h.01M10.3 3.86 1.82 18.5A2 2 0 0 0 3.55 21h16.9a2 2 0 0 0 1.73-2.5L13.7 3.86a2 2 0 0 0-3.4 0Z" />
                </svg>
            </div>
            <h2 id="admin-confirm-title" class="text-xl font-bold text-[#20332b]">Are you sure?</h2>
            <p class="mt-2 text-sm leading-6 text-gray-600" x-text="confirmMessage"></p>
            <div class="mt-7 flex justify-center gap-3">
                <button type="button" @click="confirmOpen = false"
                    class="rounded-xl border border-gray-200 px-4 py-2.5 text-sm font-semibold text-gray-600 transition hover:bg-gray-50">
                    Cancel
                </button>
                <button type="button"
                    @click="confirmOpen = false; if (confirmAction) confirmAction()"
                    :class="confirmDanger
                        ? 'bg-rose-600 text-white hover:bg-rose-700'
                        : 'bg-gradient-to-r from-amber-300 to-orange-300 text-[#25342d] hover:from-amber-200 hover:to-orange-200'"
                    class="rounded-xl px-4 py-2.5 text-sm font-bold transition">
                    Yes, continue
                </button>
            </div>
        </section>
    </div>

    <style>
        @keyframes admin-toast-progress {
            to { transform: scaleX(0); }
        }
    </style>
    <!-- Mobile Header -->
    <div class="lg:hidden sticky top-0 z-40 flex items-center justify-between border-b border-white/10 bg-gradient-to-r from-[#1f322e] to-[#17503d] p-4 text-white shadow-md">
        <div class="flex items-center gap-2">
            <?php $admin_name = $_SESSION['admin_user'] ?? 'Admin'; ?>
            <?php if ($admin_brand_logo !== ''): ?>
                <div class="flex h-8 w-fit max-w-12 items-center justify-center overflow-hidden rounded-lg border border-white/10 bg-white">
                    <img src="../<?= htmlspecialchars($admin_brand_logo) ?>" alt="<?= htmlspecialchars($admin_brand_name) ?> logo" class="block h-full w-auto max-w-full object-contain">
                </div>
            <?php else: ?>
                <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-gradient-to-br from-amber-300 to-orange-400 font-bold text-sm text-[#25342d] shadow-sm">
                    <?= htmlspecialchars($admin_brand_initial) ?>
                </div>
            <?php endif; ?>
            <span class="font-bold text-white"><?= htmlspecialchars(ucfirst($admin_name)) ?></span>
        </div>
        <button type="button" @click="sidebarOpen = !sidebarOpen" aria-label="Toggle navigation"
            class="rounded-xl p-2 text-white/90 transition-colors hover:bg-white/10 hover:text-white">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16m-7 6h7"></path>
            </svg>
        </button>
    </div>

    <div class="flex">
        <?php include 'sidebar.php'; ?>
        <main class="flex-1 flex flex-col min-h-screen">
            <div class="admin-page-content flex-1 px-5 pt-6 sm:px-8 sm:pt-8 lg:px-12 lg:pt-12">
                <header class="sticky top-[64px] lg:top-0 z-30 -mx-5 -mt-6 mb-8 flex items-center justify-between gap-4 border-b border-gray-200/50 bg-gray-50/95 px-5 pb-4 pt-5 shadow-sm backdrop-blur-xl sm:-mx-8 sm:-mt-8 sm:px-8 sm:pt-6 lg:-mx-12 lg:-mt-12 lg:px-12 lg:pt-8">
                    <h1 class="min-w-0 truncate text-2xl font-bold text-gray-900 sm:text-3xl">
                        <?= htmlspecialchars($page_title ?? 'Admin Panel', ENT_QUOTES, 'UTF-8') ?>
                    </h1>
                    <button type="button" onclick="window.location.reload()" title="Refresh admin page"
                        aria-label="Refresh admin page"
                        class="inline-flex shrink-0 items-center gap-2 rounded-xl border border-gray-200 bg-white px-3 py-2.5 text-sm font-bold text-[#20332b] shadow-sm transition hover:border-orange-300 hover:bg-orange-50 focus:outline-none focus:ring-4 focus:ring-orange-500/15 sm:px-4">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                                d="M20 7v5h-5M4 17v-5h5m10.5-2A7.5 7.5 0 0 0 6.2 6.5L4 9m16 6-2.2 2.5A7.5 7.5 0 0 1 4.5 14" />
                        </svg>
                        <span>Refresh</span>
                    </button>
                </header>