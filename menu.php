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

$social_links = $pdo->query("SELECT platform, name, url
                              FROM social_links
                              WHERE is_active = 1
                              ORDER BY sort_order ASC, id ASC")->fetchAll();

$brand_name = get_app_setting('brand_name', 'Tasty Pizza & Burger');
$brand_short_name = get_app_setting('brand_short_name', 'Tasty Pizza');
$brand_logo = get_app_setting('brand_logo');
$brand_tagline = get_app_setting('brand_tagline', 'Digital Menu');
$brand_phone = get_app_setting('phone_number', '0926862255');
$copyright_name = get_app_setting('copyright_name', 'Tasty Pizza & Burger');

function social_link_icon(string $platform): string
{
    switch ($platform) {
        case 'facebook':
            return '<svg class="h-6 w-6" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path d="M13.5 22v-8h2.5l.4-3h-2.9V7.5c0-.9.3-1.5 1.6-1.5H16V3.1c-.3-.1-1.2-.1-2.3-.1-2.3 0-3.9 1.4-3.9 4V11H7.5v3h2.3v8h3.7Z"/></svg>';
        case 'instagram':
            return '<svg class="h-6 w-6" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path d="M7 2h10a5 5 0 0 1 5 5v10a5 5 0 0 1-5 5H7a5 5 0 0 1-5-5V7a5 5 0 0 1 5-5Zm0 2a3 3 0 0 0-3 3v10a3 3 0 0 0 3 3h10a3 3 0 0 0 3-3V7a3 3 0 0 0-3-3H7Zm5 3.2A4.8 4.8 0 1 1 7.2 12 4.8 4.8 0 0 1 12 7.2Zm0 2A2.8 2.8 0 1 0 14.8 12 2.8 2.8 0 0 0 12 9.2Zm5.2-3.1a1.1 1.1 0 1 1-1.1 1.1 1.1 1.1 0 0 1 1.1-1.1Z"/></svg>';
        case 'telegram':
            return '<svg class="h-6 w-6" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path d="M21.8 3.2a1.1 1.1 0 0 0-1.2-.2L2.9 10.3c-.8.3-.8 1.4.1 1.7l4.6 1.5 1.8 5.7c.2.7 1.1.9 1.6.4l2.6-2.5 4.7 3.4c.7.5 1.7.1 1.8-.8l2.9-15.1c.1-.5-.1-1-.2-1.2ZM8.8 12.7l10.4-6.4-8.6 7.8-.4 3.1-1.4-4.5Z"/></svg>';
        case 'whatsapp':
            return '<svg class="h-6 w-6" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2a9.8 9.8 0 0 0-8.4 14.8L2 22l5.4-1.6A10 10 0 1 0 12 2Zm0 17.9a8 8 0 0 1-4.1-1.1l-.3-.2-3.2 1 1-3.1-.2-.3A8 8 0 1 1 12 19.9Zm4.4-6c-.2-.1-1.4-.7-1.6-.8-.2-.1-.4-.1-.6.1l-.8 1c-.1.2-.3.2-.5.1a6.5 6.5 0 0 1-1.9-1.2 7.2 7.2 0 0 1-1.3-1.7c-.1-.2 0-.4.1-.5l.4-.5.2-.4c.1-.1 0-.3 0-.4l-.7-1.7c-.2-.5-.4-.4-.6-.4h-.5c-.2 0-.4.1-.6.3-.2.2-.8.8-.8 1.9s.8 2.2 1 2.4c.1.2 1.7 2.6 4.1 3.6.6.2 1 .4 1.4.5.6.2 1.1.2 1.5.1.5-.1 1.4-.6 1.6-1.1.2-.5.2-1 .1-1.1-.1-.1-.3-.2-.5-.3Z"/></svg>';
        case 'youtube':
            return '<svg class="h-6 w-6" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path d="M23.5 6.2a3 3 0 0 0-2.1-2.1C19.5 3.6 12 3.6 12 3.6s-7.5 0-9.4.5A3 3 0 0 0 .5 6.2 31 31 0 0 0 0 12a31 31 0 0 0 .5 5.8 3 3 0 0 0 2.1 2.1c1.9.5 9.4.5 9.4.5s7.5 0 9.4-.5a3 3 0 0 0 2.1-2.1A31 31 0 0 0 24 12a31 31 0 0 0-.5-5.8ZM9.6 15.6V8.4l6.3 3.6-6.3 3.6Z"/></svg>';
        case 'tiktok':
            return '<svg class="h-6 w-6" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path d="M16 3c.6 1.8 2 3 4 3v2.7c-1.3 0-2.4-.2-3.4-.8V13a5.5 5.5 0 1 1-5.5-5.5c.3 0 .6.1.9.1v2.8a3.1 3.1 0 0 0-.9-.1A3.1 3.1 0 0 0 7 13a3.1 3.1 0 0 0 3.1 3.1A3.1 3.1 0 0 0 13.2 13V3h2.8Z"/></svg>';
        default:
            return '<svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M10 13a5 5 0 0 0 7.1 0l2-2a5 5 0 0 0-7.1-7.1l-1.1 1.1M14 11a5 5 0 0 0-7.1 0l-2 2A5 5 0 0 0 12 20.1l1.1-1.1"/></svg>';
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($current_category['name'] ?? 'Menu') ?> | <?= htmlspecialchars($brand_name) ?></title>
    <meta name="theme-color" content="#1f322e">
    <meta name="description" content="Browse the <?= htmlspecialchars($brand_name) ?> menu, payment details, and customer feedback.">
    <link rel="manifest" href="manifest.webmanifest">
    <link rel="icon" href="assets/pwa/icon.svg" type="image/svg+xml">
    <link rel="apple-touch-icon" href="assets/pwa/icon-192.png">
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
            font-size: clamp(1.25rem, 3vw, 2.35rem);
            font-weight: 600;
            letter-spacing: -0.045em;
            line-height: 1.15;
        }

        .payment-menu-panel {
            width: min(22rem, calc(100vw - 2rem));
            max-height: min(75vh, 38rem);
            overflow-y: auto;
            border: 1px solid rgba(255, 255, 255, 0.14);
            border-radius: 1.5rem;
            background: linear-gradient(135deg, #1f322e, #153b34 58%, #3a2314);
            color: white;
            padding: 1.25rem;
            box-shadow: 0 24px 60px rgba(16, 39, 32, 0.3);
        }

        .call-action:hover .call-action-icon,
        .call-action:focus-visible .call-action-icon {
            animation: phone-ring 0.55s ease-in-out;
            transform-origin: 50% 50%;
        }

        @keyframes phone-ring {
            0%, 100% { transform: rotate(0); }
            20%, 60% { transform: rotate(-12deg); }
            40%, 80% { transform: rotate(12deg); }
        }

        @media (prefers-reduced-motion: reduce) {
            .call-action:hover .call-action-icon,
            .call-action:focus-visible .call-action-icon {
                animation: none;
            }
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
    x-data="{ mobileMenu: false, scrolled: false, searchQuery: '', dietFilter: 'all', paymentOpen: false, selectedPaymentQr: '', categoryRailHasOverflow: false, categoryRailAtStart: true, categoryRailAtEnd: false, mobilePaymentOpen: false, mobileSocialOpen: false, updateCategoryRail(rail) { if (!rail) return; this.categoryRailHasOverflow = rail.scrollWidth > rail.clientWidth + 2; this.categoryRailAtStart = rail.scrollLeft <= 2; this.categoryRailAtEnd = rail.scrollLeft + rail.clientWidth >= rail.scrollWidth - 2; }, scrollCategoryRail(direction, rail) { if (rail) rail.scrollBy({ left: direction === 'left' ? -220 : 220, behavior: 'smooth' }); } }"
    @scroll.window="scrolled = window.pageYOffset > 50">



    <!-- ====================== HEADER + MOBILE MENU ====================== -->
    <header
        class="glass-nav bg-gradient-to-r from-orange-500/95 to-orange-400/95 backdrop-blur-xl sticky top-0 z-[150] py-3 md:py-4 border-b border-orange-400/30 transition-shadow duration-300"
        :class="scrolled ? 'shadow-2xl shadow-orange-500/30' : 'shadow-lg'">

        <div class="container mx-auto px-4 sm:px-6 lg:px-12">
            <div class="flex justify-between items-center">
                <!-- Logo -->
                <a href="index.php" class="flex items-center gap-2 sm:gap-3 md:gap-4 relative z-[120] group">
                    <div class="<?= $brand_logo !== ''
                        ? 'flex h-9 w-fit max-w-[34vw] shrink-0 items-center justify-center rounded-xl border border-white/50 bg-white p-1 shadow-xl shadow-black/20 transition-transform duration-300 group-hover:scale-105 sm:h-10 sm:max-w-[30vw] md:h-12 md:max-w-[9rem]'
                        : 'flex h-9 w-9 shrink-0 items-center justify-center rounded-2xl border-2 border-white/50 bg-white shadow-xl shadow-black/20 transition-transform duration-300 group-hover:scale-105 sm:h-10 sm:w-11 md:h-12 md:w-12' ?>">
                        <?php if ($brand_logo !== ''): ?>
                            <img src="<?= htmlspecialchars($brand_logo) ?>" alt="<?= htmlspecialchars($brand_short_name) ?> logo" class="block h-full w-auto max-w-full object-contain">
                        <?php else: ?>
                            <span class="text-orange-600 font-black text-lg sm:text-xl md:text-2xl">T</span>
                        <?php endif; ?>
                    </div>
                    <div class="flex flex-col leading-tight">
                        <span class="text-base sm:text-lg md:text-2xl font-black tracking-tight uppercase text-white"><?= htmlspecialchars($brand_short_name) ?></span>
                        <span
                            class="text-[7px] sm:text-[8px] md:text-[10px] font-black uppercase tracking-[0.4em] text-orange-100 mt-0.5">
                            <?= htmlspecialchars($brand_tagline) ?></span>
                    </div>
                </a>

                <!-- Desktop payment menu -->
                <div class="hidden md:flex items-center gap-5 lg:gap-6">
                    <a href="tel:<?= htmlspecialchars(preg_replace('/[^0-9+]/', '', $brand_phone)) ?>" aria-label="Call <?= htmlspecialchars($brand_name) ?>" title="Call us"
                        class="call-action inline-flex h-10 w-10 items-center justify-center rounded-full border border-white/30 bg-white/10 text-white transition-colors hover:border-amber-200/70 hover:bg-white/20 hover:text-amber-100 focus:outline-none focus:ring-2 focus:ring-white/70">
                        <svg class="call-action-icon h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                                d="M3 5a2 2 0 0 1 2-2h2.3a1 1 0 0 1 .95.68l1.1 3.3a1 1 0 0 1-.5 1.2l-1.65.82a11 11 0 0 0 4.8 4.8l.82-1.65a1 1 0 0 1 1.2-.5l3.3 1.1a1 1 0 0 1 .68.95V16a2 2 0 0 1-2 2h-1C8.4 18 3 12.6 3 6V5Z" />
                        </svg>
                    </a>
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
                            class="payment-menu-panel absolute right-0 top-full z-[220] mt-3 text-left">
                            <div class="mb-5 flex items-center justify-between gap-3">
                                <div class="flex min-w-0 items-center gap-3">
                                    <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl border border-white/15 bg-white/10 text-amber-200">
                                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 7.5A2.5 2.5 0 0 1 5.5 5h13A2.5 2.5 0 0 1 21 7.5V17a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V7.5Zm0 0H21M7 11h4" />
                                        </svg>
                                    </span>
                                    <div class="min-w-0">
                                        <h2 class="text-base font-bold text-white">Payment</h2>
                                        <p class="text-xs text-orange-100/75">Choose a method to pay</p>
                                    </div>
                                </div>
                                <button type="button" @click="paymentOpen = false"
                                    class="shrink-0 rounded-full border border-white/15 bg-white/5 px-3 py-1.5 text-xs font-semibold text-orange-100 transition hover:bg-white/10">Close</button>
                            </div>
                            <?php if (!$payment_accounts): ?>
                                <div class="rounded-2xl border border-white/10 bg-white/5 p-4 text-center">
                                    <p class="text-sm font-semibold text-white">No payment methods available</p>
                                    <p class="mt-1 text-xs text-orange-100/70">Please check back later.</p>
                                </div>
                            <?php else: ?>
                                <div class="space-y-3">
                                    <?php foreach ($payment_accounts as $account): ?>
                                        <article class="rounded-2xl border border-white/15 bg-white/[0.08] p-4">
                                            <div class="flex items-center justify-between gap-3">
                                                <div class="min-w-0">
                                                    <h3 class="truncate text-sm font-bold text-white"><?= htmlspecialchars($account['provider']) ?></h3>
                                                    <p class="mt-0.5 truncate text-xs text-orange-100/70"><?= htmlspecialchars($account['account_name']) ?></p>
                                                </div>
                                                <?php if (!empty($account['qr_image'])): ?>
                                                    <button type="button"
                                                        @click.stop="selectedPaymentQr = '<?= htmlspecialchars($account['qr_image'], ENT_QUOTES) ?>'; paymentOpen = false"
                                                        class="inline-flex shrink-0 items-center gap-1.5 rounded-full bg-amber-300 px-3 py-2 text-[10px] font-bold uppercase tracking-[0.12em] text-[#263b30] transition hover:bg-amber-200">
                                                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 3h7v7H3zM14 3h7v7h-7zM3 14h7v7H3zM14 14h3v3h-3zM19 14v2m-5 3h2m3 2h2v-3" />
                                                        </svg>
                                                        View QR
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                            <div x-data="{ copied: false }" class="mt-3 flex items-center gap-2 rounded-xl border border-white/10 bg-black/10 px-3 py-2.5">
                                                <p x-ref="accountNumber" class="min-w-0 flex-1 break-all text-sm font-semibold tracking-wide text-white"><?= htmlspecialchars($account['account_number']) ?></p>
                                                <button type="button" @click.stop="navigator.clipboard.writeText($refs.accountNumber.textContent.trim()).then(() => { copied = true; setTimeout(() => copied = false, 1500) })"
                                                    :aria-label="copied ? 'Account number copied' : 'Copy account number'" :title="copied ? 'Copied' : 'Copy account number'"
                                                    class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-orange-100 transition hover:bg-white/10 hover:text-white focus:outline-none focus:ring-2 focus:ring-white/60">
                                                    <svg x-show="!copied" class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><rect x="8" y="8" width="13" height="13" rx="2" stroke-width="1.8"/><path d="M16 8V5a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h3" stroke-width="1.8"/></svg>
                                                    <svg x-show="copied" x-cloak class="h-4 w-4 text-emerald-300" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m5 12 4 4L19 6"/></svg>
                                                </button>
                                            </div>
                                            <?php if (!empty($account['qr_image'])): ?>
                                                <p class="mt-2 text-right text-[10px] font-medium text-orange-100/60">Scan to pay</p>
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
                    <a href="tel:<?= htmlspecialchars(preg_replace('/[^0-9+]/', '', $brand_phone)) ?>" aria-label="Call <?= htmlspecialchars($brand_name) ?>" title="Call us"
                        class="call-action inline-flex h-9 w-9 items-center justify-center rounded-full border border-white/30 bg-white/10 text-white transition-colors hover:border-amber-200/70 hover:bg-white/20 hover:text-amber-100 focus:outline-none focus:ring-2 focus:ring-white/70">
                        <svg class="call-action-icon h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                                d="M3 5a2 2 0 0 1 2-2h2.3a1 1 0 0 1 .95.68l1.1 3.3a1 1 0 0 1-.5 1.2l-1.65.82a11 11 0 0 0 4.8 4.8l.82-1.65a1 1 0 0 1 1.2-.5l3.3 1.1a1 1 0 0 1 .68.95V16a2 2 0 0 1-2 2h-1C8.4 18 3 12.6 3 6V5Z" />
                        </svg>
                    </a>
                    <div class="relative" @click.away="paymentOpen = false">
                        <button type="button" @click="paymentOpen = !paymentOpen" :aria-expanded="paymentOpen"
                            class="inline-flex items-center justify-center gap-1.5 rounded-full border border-white/30 bg-white/10 px-3.5 py-2 text-xs font-bold text-white transition-colors hover:bg-white/20"
                            aria-label="Payment accounts">
                            <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                                    d="M3 7h18M5 4h14a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2zm1 13h5" />
                            </svg>
                            Payment
                            <svg class="h-3.5 w-3.5 shrink-0 transition-transform" :class="paymentOpen ? 'rotate-180' : ''"
                                fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="m19 9-7 7-7-7" />
                            </svg>
                        </button>
                        <div x-show="paymentOpen" x-cloak
                            class="payment-menu-panel absolute right-0 top-full z-[220] mt-3 text-left">
                            <div class="mb-5 flex items-center justify-between gap-3">
                                <div class="flex min-w-0 items-center gap-3">
                                    <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl border border-white/15 bg-white/10 text-amber-200">
                                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 7.5A2.5 2.5 0 0 1 5.5 5h13A2.5 2.5 0 0 1 21 7.5V17a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V7.5Zm0 0H21M7 11h4" />
                                        </svg>
                                    </span>
                                    <div class="min-w-0">
                                        <h2 class="text-base font-bold text-white">Payment</h2>
                                        <p class="text-xs text-orange-100/75">Choose a method to pay</p>
                                    </div>
                                </div>
                                <button type="button" @click="paymentOpen = false"
                                    class="shrink-0 rounded-full border border-white/15 bg-white/5 px-3 py-1.5 text-xs font-semibold text-orange-100 transition hover:bg-white/10">Close</button>
                            </div>
                            <?php if (!$payment_accounts): ?>
                                <div class="rounded-2xl border border-white/10 bg-white/5 p-4 text-center">
                                    <p class="text-sm font-semibold text-white">No payment methods available</p>
                                    <p class="mt-1 text-xs text-orange-100/70">Please check back later.</p>
                                </div>
                            <?php else: ?>
                                <div class="space-y-3">
                                    <?php foreach ($payment_accounts as $account): ?>
                                        <article class="rounded-2xl border border-white/15 bg-white/[0.08] p-4">
                                            <div class="flex items-center justify-between gap-3">
                                                <div class="min-w-0">
                                                    <h3 class="truncate text-sm font-bold text-white"><?= htmlspecialchars($account['provider']) ?></h3>
                                                    <p class="mt-0.5 truncate text-xs text-orange-100/70"><?= htmlspecialchars($account['account_name']) ?></p>
                                                </div>
                                                <?php if (!empty($account['qr_image'])): ?>
                                                    <button type="button"
                                                        @click.stop="selectedPaymentQr = '<?= htmlspecialchars($account['qr_image'], ENT_QUOTES) ?>'; paymentOpen = false"
                                                        class="inline-flex shrink-0 items-center gap-1.5 rounded-full bg-amber-300 px-3 py-2 text-[10px] font-bold uppercase tracking-[0.12em] text-[#263b30] transition hover:bg-amber-200">
                                                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 3h7v7H3zM14 3h7v7h-7zM3 14h7v7H3zM14 14h3v3h-3zM19 14v2m-5 3h2m3 2h2v-3" />
                                                        </svg>
                                                        View QR
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                            <div x-data="{ copied: false }" class="mt-3 flex items-center gap-2 rounded-xl border border-white/10 bg-black/10 px-3 py-2.5">
                                                <p x-ref="accountNumber" class="min-w-0 flex-1 break-all text-sm font-semibold tracking-wide text-white"><?= htmlspecialchars($account['account_number']) ?></p>
                                                <button type="button" @click.stop="navigator.clipboard.writeText($refs.accountNumber.textContent.trim()).then(() => { copied = true; setTimeout(() => copied = false, 1500) })"
                                                    :aria-label="copied ? 'Account number copied' : 'Copy account number'" :title="copied ? 'Copied' : 'Copy account number'"
                                                    class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-orange-100 transition hover:bg-white/10 hover:text-white focus:outline-none focus:ring-2 focus:ring-white/60">
                                                    <svg x-show="!copied" class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><rect x="8" y="8" width="13" height="13" rx="2" stroke-width="1.8"/><path d="M16 8V5a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h3" stroke-width="1.8"/></svg>
                                                    <svg x-show="copied" x-cloak class="h-4 w-4 text-emerald-300" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m5 12 4 4L19 6"/></svg>
                                                </button>
                                            </div>
                                            <?php if (!empty($account['qr_image'])): ?>
                                                <p class="mt-2 text-right text-[10px] font-medium text-orange-100/60">Scan to pay</p>
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
            <div class="border-t border-gray-100/60 pt-5 mt-3" x-init="$nextTick(() => updateCategoryRail($refs.categoryRail))" @resize.window="updateCategoryRail($refs.categoryRail)">
                <div class="relative">
                    <div x-show="categoryRailHasOverflow && !categoryRailAtStart" x-cloak
                        class="pointer-events-none absolute inset-y-0 left-0 z-[5] w-10 bg-gradient-to-r from-white/95 to-transparent"></div>
                    <button type="button" x-show="categoryRailHasOverflow && !categoryRailAtStart" x-cloak
                        @click="scrollCategoryRail('left', $refs.categoryRail)" aria-label="Scroll categories left"
                        class="absolute left-1 top-1/2 -translate-y-1/2 z-10 bg-white/95 backdrop-blur rounded-full p-2 shadow-lg ring-1 ring-gray-200/80">
                        <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                        </svg>
                    </button>

                    <div x-ref="categoryRail" @scroll.passive="updateCategoryRail($el)"
                        class="flex snap-x snap-mandatory gap-3 overflow-x-auto scroll-smooth pb-3 scrollbar-hide px-4 md:px-0">
                        <a href="index.php"
                            class="category-pill snap-start whitespace-nowrap px-6 py-3 rounded-full text-xs font-black uppercase tracking-wider border transition-all <?= !$category_id ? 'bg-orange-600 text-white border-orange-600' : 'bg-white text-gray-600 border-gray-200 hover:border-orange-400 hover:text-orange-600' ?>">
                            All
                        </a>

                        <!-- Diet Filters (Dynamic) -->
                        <button @click="dietFilter = dietFilter === '1' ? 'all' : '1'"
                            :class="dietFilter === '1' ? 'bg-emerald-500 text-white border-emerald-500 shadow-md shadow-emerald-500/20' : 'bg-white text-emerald-600 border-emerald-100 hover:border-emerald-500'"
                            class="category-pill snap-start whitespace-nowrap px-6 py-3 rounded-full text-xs font-black uppercase tracking-wider border transition-all flex items-center gap-1.5 focus:outline-none">
                            <svg class="w-3.5 h-3.5" x-show="dietFilter === '1'" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3"
                                    d="M5 13l4 4L19 7"></path>
                            </svg>
                            Fasting
                        </button>

                        <button @click="dietFilter = dietFilter === '0' ? 'all' : '0'"
                            :class="dietFilter === '0' ? 'bg-rose-500 text-white border-rose-500 shadow-md shadow-rose-500/20' : 'bg-white text-rose-600 border-rose-100 hover:border-rose-500'"
                            class="category-pill snap-start whitespace-nowrap px-6 py-3 rounded-full text-xs font-black uppercase tracking-wider border transition-all flex items-center gap-1.5 focus:outline-none">
                            <span class="w-2.5 h-2.5 rounded-full"
                                :class="dietFilter === '0' ? 'bg-white animate-pulse' : 'bg-rose-500'"></span>
                            Non-Fasting
                        </button>

                        <div class="w-px bg-gray-200 my-2 mx-1 hidden md:block"></div>
                        <?php foreach ($categories as $cat): ?>
                            <a href="index.php?cat=<?= $cat['id'] ?>"
                                class="category-pill snap-start whitespace-nowrap px-6 py-3 rounded-full text-xs font-black uppercase tracking-wider border transition-all <?= $category_id == $cat['id'] ? 'bg-orange-600 text-white border-orange-600' : 'bg-white text-gray-600 border-gray-200 hover:border-orange-400 hover:text-orange-600' ?>">
                                <?= htmlspecialchars($cat['name']) ?>
                            </a>
                        <?php endforeach; ?>
                    </div>

                    <div x-show="categoryRailHasOverflow && !categoryRailAtEnd" x-cloak
                        class="pointer-events-none absolute inset-y-0 right-0 z-[5] w-14 bg-gradient-to-l from-white/95 via-white/75 to-transparent"></div>
                    <button type="button" x-show="categoryRailHasOverflow && !categoryRailAtEnd" x-cloak
                        @click="scrollCategoryRail('right', $refs.categoryRail)" aria-label="Scroll categories right"
                        class="absolute right-1 top-1/2 -translate-y-1/2 z-10 bg-white/95 backdrop-blur rounded-full p-2 shadow-lg ring-1 ring-gray-200/80">
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
            <h2 class="text-3xl sm:text-4xl font-black tracking-tight uppercase text-white"><?= htmlspecialchars($brand_short_name) ?></h2>
            <p class="text-orange-100 text-sm mt-2 font-medium"><?= htmlspecialchars($brand_tagline) ?></p>
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
    <main id="menu-section" class="pt-16 pb-32 md:pt-20 md:pb-48 min-h-screen">
        <div class="container mx-auto px-6 lg:px-12">
            <!-- Search & Page Title -->
            <div class="mb-8 flex flex-row items-center justify-between gap-2 sm:gap-6 md:mb-16">
                <div class="reveal min-w-0 flex-1 text-left">
                    <h1 class="menu-page-title mb-0 truncate whitespace-nowrap">
                        <?= htmlspecialchars($current_category['name'] ?? 'The Vault') ?>
                    </h1>
                </div>

                <div class="group reveal delay-1 relative w-[52%] shrink-0 sm:w-72 lg:w-96">
                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 sm:pl-4">
                        <svg class="h-4 w-4 text-gray-500 transition-colors group-focus-within:text-orange-600 sm:h-5 sm:w-5"
                            fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                    </div>
                    <input type="search" aria-label="Search menu items" placeholder="Search..." x-model="searchQuery"
                        class="w-full rounded-2xl border border-gray-300 bg-white py-3 pl-9 pr-2 text-xs text-gray-900 shadow-md outline-none transition placeholder:text-gray-500 focus:border-orange-600 focus:ring-4 focus:ring-orange-500/15 sm:rounded-full sm:py-4 sm:pl-12 sm:pr-6 sm:text-sm">
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
                            x-show="(searchQuery === '' || '<?= addslashes(strtolower($item['name'])) ?>'.includes(searchQuery.toLowerCase())) && (dietFilter === 'all' || dietFilter === '<?= isset($item['is_fasting']) ? (int) $item['is_fasting'] : '0' ?>')">
                            <!-- Image Container -->
                            <div
                                class="menu-item-image rounded-[3rem] overflow-hidden relative mb-8 shadow-sm group-hover:shadow-2xl transition-all duration-500">
                                <img src="<?= $item['image_url'] ?: 'https://images.unsplash.com/photo-1546069901-ba9599a7e63c?auto=format&fit=crop&q=80&w=800' ?>"
                                    class="w-full h-full object-cover transition-transform duration-[2s] group-hover:scale-110"
                                    alt="<?= htmlspecialchars($item['name']) ?>">

                                <!-- Dietary Badge Overlay -->
                                <div class="absolute top-4 left-4 z-10 transition-transform duration-500 group-hover:scale-105">
                                    <?php if (isset($item['is_fasting']) && (int) $item['is_fasting'] === 1): ?>
                                        <span
                                            class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-2xl bg-emerald-500/40 backdrop-blur-xl text-white text-[9px] font-black uppercase tracking-[0.2em] shadow-lg shadow-emerald-500/20 border border-white/30">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3"
                                                    d="M5 13l4 4L19 7"></path>
                                            </svg>
                                            Fasting
                                        </span>
                                    <?php elseif (isset($item['is_fasting']) && (int) $item['is_fasting'] === 0): ?>
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

    <footer class="mt-10 hidden border-t border-orange-300/40 bg-gradient-to-r from-[#1f322e] via-[#153b34] to-[#3a2314] text-white shadow-[0_-12px_30px_rgba(0,0,0,0.08)] md:block">
        <div class="container mx-auto px-4 py-5 sm:px-6 lg:px-12">
            <div class="hidden md:block">
                <div class="flex flex-col gap-6 pb-6 md:flex-row md:items-center md:justify-between">
                    <div class="text-center md:text-left">
                        <span class="inline-flex items-center rounded-full border border-white/20 bg-white/5 px-3 py-1 text-[10px] font-bold uppercase tracking-[0.22em] text-orange-100">
                            Fresh from our kitchen
                        </span>
                        <p class="mt-3 text-lg font-semibold text-white sm:text-xl"><?= htmlspecialchars($brand_name) ?></p>
                        <button id="pwa-install-button" type="button"
                            class="hidden mt-3 inline-flex items-center gap-2 rounded-full border border-white/20 bg-white/10 px-4 py-2 text-xs font-bold text-white transition hover:border-amber-200/50 hover:bg-white/15 focus:outline-none focus:ring-2 focus:ring-amber-200/70">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 3v12m0 0 4-4m-4 4-4-4M5 17v2a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2v-2" />
                            </svg>
                            Install app
                        </button>
                    </div>

                    <div class="flex items-center justify-center gap-3 md:justify-end">
                        <?php foreach ($social_links as $social): ?>
                            <a href="<?= htmlspecialchars($social['url']) ?>" target="_blank" rel="noopener noreferrer"
                                aria-label="<?= htmlspecialchars($social['name']) ?>" title="<?= htmlspecialchars($social['name']) ?>"
                                class="group flex h-11 items-center gap-2 rounded-full border border-white/20 bg-white/5 px-3 text-white transition-all duration-200 hover:-translate-y-0.5 hover:border-orange-200/60 hover:bg-white hover:text-orange-600">
                                <?= social_link_icon($social['platform']) ?>
                                <span class="text-xs font-bold"><?= htmlspecialchars($social['name']) ?></span>
                            </a>
                        <?php endforeach; ?>
                    </div>

                    <div class="text-center md:text-right">
                        <span class="inline-flex items-center rounded-full border border-white/20 bg-white/5 px-3 py-1 text-[10px] font-bold uppercase tracking-[0.22em] text-orange-100">
                            Open daily
                        </span>
                        <p class="font-heading mt-3 text-lg font-semibold leading-snug text-white sm:text-xl lg:text-2xl">Fresh Meals</p>
                    </div>
                </div>
            </div>
            <div class="text-center text-xs text-orange-100/80 sm:text-sm">
                <p>
                    Copyrights &copy; <?= date('Y') ?> <?= htmlspecialchars($copyright_name) ?>. All rights reserved.
                    <span class="mx-1 text-white/40" aria-hidden="true">·</span>
                    Developed by <a href="https://yared.infinityfreeapp.com/portfolio.php" target="_blank" rel="noopener noreferrer" class="font-semibold text-white transition-colors hover:text-amber-200">Yared</a>
                </p>
            </div>
        </div>
    </footer>

    <nav class="fixed inset-x-0 bottom-3 z-[180] mx-auto w-[calc(100%-1.25rem)] rounded-[1.75rem] border border-orange-200/25 bg-gradient-to-r from-[#1f322e] via-[#153b34] to-[#3a2314] p-2 text-white shadow-[0_18px_50px_rgba(20,45,36,0.28)] backdrop-blur-xl md:hidden">
        <div class="grid grid-cols-4 gap-1">
            <button type="button"
                @click="window.scrollTo({ top: 0, behavior: 'smooth' });"
                class="flex flex-col items-center justify-center gap-1 rounded-2xl px-0 py-2 text-[9px] font-semibold text-orange-50 transition hover:bg-white/10">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 10.5 12 3l9 7.5V20a1 1 0 0 1-1 1h-5v-7H9v7H4a1 1 0 0 1-1-1v-9.5Z" />
                </svg>
                <span>Home</span>
            </button>

            <button type="button"
                @click="document.getElementById('menu-section')?.scrollIntoView({ behavior: 'smooth', block: 'start' });"
                class="flex flex-col items-center justify-center gap-1 rounded-2xl px-0 py-2 text-[9px] font-semibold text-orange-50 transition hover:bg-white/10">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 6.5A2.5 2.5 0 0 1 6.5 4h11A2.5 2.5 0 0 1 20 6.5v11A2.5 2.5 0 0 1 17.5 20h-11A2.5 2.5 0 0 1 4 17.5v-11Zm3 2h10M7 12h10M7 15.5h7" />
                </svg>
                <span>Dishes</span>
            </button>

            <button type="button"
                @click="mobilePaymentOpen = true"
                class="flex flex-col items-center justify-center gap-1 rounded-2xl px-0 py-2 text-[9px] font-semibold text-orange-50 transition hover:bg-white/10">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 7.5A2.5 2.5 0 0 1 5.5 5h13A2.5 2.5 0 0 1 21 7.5V17a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V7.5Zm0 0H21M7 11h4" />
                </svg>
                <span>Payment</span>
            </button>

            <button type="button" @click="mobileSocialOpen = true" :aria-expanded="mobileSocialOpen" aria-label="Open social media"
                class="flex flex-col items-center justify-center gap-1 rounded-2xl px-0 py-2 text-[9px] font-semibold text-orange-50 transition hover:bg-white/10">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M18 8a3 3 0 1 0-2.83-4M6 14a3 3 0 1 0 0-4m12 11a3 3 0 1 0-2.83-4M8.7 10.7l6.6-3.4m-6.6 6 6.6 3.4" />
                </svg>
                <span>Social</span>
            </button>
        </div>
    </nav>

    <div x-show="mobilePaymentOpen" x-cloak x-transition
        class="fixed inset-0 z-[190] flex items-end justify-center bg-[#102720]/50 p-4 md:hidden">
        <div @click.away="mobilePaymentOpen = false" class="max-h-[85dvh] w-full max-w-md overflow-y-auto rounded-t-[2rem] bg-gradient-to-br from-[#1f322e] via-[#153b34] to-[#3a2314] p-5 text-white shadow-2xl">
            <div class="mx-auto mb-4 h-1.5 w-16 rounded-full bg-white/30"></div>
            <div class="mb-5 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <span class="flex h-11 w-11 items-center justify-center rounded-2xl border border-white/15 bg-white/10 text-amber-200">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 7.5A2.5 2.5 0 0 1 5.5 5h13A2.5 2.5 0 0 1 21 7.5V17a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V7.5Zm0 0H21M7 11h4" />
                        </svg>
                    </span>
                    <div>
                        <h3 class="text-lg font-bold text-white">Payment</h3>
                        <p class="text-xs text-orange-100/75">Choose a method to pay</p>
                    </div>
                </div>
                <button type="button" @click="mobilePaymentOpen = false" class="rounded-full border border-white/15 bg-white/5 px-3 py-1.5 text-xs font-semibold text-orange-100 transition hover:bg-white/10">Close</button>
            </div>
            <div class="space-y-3">
                <?php if (!$payment_accounts): ?>
                    <div class="rounded-2xl border border-white/10 bg-white/5 p-5 text-center">
                        <p class="text-sm font-semibold text-white">No payment methods available</p>
                        <p class="mt-1 text-xs text-orange-100/70">Please check back later.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($payment_accounts as $account): ?>
                        <div class="rounded-2xl border border-white/15 bg-white/[0.08] p-4 backdrop-blur-sm">
                            <div class="flex items-center justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-bold text-white"><?= htmlspecialchars($account['provider']) ?></p>
                                    <p class="mt-0.5 truncate text-xs text-orange-100/70"><?= htmlspecialchars($account['account_name']) ?></p>
                                </div>
                                <?php if (!empty($account['qr_image'])): ?>
                                    <button type="button" @click="selectedPaymentQr = '<?= htmlspecialchars($account['qr_image'], ENT_QUOTES) ?>'; mobilePaymentOpen = false;" class="inline-flex shrink-0 items-center gap-1.5 rounded-full bg-amber-300 px-3 py-2 text-[10px] font-bold uppercase tracking-[0.12em] text-[#263b30] transition hover:bg-amber-200">
                                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 3h7v7H3zM14 3h7v7h-7zM3 14h7v7H3zM14 14h3v3h-3zM19 14v2m-5 3h2m3 2h2v-3" />
                                        </svg>
                                        View QR
                                    </button>
                                <?php endif; ?>
                            </div>
                            <div x-data="{ copied: false }" class="mt-3 flex items-center gap-2 rounded-xl border border-white/10 bg-black/10 px-3 py-2.5">
                                <p x-ref="accountNumber" class="min-w-0 flex-1 break-all text-sm font-semibold tracking-wide text-white"><?= htmlspecialchars($account['account_number']) ?></p>
                                <button type="button" @click.stop="navigator.clipboard.writeText($refs.accountNumber.textContent.trim()).then(() => { copied = true; setTimeout(() => copied = false, 1500) })"
                                    :aria-label="copied ? 'Account number copied' : 'Copy account number'" :title="copied ? 'Copied' : 'Copy account number'"
                                    class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-orange-100 transition hover:bg-white/10 hover:text-white focus:outline-none focus:ring-2 focus:ring-white/60">
                                    <svg x-show="!copied" class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><rect x="8" y="8" width="13" height="13" rx="2" stroke-width="1.8"/><path d="M16 8V5a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h3" stroke-width="1.8"/></svg>
                                    <svg x-show="copied" x-cloak class="h-4 w-4 text-emerald-300" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m5 12 4 4L19 6"/></svg>
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div x-show="mobileSocialOpen" x-cloak x-transition
        class="fixed inset-0 z-[190] flex items-end justify-center bg-[#102720]/50 p-4 md:hidden">
        <div @click.away="mobileSocialOpen = false" class="w-full max-w-md rounded-t-[2rem] bg-gradient-to-br from-[#1f322e] via-[#153b34] to-[#3a2314] p-5 text-white shadow-2xl">
            <div class="mx-auto mb-4 h-1.5 w-16 rounded-full bg-white/30"></div>
            <div class="mb-5 flex items-center justify-between">
                <h3 class="text-lg font-bold text-white">Social media</h3>
                <button type="button" @click="mobileSocialOpen = false" class="text-sm font-medium text-orange-100">Close</button>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <?php if (!$social_links): ?>
                    <p class="col-span-2 text-center text-sm text-orange-100/70">No social links available.</p>
                <?php else: ?>
                    <?php foreach ($social_links as $social): ?>
                        <a href="<?= htmlspecialchars($social['url']) ?>" target="_blank" rel="noopener noreferrer"
                            aria-label="<?= htmlspecialchars($social['name']) ?>" title="<?= htmlspecialchars($social['name']) ?>"
                            @click="mobileSocialOpen = false"
                            class="flex items-center gap-3 rounded-2xl border border-white/10 bg-white/5 p-3 text-white transition hover:-translate-y-0.5 hover:border-orange-300/50 hover:bg-orange-500/20">
                            <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-white/10">
                                <?= social_link_icon($social['platform']) ?>
                            </span>
                            <span class="text-sm font-bold"><?= htmlspecialchars($social['name']) ?></span>
                        </a>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <dialog id="pwa-install-help" aria-labelledby="pwa-install-title"
        class="w-[calc(100%-2rem)] max-w-md rounded-3xl border border-white/70 bg-white p-0 text-[#20332b] shadow-2xl backdrop:bg-[#102720]/60">
        <div class="p-6 sm:p-8">
            <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-800">
                <svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 3v12m0 0 4-4m-4 4-4-4M5 17v2a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2v-2" />
                </svg>
            </div>
            <h2 id="pwa-install-title" class="mt-4 text-center text-xl font-bold">Install <?= htmlspecialchars($brand_short_name) ?></h2>
            <p id="pwa-install-instructions" class="mt-2 text-center text-sm leading-6 text-gray-600"></p>
            <button type="button" id="pwa-install-close"
                class="mt-6 w-full rounded-xl bg-[#1f4d3e] px-4 py-3 text-sm font-bold text-white transition hover:bg-[#173b34]">
                Got it
            </button>
        </div>
    </dialog>

    <!-- Floating Feedback Trigger -->
    <div x-data="{
            feedbackOpen: <?= (isset($_GET['status']) && $_GET['status'] == 'success') ? 'true' : 'false' ?>,
            showSuccess: <?= (isset($_GET['status']) && $_GET['status'] == 'success') ? 'true' : 'false' ?>,
            stars: 0
         }"
        x-init="if(showSuccess) { setTimeout(() => { showSuccess = false; feedbackOpen = false; history.replaceState(null, '', window.location.pathname); }, 3000) }"
        class="fixed bottom-24 right-4 z-[200] md:bottom-8 md:right-8">

        <!-- Floating Feedback Button -->
        <button @click="feedbackOpen = !feedbackOpen"
            class="flex h-12 w-12 items-center justify-center rounded-full bg-gradient-to-br from-orange-500 to-orange-700 text-white shadow-2xl shadow-orange-500/40 transition-all duration-300 hover:scale-110 active:scale-95 relative md:h-16 md:w-16">
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

        <!-- Feedback Form Panel -->
        <div x-show="feedbackOpen" x-transition:enter="transition ease-out duration-300 transform"
            x-transition:enter-start="opacity-0 translate-y-10 scale-90"
            x-transition:enter-end="opacity-100 translate-y-0 scale-100"
            x-transition:leave="transition ease-in duration-200 transform"
            x-transition:leave-start="opacity-100 translate-y-0 scale-100"
            x-transition:leave-end="opacity-0 translate-y-10 scale-90" @click.away="feedbackOpen = false"
            class="absolute bottom-16 right-0 w-[min(20rem,calc(100vw-2rem))] overflow-y-auto rounded-3xl border border-emerald-900/10 bg-white shadow-[0_20px_60px_rgba(16,39,32,0.18)] max-h-[calc(100dvh-10rem)] sm:w-80 md:bottom-20 md:max-h-[80vh]"
            x-cloak>

            <div class="p-6 text-gray-900 text-center">
                <!-- Header -->
                <div class="flex items-center justify-center gap-3 mb-4">
                    <div
                        class="w-10 h-10 bg-gradient-to-br from-emerald-700 to-[#153b34] rounded-2xl flex items-center justify-center shadow-lg shadow-emerald-900/20 shrink-0">
                        <span class="text-white text-base">✨</span>
                    </div>
                    <div class="text-left">
                        <h4 class="text-base font-black text-emerald-800 tracking-tight uppercase leading-tight">Share
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
                    <div class="border-t border-gray-200 pt-4">
                        <p class="mb-2 text-left text-xs font-bold text-gray-700">How was your experience?</p>
                        <div role="group" aria-label="Select a star rating" class="flex justify-center gap-2">
                            <template x-for="i in 5">
                                <button type="button" @click="stars = i"
                                    :aria-label="`${i} star${i === 1 ? '' : 's'}`" :aria-pressed="stars === i"
                                    class="text-3xl leading-none transition-transform duration-200 hover:scale-110 focus:outline-none focus-visible:rounded focus-visible:ring-2 focus-visible:ring-amber-400"
                                    :class="stars >= i ? 'text-amber-400 drop-shadow-[0_3px_4px_rgba(180,120,20,0.25)]' : 'text-gray-300 hover:text-amber-300'">★</button>
                            </template>
                        </div>
                        <input type="hidden" name="stars" :value="stars">
                    </div>

                    <div class="space-y-2.5">
                        <input type="tel" name="phone" required placeholder="Phone Number"
                            class="w-full px-4 py-3 rounded-xl bg-[#f6f9f6] border border-gray-200 focus:border-emerald-700 focus:ring-2 focus:ring-emerald-700/15 focus:outline-none text-sm text-gray-900 transition-all placeholder:text-gray-500 font-medium">

                        <div class="relative">
                            <input type="email" name="email" placeholder="Email Address"
                                class="w-full px-4 py-3 rounded-xl bg-[#f6f9f6] border border-gray-200 focus:border-emerald-700 focus:ring-2 focus:ring-emerald-700/15 focus:outline-none text-sm text-gray-900 transition-all placeholder:text-gray-500 font-medium pr-20">
                            <span
                                class="absolute right-3 top-1/2 -translate-y-1/2 text-[9px] font-bold uppercase tracking-wider text-emerald-800 bg-emerald-50 px-2 py-1 rounded-full border border-emerald-100">Optional</span>
                        </div>

                        <textarea name="comment" required rows="2" placeholder="Describe your flavor journey..."
                            class="w-full px-4 py-3 rounded-xl bg-[#f6f9f6] border border-gray-200 focus:border-emerald-700 focus:ring-2 focus:ring-emerald-700/15 focus:outline-none text-sm text-gray-900 transition-all placeholder:text-gray-500 font-medium resize-none"></textarea>
                    </div>

                    <button type="submit"
                        class="w-full py-3 bg-gradient-to-r from-[#1f322e] to-emerald-700 hover:from-[#153b34] hover:to-emerald-800 text-white rounded-2xl font-bold text-xs uppercase tracking-widest transition-all shadow-lg shadow-emerald-900/15 hover:shadow-emerald-900/25 hover:scale-[1.02] active:scale-95">
                        Submit feedback
                    </button>
                </form>
            </div>

            <div class="bg-[#f6f9f6] py-2.5 text-center border-t border-gray-200">
                <p class="text-[9px] text-emerald-800 font-bold uppercase tracking-[0.2em]"><?= htmlspecialchars($brand_short_name) ?></p>
            </div>
        </div>
    </div>

    <script>
        (() => {
            const installButton = document.getElementById('pwa-install-button');
            const installDialog = document.getElementById('pwa-install-help');
            const instructions = document.getElementById('pwa-install-instructions');
            const closeButton = document.getElementById('pwa-install-close');
            let installPrompt = null;
            const isInstalled = window.matchMedia('(display-mode: standalone)').matches || navigator.standalone === true;

            if (installButton) {
                installButton.classList.toggle('hidden', isInstalled);

                window.addEventListener('beforeinstallprompt', event => {
                    if (!window.matchMedia('(min-width: 768px)').matches) return;
                    event.preventDefault();
                    installPrompt = event;
                    installButton.classList.remove('hidden');
                });

                installButton.addEventListener('click', async () => {
                    if (installPrompt) {
                        installPrompt.prompt();
                        await installPrompt.userChoice;
                        installPrompt = null;
                        return;
                    }

                    const isIos = /iphone|ipad|ipod/i.test(navigator.userAgent);
                    instructions.textContent = isIos
                        ? 'In Safari, tap Share, then choose “Add to Home Screen”.'
                        : 'Open your browser menu and choose “Install app” or “Add to Home Screen”.';
                    installDialog.showModal();
                });

                closeButton.addEventListener('click', () => installDialog.close());
                installDialog.addEventListener('click', event => {
                    if (event.target === installDialog) installDialog.close();
                });
            }

            if ('serviceWorker' in navigator) {
                navigator.serviceWorker.register('./service-worker.js')
                    .catch(error => console.error('Service worker registration failed:', error));
            }
        })();
    </script>

</body>

</html>