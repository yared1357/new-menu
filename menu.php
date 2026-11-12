<?php
session_start();
require_once 'includes/db.php';

// Detect first visit vs returning visitor
$is_first_visit = !isset($_SESSION['menu_visited']);
$_SESSION['menu_visited'] = true;

$welcome_title = $is_first_visit ? 'Welcome! 👋' : 'Welcome back! 👋';
$welcome_message = $is_first_visit
    ? "Explore our full menu and find your perfect dish."
    : "Here&rsquo;s your overview for today.";

$category_id = isset($_GET['cat']) ? $_GET['cat'] : null;

// Get only active categories that have at least one AVAILABLE menu item
$categories_query = "SELECT DISTINCT c.* FROM categories c 
                    INNER JOIN menu_items mi ON c.id = mi.category_id 
                    WHERE mi.is_available = 1 AND (c.is_active = 1 OR c.is_active IS NULL)
                    ORDER BY c.name ASC";
$categories = $pdo->query($categories_query)->fetchAll();

if ($category_id) {
    $stmt = $pdo->prepare("SELECT * FROM categories WHERE id = ? AND (is_active = 1 OR is_active IS NULL)");
    $stmt->execute([$category_id]);
    $current_category = $stmt->fetch();

    if ($current_category) {
        $stmt = $pdo->prepare("SELECT * FROM menu_items WHERE category_id = ? AND is_available = 1 ORDER BY name ASC");
        $stmt->execute([$category_id]);
        $items = $stmt->fetchAll();
    } else {
        // Fallback if category is disabled or not found
        $current_category = ['name' => 'All'];
        $items = $pdo->query("SELECT mi.* FROM menu_items mi 
                              INNER JOIN categories c ON mi.category_id = c.id
                              WHERE mi.is_available = 1 AND (c.is_active = 1 OR c.is_active IS NULL)
                              ORDER BY mi.name ASC")->fetchAll();
    }
} else {
    $current_category = ['name' => 'All'];
    $items = $pdo->query("SELECT mi.* FROM menu_items mi 
                          INNER JOIN categories c ON mi.category_id = c.id
                          WHERE mi.is_available = 1 AND (c.is_active = 1 OR c.is_active IS NULL)
                          ORDER BY mi.name ASC")->fetchAll();
}

$payment_accounts = $pdo->query("SELECT provider, account_name, account_number, qr_image
                                 FROM payment_accounts
                                 WHERE is_active = 1
                                 ORDER BY provider ASC, id ASC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($current_category['name'] ?? 'Menu') ?> | Tasty Pizza & Burger</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        orange: {
                            50: '#eef7f1', 100: '#dceee2', 200: '#b8ddc6', 300: '#88c4a0',
                            400: '#56a878', 500: '#338765', 600: '#206b50', 700: '#1c553f',
                            800: '#194432', 900: '#14382a', 950: '#0b2118'
                        },
                        amber: {
                            50: '#fff9e8', 100: '#fff0c2', 200: '#ffe18a', 300: '#ffd052',
                            400: '#e8b63e', 500: '#c9952b', 600: '#a87620', 700: '#80571d',
                            800: '#67451c', 900: '#553a1c'
                        }
                    }
                }
            }
        };
    </script>
    <link
        href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&family=Playfair+Display:wght@700&display=swap"
        rel="stylesheet">
    <script defer src="assets/js/alpine.min.js"></script>
    <style>
        body {
            font-family: 'Outfit', sans-serif;
            scroll-behavior: smooth;
            background: #f7f7f2;
        }

        .font-heading {
            font-family: 'Playfair Display', serif;
        }

        .glass-nav {
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(30px);
            border-bottom: 1px solid rgba(0, 0, 0, 0.04);
        }

        .category-pill {
            transition: color 0.2s ease, background-color 0.2s ease, border-color 0.2s ease;
        }

        .category-pill.active {
            box-shadow: none;
            transform: none;
        }

        [x-cloak] {
            display: none !important;
        }

        .mobile-menu-link {
            font-size: 2rem;
            font-family: 'Playfair Display', serif;
            font-weight: 700;
            color: #1a1a1a;
            transition: all 0.5s cubic-bezier(0.16, 1, 0.3, 1);
            position: relative;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 1.5rem;
            text-transform: uppercase;
            letter-spacing: 0.2em;
        }

        .mobile-menu-link:hover {
            color: #ea580c;
            transform: translateX(10px);
        }

        .search-input:focus {
            box-shadow: 0 0 0 4px rgba(249, 115, 22, 0.08);
        }

        .menu-item-card {
            overflow: hidden;
            border: 1px solid rgba(28, 85, 63, 0.08);
            border-radius: 1.75rem;
            background: rgba(255, 255, 255, 0.92);
            box-shadow: 0 10px 28px rgba(34, 48, 42, 0.06);
            transition: transform 0.35s ease, box-shadow 0.35s ease;
        }

        .menu-item-card:hover {
            transform: translateY(-7px);
            box-shadow: 0 22px 44px rgba(34, 48, 42, 0.13);
        }

        .menu-item-card .menu-item-image {
            aspect-ratio: 1.4 / 1;
            margin-bottom: 0;
            border-radius: 1.65rem 1.65rem 0 0;
            box-shadow: none;
        }

        .menu-item-card .menu-item-details {
            padding: 1.25rem 1.1rem 1.4rem;
        }

        .menu-item-name {
            font-size: clamp(0.9rem, 1.4vw, 1.05rem);
            font-weight: 600;
            line-height: 1.35;
            letter-spacing: -0.025em;
            overflow-wrap: anywhere;
        }

        .menu-item-price {
            flex-shrink: 0;
            white-space: nowrap;
            color: #206b50;
            font-size: 0.9rem;
            font-weight: 700;
            letter-spacing: -0.025em;
        }

        .menu-item-description {
            color: #6b7280;
            font-size: 0.82rem;
            line-height: 1.6;
        }

        .menu-eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 0.55rem;
            color: #206b50;
            font-size: 0.7rem;
            font-weight: 700;
            letter-spacing: 0.16em;
        }

        .menu-page-title {
            color: #20332b;
            font-size: clamp(1.75rem, 3vw, 2.35rem);
            font-weight: 600;
            letter-spacing: -0.045em;
            line-height: 1.15;
        }

        .payment-menu-panel {
            width: min(22rem, calc(100vw - 2rem));
            max-height: min(75vh, 38rem);
            overflow-y: auto;
        }

        .sticky-category-rail {
            scrollbar-width: none;
            -ms-overflow-style: none;
        }

        .sticky-category-rail::-webkit-scrollbar {
            display: none;
        }

        .reveal {
            opacity: 0;
            transform: translateY(30px);
            animation: revealFade 0.8s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }

        @keyframes revealFade {
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .delay-1 {
            animation-delay: 0.2s;
        }

        .delay-2 {
            animation-delay: 0.4s;
        }
    </style>
</head>

<body class="bg-[#f7f7f2] text-[#20332b]"
    x-data="{ mobileMenu: false, scrolled: false, searchQuery: '', dietFilter: 'all', paymentOpen: false, selectedPaymentQr: '', scrollCategoryRail(direction) { const rail = this.$refs.categoryRail; const scrollAmount = 200; if (direction === 'left') { rail.scrollLeft -= scrollAmount; } else { rail.scrollLeft += scrollAmount; } } }"
    @scroll.window="scrolled = window.pageYOffset > 50">



    <!-- ====================== HEADER + MOBILE MENU ====================== -->
    <header
        class="glass-nav bg-gradient-to-r from-orange-500/95 to-orange-400/95 backdrop-blur-xl sticky top-0 z-[150] py-3 md:py-4 border-b border-orange-400/30 transition-shadow duration-300"
        :class="scrolled ? 'shadow-2xl shadow-orange-500/30' : 'shadow-lg'">

        <div class="container mx-auto px-4 sm:px-6 lg:px-12">
            <div class="flex justify-between items-center">
                <!-- Logo -->
                <a href="index.php" class="flex items-center gap-2 sm:gap-3 md:gap-4 relative z-[120] group">
                    <div
                        class="w-9 h-9 sm:w-10 sm:h-11 md:w-12 md:h-12 bg-white rounded-2xl flex items-center justify-center shadow-xl shadow-black/20 group-hover:scale-105 transition-transform duration-400 border-2 border-white/50">
                        <span class="text-orange-600 font-black text-lg sm:text-xl md:text-2xl">T</span>
                    </div>
                    <div class="flex flex-col leading-tight">
                        <span
                            class="text-base sm:text-lg md:text-2xl font-black tracking-tight uppercase text-white">Tasty<span
                                class="text-orange-100">Pizza</span></span>
                        <span
                            class="text-[7px] sm:text-[8px] md:text-[10px] font-black uppercase tracking-[0.4em] text-orange-100 mt-0.5">
                            Digital Menu</span>
                    </div>
                </a>

                <!-- Desktop payment menu -->
                <div class="hidden md:flex items-center gap-5 lg:gap-6">
                    <div class="relative" @click.away="paymentOpen = false">
                        <button type="button" @click="paymentOpen = !paymentOpen" :aria-expanded="paymentOpen"
                            class="inline-flex items-center gap-2 rounded-full border border-white/30 bg-white/10 px-4 py-2.5 text-xs font-bold text-white transition-colors hover:bg-white/20">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                                    d="M3 7h18M5 4h14a2 2 0 012 2v12a2 2 0 01-2 2H5a2 2 0 01-2-2V6a2 2 0 012-2zm1 13h5" />
                            </svg>
                            Payment
                            <svg class="h-3.5 w-3.5 transition-transform" :class="paymentOpen ? 'rotate-180' : ''"
                                fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>
                        <div x-show="paymentOpen" x-cloak
                            class="payment-menu-panel absolute right-0 top-full z-[220] mt-3 rounded-2xl border border-gray-100 bg-white p-4 text-left text-gray-800 shadow-2xl">
                            <h2 class="mb-3 text-xs font-bold uppercase tracking-wider text-gray-500">Payment accounts</h2>
                            <?php if (!$payment_accounts): ?>
                                <p class="rounded-xl bg-gray-50 p-4 text-sm text-gray-500">Payment details are not available yet.</p>
                            <?php else: ?>
                                <div class="space-y-3">
                                    <?php foreach ($payment_accounts as $account): ?>
                                        <article class="rounded-xl border border-gray-100 p-3">
                                            <h3 class="font-bold text-gray-900"><?= htmlspecialchars($account['provider']) ?></h3>
                                            <p class="mt-1 text-xs text-gray-500"><?= htmlspecialchars($account['account_name']) ?></p>
                                            <p class="mt-1 break-all font-semibold text-gray-800"><?= htmlspecialchars($account['account_number']) ?></p>
                                            <?php if (!empty($account['qr_image'])): ?>
                                                <button type="button"
                                                    @click.stop="selectedPaymentQr = '<?= htmlspecialchars($account['qr_image'], ENT_QUOTES) ?>'"
                                                    class="mt-3 flex w-full items-center gap-3 rounded-lg bg-gray-50 p-2 text-left hover:bg-emerald-50">
                                                    <img src="<?= htmlspecialchars($account['qr_image']) ?>"
                                                        alt="QR code for <?= htmlspecialchars($account['provider']) ?>"
                                                        class="h-14 w-14 rounded-md bg-white object-contain p-1">
                                                    <span class="text-xs font-semibold text-emerald-800">View QR code</span>
                                                </button>
                                            <?php endif; ?>
                                        </article>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="hidden">
                        <a href="https://facebook.com/yourpage" target="_blank"
                            class="text-white/80 hover:text-orange-600 transition-colors duration-200">
                            <svg class="w-6 h-6 lg:w-7 lg:h-7 transition-all" fill="currentColor" viewBox="0 0 24 24">
                                <path
                                    d="M22 12c0-5.523-4.477-10-10-10S2 6.477 2 12c0 4.991 3.657 9.128 8.438 9.879v-6.987h-2.54V12h2.54V9.797c0-2.506 1.492-3.89 3.777-3.89 1.094 0 2.238.195 2.238.195v2.46h-1.26c-1.243 0-1.63.771-1.63 1.562V12h2.773l-.443 2.89h-2.33v6.989C18.343 21.129 22 16.992 22 12z" />
                            </svg>
                        </a>
                        <a href="https://instagram.com/yourpage" target="_blank"
                            class="text-white/80 hover:text-orange-600 transition-colors duration-200">
                            <svg class="w-6 h-6 lg:w-7 lg:h-7 transition-all" fill="currentColor" viewBox="0 0 24 24">
                                <path
                                    d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-2.97 0-3.375.014-4.595.074-1.219.06-2.05.25-2.775.533-.75.29-1.386.676-1.92 1.21-.534.534-.92 1.17-1.21 1.92-.283.725-.473 1.556-.533 2.775-.06 1.22-.074 1.625-.074 4.595s.014 3.375.074 4.595c.06 1.117.25 2.05.533 2.775.29.75.676 1.386 1.21 1.92.534.534 1.17.92 1.92 1.21.725.283 1.556.473 2.775.533 1.22.06 1.625.074 4.595.074s3.375-.014 4.595-.074c1.219-.06 2.05-.25 2.775-.533.75-.29 1.386-.676 1.92-1.21.534-.534.92-1.17 1.21-1.92.283-.725.473-1.556.533-2.775.06-1.22.074-1.625.074-4.595s-.014-3.375-.074-4.595c-.06-1.117-.25-2.05-.533-2.775-.29-.75-.676-1.386-1.21-1.92-.534-.534-1.17-.92-1.92-1.21-.725-.283-1.556-.473-2.775-.533-1.22-.06-1.625-.074-4.595-.074zm0 1.8c2.917 0 3.266.011 4.47.07 1.117.05 1.722.238 2.124.395.534.208.915.455 1.315.855.4.4.647.781.855 1.315.157.402.345 1.007.395 2.124.059 1.204.07 1.553.07 4.47s-.011 3.266-.07 4.47c-.05 1.117-.238 1.722-.395 2.124-.208.534-.455.915-.855 1.315-.4.4-.781.647-1.315.855-.402.157-1.007.345-2.124.395-1.204.059-1.553.07-4.47.07s-3.266-.011-4.47-.07c-1.117-.05-1.722-.238-2.124-.395-.534-.208-.915-.455-1.315-.855-.4-.4-.647-.781-.855-1.315-.157-.402-.345-1.007-.395-2.124-.059-1.204-.07-1.553-.07-4.47s.011-3.266.07-4.47c.05-1.117.238-1.722.395-2.124.208-.534.455-.915.855-1.315.4-.4.781-.647 1.315-.855.402-.157 1.007-.345 2.124-.395 1.204-.059 1.553-.07 4.47-.07zm0 3.238c-2.551 0-4.623 2.072-4.623 4.623s2.072 4.623 4.623 4.623 4.623-2.072 4.623-4.623-2.072-4.623-4.623-4.623zm0 7.623c-1.657 0-3-1.343-3-3s1.343-3 3-3 3 1.343 3 3-1.343 3-3 3zm4.865-12.845c-.596 0-1.08.484-1.08 1.08s.484 1.08 1.08 1.08 1.08-.484 1.08-1.08-.484-1.08-1.08-1.08z" />
                            </svg>
                        </a>
                        <a href="https://tiktok.com/@yourprofile" target="_blank"
                            class="text-white/80 hover:text-orange-600 transition-colors duration-200">
                            <svg class="w-6 h-6 lg:w-7 lg:h-7 transition-all" fill="currentColor" viewBox="0 0 24 24">
                                <path
                                    d="M12.525.02c1.31-.02 2.61-.01 3.91-.02.08 1.53.63 3.09 1.75 4.17 1.12 1.11 2.7 1.62 4.24 1.79v4.03c-1.44-.05-2.89-.35-4.2-.97-.57-.26-1.1-.59-1.62-.93-.01 2.92.01 5.84-.02 8.75-.08 1.4-.54 2.79-1.35 3.94-1.31 1.92-3.58 3.17-5.91 3.21-1.43.08-2.86-.31-4.08-1.03-2.02-1.19-3.44-3.37-3.65-5.71-.02-.5-.03-1-.01-1.49.18-1.9 1.12-3.72 2.58-4.96 1.66-1.44 3.98-2.13 6.15-1.72.02 1.48-.04 2.96-.04 4.44-.98-.32-2.15-.23-3.02.37-.63.41-1.11 1.04-1.36 1.75-.21.51-.15 1.07-.14 1.61.24 1.64 1.82 3.02 3.5 2.87 1.12-.12 2.19-.66 2.77-1.61.19-.33.4-.67.41-1.06.1-1.79.06-3.57.07-5.36.01-4.03-.01-8.05.02-12.07z" />
                            </svg>
                        </a>
                    </div>
                </div>

                <!-- Mobile payment menu -->
                <div class="md:hidden flex items-center gap-2">
                    <div class="relative" @click.away="paymentOpen = false">
                        <button type="button" @click="paymentOpen = !paymentOpen" :aria-expanded="paymentOpen"
                            class="rounded-full border border-white/30 bg-white/10 px-3 py-2 text-xs font-bold text-white"
                            aria-label="Payment accounts">Pay</button>
                        <div x-show="paymentOpen" x-cloak
                            class="payment-menu-panel absolute right-0 top-full z-[220] mt-3 rounded-2xl border border-gray-100 bg-white p-4 text-left text-gray-800 shadow-2xl">
                            <h2 class="mb-3 text-xs font-bold uppercase tracking-wider text-gray-500">Payment accounts</h2>
                            <?php if (!$payment_accounts): ?>
                                <p class="rounded-xl bg-gray-50 p-4 text-sm text-gray-500">Payment details are not available yet.</p>
                            <?php else: ?>
                                <div class="space-y-3">
                                    <?php foreach ($payment_accounts as $account): ?>
                                        <article class="rounded-xl border border-gray-100 p-3">
                                            <h3 class="font-bold text-gray-900"><?= htmlspecialchars($account['provider']) ?></h3>
                                            <p class="mt-1 text-xs text-gray-500"><?= htmlspecialchars($account['account_name']) ?></p>
                                            <p class="mt-1 break-all font-semibold text-gray-800"><?= htmlspecialchars($account['account_number']) ?></p>
                                            <?php if (!empty($account['qr_image'])): ?>
                                                <button type="button"
                                                    @click.stop="selectedPaymentQr = '<?= htmlspecialchars($account['qr_image'], ENT_QUOTES) ?>'"
                                                    class="mt-3 flex w-full items-center gap-3 rounded-lg bg-gray-50 p-2 text-left hover:bg-emerald-50">
                                                    <img src="<?= htmlspecialchars($account['qr_image']) ?>"
                                                        alt="QR code for <?= htmlspecialchars($account['provider']) ?>"
                                                        class="h-14 w-14 rounded-md bg-white object-contain p-1">
                                                    <span class="text-xs font-semibold text-emerald-800">View QR code</span>
                                                </button>
                                            <?php endif; ?>
                                        </article>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="hidden">
                        <a href="https://facebook.com/yourpage" target="_blank"
                            class="text-white/80 hover:text-white transition-colors duration-200">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                <path
                                    d="M22 12c0-5.523-4.477-10-10-10S2 6.477 2 12c0 4.991 3.657 9.128 8.438 9.879v-6.987h-2.54V12h2.54V9.797c0-2.506 1.492-3.89 3.777-3.89 1.094 0 2.238.195 2.238.195v2.46h-1.26c-1.243 0-1.63.771-1.63 1.562V12h2.773l-.443 2.89h-2.33v6.989C18.343 21.129 22 16.992 22 12z" />
                            </svg>
                        </a>
                        <a href="https://instagram.com/yourpage" target="_blank"
                            class="text-white/80 hover:text-white transition-colors duration-200">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                <path
                                    d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-2.97 0-3.375.014-4.595.074-1.219.06-2.05.25-2.775.533-.75.29-1.386.676-1.92 1.21-.534.534-.92 1.17-1.21 1.92-.283.725-.473 1.556-.533 2.775-.06 1.22-.074 1.625-.074 4.595s.014 3.375.074 4.595c.06 1.117.25 2.05.533 2.775.29.75.676 1.386 1.21 1.92.534.534 1.17.92 1.92 1.21.725.283 1.556.473 2.775.533 1.22.06 1.625.074 4.595.074s3.375-.014 4.595-.074c1.219-.06 2.05-.25 2.775-.533.75-.29 1.386-.676 1.92-1.21.534-.534.92-1.17 1.21-1.92.283-.725.473-1.556.533-2.775.06-1.22.074-1.625.074-4.595s-.014-3.375-.074-4.595c-.06-1.117-.25-2.05-.533-2.775-.29-.75-.676-1.386-1.21-1.92-.534-.534-1.17-.92-1.92-1.21-.725-.283-1.556-.473-2.775-.533-1.22-.06-1.625-.074-4.595-.074zm0 1.8c2.917 0 3.266.011 4.47.07 1.117.05 1.722.238 2.124.395.534.208.915.455 1.315.855.4.4.647.781.855 1.315.157.402.345 1.007.395 2.124.059 1.204.07 1.553.07 4.47s-.011 3.266-.07 4.47c-.05 1.117-.238 1.722-.395 2.124-.208.534-.455.915-.855 1.315-.4.4-.781.647-1.315.855-.402.157-1.007.345-2.124.395-1.204.059-1.553.07-4.47.07s-3.266-.011-4.47-.07c-1.117-.05-1.722-.238-2.124-.395-.534-.208-.915-.455-1.315-.855-.4-.4-.647-.781-.855-1.315-.157-.402-.345-1.007-.395-2.124-.059-1.204-.07-1.553-.07-4.47s.011-3.266.07-4.47c.05-1.117.238-1.722.395-2.124.208-.534.455-.915.855-1.315.4-.4.781-.647 1.315-.855.402-.157 1.007-.345 2.124-.395 1.204-.059 1.553-.07 4.47-.07zm0 3.238c-2.551 0-4.623 2.072-4.623 4.623s2.072 4.623 4.623 4.623 4.623-2.072 4.623-4.623-2.072-4.623-4.623-4.623zm0 7.623c-1.657 0-3-1.343-3-3s1.343-3 3-3 3 1.343 3 3-1.343 3-3 3zm4.865-12.845c-.596 0-1.08.484-1.08 1.08s.484 1.08 1.08 1.08 1.08-.484 1.08-1.08-.484-1.08-1.08-1.08z" />
                            </svg>
                        </a>
                        <a href="https://tiktok.com/@yourprofile" target="_blank"
                            class="text-white/80 hover:text-white transition-colors duration-200">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                <path
                                    d="M12.525.02c1.31-.02 2.61-.01 3.91-.02.08 1.53.63 3.09 1.75 4.17 1.12 1.11 2.7 1.62 4.24 1.79v4.03c-1.44-.05-2.89-.35-4.2-.97-.57-.26-1.1-.59-1.62-.93-.01 2.92.01 5.84-.02 8.75-.08 1.4-.54 2.79-1.35 3.94-1.31 1.92-3.58 3.17-5.91 3.21-1.43.08-2.86-.31-4.08-1.03-2.02-1.19-3.44-3.37-3.65-5.71-.02-.5-.03-1-.01-1.49.18-1.9 1.12-3.72 2.58-4.96 1.66-1.44 3.98-2.13 6.15-1.72.02 1.48-.04 2.96-.04 4.44-.98-.32-2.15-.23-3.02.37-.63.41-1.11 1.04-1.36 1.75-.21.51-.15 1.07-.14 1.61.24 1.64 1.82 3.02 3.5 2.87 1.12-.12 2.19-.66 2.77-1.61.19-.33.4-.67.41-1.06.1-1.79.06-3.57.07-5.36.01-4.03-.01-8.05.02-12.07z" />
                            </svg>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Category rail (visible on all screens) -->
            <div class="border-t border-gray-100/60 pt-5 mt-3">
                <div class="relative">
                    <button @click="scrollCategoryRail('left')"
                        class="absolute left-0 top-1/2 -translate-y-1/2 z-10 bg-white/90 backdrop-blur rounded-full p-2 shadow md:hidden">
                        <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                        </svg>
                    </button>

                    <div x-ref="categoryRail" class="flex gap-3 overflow-x-auto pb-3 scrollbar-hide px-4 md:px-0">
                        <a href="index.php"
                            class="category-pill whitespace-nowrap px-6 py-3 rounded-full text-xs font-black uppercase tracking-wider border transition-all <?= !$category_id ? 'bg-orange-600 text-white border-orange-600' : 'bg-white text-gray-600 border-gray-200 hover:border-orange-400 hover:text-orange-600' ?>">
                            All
                        </a>

                        <!-- Diet Filters (Dynamic) -->
                        <button @click="dietFilter = dietFilter === '1' ? 'all' : '1'"
                            :class="dietFilter === '1' ? 'bg-emerald-500 text-white border-emerald-500 shadow-md shadow-emerald-500/20' : 'bg-white text-emerald-600 border-emerald-100 hover:border-emerald-500'"
                            class="category-pill whitespace-nowrap px-6 py-3 rounded-full text-xs font-black uppercase tracking-wider border transition-all flex items-center gap-1.5 focus:outline-none">
                            <svg class="w-3.5 h-3.5" x-show="dietFilter === '1'" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3"
                                    d="M5 13l4 4L19 7"></path>
                            </svg>
                            Fasting
                        </button>

                        <button @click="dietFilter = dietFilter === '0' ? 'all' : '0'"
                            :class="dietFilter === '0' ? 'bg-rose-500 text-white border-rose-500 shadow-md shadow-rose-500/20' : 'bg-white text-rose-600 border-rose-100 hover:border-rose-500'"
                            class="category-pill whitespace-nowrap px-6 py-3 rounded-full text-xs font-black uppercase tracking-wider border transition-all flex items-center gap-1.5 focus:outline-none">
                            <span class="w-2.5 h-2.5 rounded-full"
                                :class="dietFilter === '0' ? 'bg-white animate-pulse' : 'bg-rose-500'"></span>
                            Non-Fasting
                        </button>

                        <div class="w-px bg-gray-200 my-2 mx-1 hidden md:block"></div>
                        <?php foreach ($categories as $cat): ?>
                            <a href="index.php?cat=<?= $cat['id'] ?>"
                                class="category-pill whitespace-nowrap px-6 py-3 rounded-full text-xs font-black uppercase tracking-wider border transition-all <?= $category_id == $cat['id'] ? 'bg-orange-600 text-white border-orange-600' : 'bg-white text-gray-600 border-gray-200 hover:border-orange-400 hover:text-orange-600' ?>">
                                <?= htmlspecialchars($cat['name']) ?>
                            </a>
                        <?php endforeach; ?>
                    </div>

                    <button @click="scrollCategoryRail('right')"
                        class="absolute right-0 top-1/2 -translate-y-1/2 z-10 bg-white/90 backdrop-blur rounded-full p-2 shadow md:hidden">
                        <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    </header>

    <div x-show="selectedPaymentQr" x-cloak x-transition @click.self="selectedPaymentQr = ''"
        @keydown.escape.window="selectedPaymentQr = ''"
        class="fixed inset-0 z-[300] flex items-center justify-center bg-gray-950/80 p-4"
        role="dialog" aria-modal="true" aria-label="Payment QR code">
        <button type="button" @click="selectedPaymentQr = ''"
            class="absolute right-5 top-5 rounded-full bg-white/15 px-4 py-2 text-sm font-bold text-white hover:bg-white/25">
            Close
        </button>
        <img :src="selectedPaymentQr" alt="Payment QR code"
            class="max-h-[85vh] max-w-[min(90vw,36rem)] rounded-2xl bg-white p-3 object-contain shadow-2xl">
    </div>

    <!-- ====================== MOBILE FULL-SCREEN MENU (Enhanced) ====================== -->
    <div x-show="mobileMenu" x-cloak x-transition:enter="transition ease-out duration-500"
        x-transition:enter-start="opacity-0 scale-105" x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-400" x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-105"
        class="fixed inset-0 bg-gradient-to-br from-orange-600 via-orange-500 to-orange-700 text-white z-[140] flex flex-col items-center justify-between px-6 overflow-hidden">

        <!-- Animated background elements -->
        <div class="absolute inset-0 overflow-hidden">
            <div class="absolute -top-20 -right-20 w-64 h-64 bg-white/10 rounded-full blur-3xl animate-pulse"></div>
            <div class="absolute -bottom-20 -left-20 w-64 h-64 bg-white/10 rounded-full blur-3xl animate-pulse"
                style="animation-delay: 1s"></div>
            <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-96 h-96 bg-white/5 rounded-full blur-3xl animate-pulse"
                style="animation-delay: 2s"></div>
        </div>

        <!-- Close button -->
        <button @click="mobileMenu = false"
            class="absolute top-6 right-6 text-5xl font-light leading-none hover:text-orange-200 transition-all duration-300 z-50 focus:outline-none hover:rotate-90">
            ×
        </button>

        <!-- Logo at top -->
        <div class="mt-20 sm:mt-24 flex flex-col items-center relative z-10 hidden">
            <div
                class="w-20 h-20 bg-white rounded-3xl flex items-center justify-center shadow-2xl mb-6 border-4 border-white/50 hover:scale-110 transition-transform duration-300">
                <span class="text-orange-600 font-black text-3xl">T</span>
            </div>
            <h2 class="text-3xl sm:text-4xl font-black tracking-tight uppercase text-white">Tasty<span
                    class="text-orange-100">Pizza</span></h2>
            <p class="text-orange-100 text-sm mt-2 font-medium">Digital Menu</p>
        </div>



        <!-- Bottom social icons + line -->
        <div class="pb-16 sm:pb-20 w-full max-w-xs flex flex-col items-center relative z-10">
            <div
                class="w-24 h-1 bg-gradient-to-r from-transparent via-white/40 to-transparent mx-auto mb-12 rounded-full">
            </div>

            <div class="flex justify-center gap-8 sm:gap-10">
                <a href="https://facebook.com/yourpage" target="_blank"
                    class="w-16 h-16 sm:w-18 sm:h-18 flex items-center justify-center rounded-full bg-white/10 backdrop-blur-sm border-2 border-white/20 hover:bg-white hover:text-orange-600 transition-all duration-300 hover:scale-110 hover:rotate-6">
                    <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 24 24">
                        <path
                            d="M22 12c0-5.523-4.477-10-10-10S2 6.477 2 12c0 4.991 3.657 9.128 8.438 9.879v-6.987h-2.54V12h2.54V9.797c0-2.506 1.492-3.89 3.777-3.89 1.094 0 2.238.195 2.238.195v2.46h-1.26c-1.243 0-1.63.771-1.63 1.562V12h2.773l-.443 2.89h-2.33v6.989C18.343 21.129 22 16.992 22 12z" />
                    </svg>
                </a>
                <a href="https://instagram.com/yourpage" target="_blank"
                    class="w-16 h-16 sm:w-18 sm:h-18 flex items-center justify-center rounded-full bg-white/10 backdrop-blur-sm border-2 border-white/20 hover:bg-white hover:text-orange-600 transition-all duration-300 hover:scale-110 hover:rotate-6">
                    <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 24 24">
                        <path
                            d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069z" />
                    </svg>
                </a>
                <a href="https://tiktok.com/@yourprofile" target="_blank"
                    class="w-16 h-16 sm:w-18 sm:h-18 flex items-center justify-center rounded-full bg-white/10 backdrop-blur-sm border-2 border-white/20 hover:bg-white hover:text-orange-600 transition-all duration-300 hover:scale-110 hover:rotate-6">
                    <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 24 24">
                        <path
                            d="M19 0h-14c-2.761 0-5 2.239-5 5v14c0 2.761 2.239 5 5 5h14c2.762 0 5-2.239 5-5v-14c0-2.761-2.238-5-5-5zm-11 19h-3v-11h3v11zm-1.5-12.268c-.966 0-1.75-.79-1.75-1.764s.784-1.764 1.75-1.764 1.75.79 1.75 1.764-.783 1.764-1.75 1.764zm13.5 12.268h-3v-5.604c0-3.368-4-3.113-4 0v5.604h-3v-11h3v1.765c1.396-2.586 7-2.777 7 2.476v6.759z" />
                    </svg>
                </a>
            </div>
        </div>
    </div>
    <main class="pt-16 pb-32 md:pt-20 md:pb-48 min-h-screen">
        <div class="container mx-auto px-6 lg:px-12">
            <!-- Search & Page Title -->
            <div class="flex flex-col lg:flex-row justify-between items-center mb-12 md:mb-16 gap-4 lg:gap-8">
                <div class="reveal flex-1 text-center lg:text-left">
                    <p class="menu-eyebrow uppercase mb-2">
                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500" aria-hidden="true"></span>
                        Fresh from our kitchen
                    </p>
                    <h1 class="menu-page-title mb-0">
                        <?= htmlspecialchars($current_category['name'] ?? 'The Vault') ?>
                    </h1>
                </div>

                <div class="w-full lg:w-96 relative group reveal delay-1">
                    <div class="absolute inset-y-0 left-0 pl-5 flex items-center pointer-events-none">
                        <svg class="w-4 h-4 text-gray-400 group-focus-within:text-orange-500 transition-colors"
                            fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                    </div>
                    <input type="text" placeholder="Search Masterpieces..." x-model="searchQuery"
                        class="w-full pl-12 pr-6 py-3 lg:py-4 bg-white border border-gray-100 rounded-full text-xs focus:outline-none focus:border-orange-500 focus:ring-4 focus:ring-orange-500/5 transition-all shadow-sm">
                </div>
            </div>

            <!-- Product Grid -->
            <div
                class="grid grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 2xl:grid-cols-6 gap-x-4 sm:gap-x-8 gap-y-8 sm:gap-y-12">
                <?php if (empty($items)): ?>
                    <div class="col-span-full py-40 text-center reveal">
                        <div
                            class="w-24 h-24 bg-gray-50 rounded-[2rem] flex items-center justify-center mx-auto mb-8 shadow-inner">
                            <span class="text-4xl">👨‍🍳</span>
                        </div>
                        <h3 class="text-2xl font-bold text-gray-900 mb-2">Refining the Selection</h3>
                        <p class="text-gray-400">Our chefs are currently curating new delicacies for this collection.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($items as $index => $item): ?>
                        <div class="group relative flex flex-col h-full reveal menu-item-card" style="animation-delay: <?= $index * 0.05 ?>s"
                            x-show="(searchQuery === '' || '<?= addslashes(strtolower($item['name'])) ?>'.includes(searchQuery.toLowerCase())) && (dietFilter === 'all' || dietFilter === '<?= isset($item['is_fasting']) ? $item['is_fasting'] : '0' ?>')">
                            <!-- Image Container -->
                            <div
                                class="menu-item-image rounded-[3rem] overflow-hidden relative mb-8 shadow-sm group-hover:shadow-2xl transition-all duration-500">
                                <img src="<?= $item['image_url'] ?: 'https://images.unsplash.com/photo-1546069901-ba9599a7e63c?auto=format&fit=crop&q=80&w=800' ?>"
                                    class="w-full h-full object-cover transition-transform duration-[2s] group-hover:scale-110"
                                    alt="<?= htmlspecialchars($item['name']) ?>">

                                <!-- Dietary Badge Overlay -->
                                <div class="absolute top-4 left-4 z-10 transition-transform duration-500 group-hover:scale-105">
                                    <?php if (isset($item['is_fasting']) && $item['is_fasting']): ?>
                                        <span
                                            class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-2xl bg-emerald-500/40 backdrop-blur-xl text-white text-[9px] font-black uppercase tracking-[0.2em] shadow-lg shadow-emerald-500/20 border border-white/30">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3"
                                                    d="M5 13l4 4L19 7"></path>
                                            </svg>
                                            Fasting
                                        </span>
                                    <?php else: ?>
                                        <span
                                            class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-2xl bg-rose-600/40 backdrop-blur-xl text-white text-[9px] font-black uppercase tracking-[0.2em] shadow-lg shadow-rose-600/20 border border-white/30">
                                            <span class="w-2 h-2 bg-white rounded-full animate-pulse"></span>
                                            Non-Fasting
                                        </span>
                                    <?php endif; ?>
                                </div>

                                <!-- Subtle base shadow that deepens on hover -->
                                <div
                                    class="absolute inset-0 bg-gradient-to-t from-black/50 via-black/5 to-transparent opacity-30 group-hover:opacity-80 transition-opacity duration-500 pointer-events-none z-0">
                                </div>
                            </div>

                            <!-- Details -->
                            <div class="menu-item-details flex-1 px-4 flex flex-col">
                                <div class="flex justify-between items-start mb-3">
                                    <h3
                                        class="menu-item-name text-gray-900 group-hover:text-orange-700 transition-colors min-w-0 pr-2">
                                        <?= htmlspecialchars($item['name']) ?>
                                    </h3>
                                    <span class="menu-item-price">ETB
                                        <?= number_format($item['price'], 2) ?></span>
                                </div>



                                <?php $description = trim((string) $item['description']); ?>
                                <?php if (mb_strlen($description) > 72): ?>
                                    <div x-data="{ expanded: false }">
                                        <div class="flex items-start justify-between gap-3 mb-4" x-show="!expanded">
                                            <p class="menu-item-description flex-1">
                                                <?= htmlspecialchars(mb_substr($description, 0, 72)) ?>…
                                            </p>
                                            <button @click="expanded = !expanded"
                                                class="shrink-0 text-[11px] font-semibold text-orange-700 hover:text-orange-900 transition-colors">
                                                More
                                            </button>
                                        </div>
                                        <div x-show="expanded" x-cloak>
                                            <p class="menu-item-description mb-3">
                                                <?= htmlspecialchars($description) ?>
                                            </p>
                                            <button @click="expanded = !expanded"
                                                class="text-[11px] font-semibold text-orange-700 hover:text-orange-900 transition-colors">
                                                Show less
                                            </button>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <p class="menu-item-description mb-4">
                                        <?= htmlspecialchars($description) ?>
                                    </p>
                                <?php endif; ?>


                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <footer class="mt-10 border-t border-orange-300/40 bg-gradient-to-r from-[#1f322e] via-[#153b34] to-[#3a2314] text-white shadow-[0_-12px_30px_rgba(0,0,0,0.08)]">
        <div class="container mx-auto px-4 py-8 sm:px-6 lg:px-12">
            <div class="flex flex-col gap-6 md:flex-row md:items-center md:justify-between">
                <div class="text-center md:text-left">
                    <span class="inline-flex items-center rounded-full border border-white/20 bg-white/5 px-3 py-1 text-[10px] font-bold uppercase tracking-[0.22em] text-orange-100">
                        Fresh from our kitchen
                    </span>
                    <p class="mt-3 text-lg font-semibold text-white sm:text-xl">Tasty Pizza & Burger</p>
                </div>

                <div class="flex items-center justify-center gap-3 md:justify-end">
                    <a href="https://facebook.com" target="_blank" rel="noopener noreferrer" aria-label="Facebook"
                        class="flex h-11 w-11 items-center justify-center rounded-full border border-white/20 bg-white/5 text-white transition-all duration-200 hover:-translate-y-0.5 hover:bg-white hover:text-orange-600">
                        <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M13.5 22v-8h2.5l.4-3h-2.9V7.5c0-.9.3-1.5 1.6-1.5H16V3.1c-.3-.1-1.2-.1-2.3-.1-2.3 0-3.9 1.4-3.9 4V11H7.5v3h2.3v8h3.7Z"/>
                        </svg>
                    </a>
                    <a href="https://instagram.com" target="_blank" rel="noopener noreferrer" aria-label="Instagram"
                        class="flex h-11 w-11 items-center justify-center rounded-full border border-white/20 bg-white/5 text-white transition-all duration-200 hover:-translate-y-0.5 hover:bg-white hover:text-orange-600">
                        <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M7 2h10a5 5 0 0 1 5 5v10a5 5 0 0 1-5 5H7a5 5 0 0 1-5-5V7a5 5 0 0 1 5-5Zm0 2a3 3 0 0 0-3 3v10a3 3 0 0 0 3 3h10a3 3 0 0 0 3-3V7a3 3 0 0 0-3-3H7Zm5 3.2A4.8 4.8 0 1 1 7.2 12 4.8 4.8 0 0 1 12 7.2Zm0 2A2.8 2.8 0 1 0 14.8 12 2.8 2.8 0 0 0 12 9.2Zm5.2-3.1a1.1 1.1 0 1 1-1.1 1.1 1.1 1.1 0 0 1 1.1-1.1Z"/>
                        </svg>
                    </a>
                    <a href="https://tiktok.com" target="_blank" rel="noopener noreferrer" aria-label="TikTok"
                        class="flex h-11 w-11 items-center justify-center rounded-full border border-white/20 bg-white/5 text-white transition-all duration-200 hover:-translate-y-0.5 hover:bg-white hover:text-orange-600">
                        <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M16 3c.6 1.8 2 3 4 3v2.7c-1.3 0-2.4-.2-3.4-.8V13a5.5 5.5 0 1 1-5.5-5.5c.3 0 .6.1.9.1v2.8a3.1 3.1 0 0 0-.9-.1A3.1 3.1 0 0 0 7 13a3.1 3.1 0 0 0 3.1 3.1A3.1 3.1 0 0 0 13.2 13V3h2.8Z"/>
                        </svg>
                    </a>
                </div>

                <div class="text-center md:text-right">
                    <p class="text-xs font-medium uppercase tracking-[0.2em] text-orange-100/90">Open daily</p>
                    <p class="mt-2 text-sm text-white/80">Fresh meals, warm service, and a great dining vibe.</p>
                </div>
            </div>

            <div class="mt-6 border-t border-white/10 pt-4 text-center text-xs text-orange-100/80 sm:text-sm">
                <p>Copyrights &copy; 2026 Tasty Pizza & Burger. Developed By Y-Global System Solution All rights reserved.</p>
            </div>
        </div>
    </footer>

    <!-- Floating Feedback Trigger -->
    <div x-data="{
            feedbackOpen: <?= (isset($_GET['status']) && $_GET['status'] == 'success') ? 'true' : 'false' ?>,
            showSuccess: <?= (isset($_GET['status']) && $_GET['status'] == 'success') ? 'true' : 'false' ?>,
            stars: 0
         }"
        x-init="if(showSuccess) { setTimeout(() => { showSuccess = false; feedbackOpen = false; history.replaceState(null, '', window.location.pathname); }, 3000) }"
        class="fixed bottom-8 right-8 z-[200]">

        <!-- Floating Feedback Button -->
        <button @click="feedbackOpen = !feedbackOpen"
            class="w-16 h-16 bg-gradient-to-br from-orange-500 to-orange-700 text-white rounded-full shadow-2xl shadow-orange-500/40 flex items-center justify-center hover:scale-110 transition-all duration-300 active:scale-95 relative">
            <!-- Pulse ring -->
            <span class="absolute inset-0 rounded-full animate-ping bg-orange-400 opacity-20"></span>
            <!-- Chat icon -->
            <svg x-show="!feedbackOpen" x-transition class="w-7 h-7 relative z-10" fill="none" stroke="currentColor"
                viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z">
                </path>
            </svg>
            <!-- Close icon -->
            <svg x-show="feedbackOpen" x-transition class="w-7 h-7 relative z-10" fill="none" stroke="currentColor"
                viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
            <!-- Success badge -->
            <template x-if="showSuccess">
                <span
                    class="absolute -top-1 -right-1 w-5 h-5 bg-emerald-500 rounded-full border-2 border-white animate-bounce"></span>
            </template>
        </button>

        <!-- Tooltip label -->
        <div x-show="!feedbackOpen" x-transition
            class="absolute bottom-0 right-20 whitespace-nowrap bg-orange-600 text-white text-[9px] font-black uppercase tracking-widest px-4 py-2 rounded-full shadow-xl pointer-events-none">
            Feed Back
        </div>

        <!-- Feedback Form Panel -->
        <div x-show="feedbackOpen" x-transition:enter="transition ease-out duration-300 transform"
            x-transition:enter-start="opacity-0 translate-y-10 scale-90"
            x-transition:enter-end="opacity-100 translate-y-0 scale-100"
            x-transition:leave="transition ease-in duration-200 transform"
            x-transition:leave-start="opacity-100 translate-y-0 scale-100"
            x-transition:leave-end="opacity-0 translate-y-10 scale-90" @click.away="feedbackOpen = false"
            class="absolute bottom-20 right-0 w-72 sm:w-80 bg-white/95 backdrop-blur-2xl rounded-3xl overflow-hidden shadow-[0_20px_60px_rgba(0,0,0,0.12)] border border-gray-100 max-h-[80vh] overflow-y-auto"
            x-cloak>

            <div class="p-6 text-gray-900 text-center">
                <!-- Header -->
                <div class="flex items-center justify-center gap-3 mb-4">
                    <div
                        class="w-10 h-10 bg-gradient-to-br from-orange-500 to-orange-700 rounded-2xl flex items-center justify-center shadow-lg shadow-orange-600/20 shrink-0">
                        <span class="text-white text-base">✨</span>
                    </div>
                    <div class="text-left">
                        <h4 class="text-base font-black text-orange-500 tracking-tight uppercase leading-tight">Share
                            Your Experience
                        </h4>

                    </div>
                </div>

                <!-- Success message -->
                <template x-if="showSuccess">
                    <div x-transition
                        class="bg-emerald-50 text-emerald-600 p-3 rounded-xl mb-4 text-[9px] font-black uppercase tracking-widest border border-emerald-100">
                        Legacy Updated. Thank You! 🎉
                    </div>
                </template>

                <form action="submit_feedback.php" method="POST"
                    @submit="if(stars == 0) { alert('Please select a star rating first!'); $event.preventDefault(); }"
                    class="space-y-3">

                    <!-- Return to the menu after submitting feedback. -->
                    <input type="hidden" name="redirect" value="index.php">

                    <!-- Star Rating -->
                    <div class="flex justify-center gap-1.5 mb-3">
                        <template x-for="i in 5">
                            <button type="button" @click="stars = i"
                                class="text-2xl transition-all duration-300 hover:scale-125 focus:outline-none filter"
                                :class="stars >= i ? 'drop-shadow-[0_6px_6px_rgba(249,115,22,0.4)]' : 'grayscale opacity-20'">⭐</button>
                        </template>
                        <input type="hidden" name="stars" :value="stars">
                    </div>

                    <div class="space-y-2.5">
                        <input type="tel" name="phone" required placeholder="Phone Number"
                            class="w-full px-4 py-3 rounded-xl bg-gray-50 border border-gray-100 focus:border-orange-500 focus:ring-2 focus:ring-orange-500/10 focus:outline-none text-xs text-gray-900 transition-all placeholder:text-gray-300 font-bold">

                        <div class="relative">
                            <input type="email" name="email" placeholder="Email Address"
                                class="w-full px-4 py-3 rounded-xl bg-gray-50 border border-gray-100 focus:border-orange-500 focus:ring-2 focus:ring-orange-500/10 focus:outline-none text-xs text-gray-900 transition-all placeholder:text-gray-300 font-bold pr-20">
                            <span
                                class="absolute right-3 top-1/2 -translate-y-1/2 text-[7px] font-black uppercase tracking-widest text-orange-400 bg-orange-50 px-2 py-0.5 rounded-full border border-orange-100">Optional</span>
                        </div>

                        <textarea name="comment" required rows="2" placeholder="Describe your flavor journey..."
                            class="w-full px-4 py-3 rounded-xl bg-gray-50 border border-gray-100 focus:border-orange-500 focus:ring-2 focus:ring-orange-500/10 focus:outline-none text-xs text-gray-900 transition-all placeholder:text-gray-300 font-bold resize-none"></textarea>
                    </div>

                    <button type="submit"
                        class="w-full py-3 bg-gradient-to-r from-orange-500 to-orange-700 hover:from-orange-600 hover:to-orange-800 text-white rounded-2xl font-black text-[9px] uppercase tracking-widest transition-all shadow-lg shadow-orange-500/20 hover:shadow-orange-500/40 hover:scale-[1.02] active:scale-95">
                        Submit Review ✦
                    </button>
                </form>
            </div>

            <div class="bg-gray-50/80 py-2.5 text-center border-t border-gray-100">
                <p class="text-[8px] text-orange-400 font-bold uppercase tracking-[0.2em] italic">Tasty Pizza</p>
            </div>
        </div>
    </div>

</body>

</html>