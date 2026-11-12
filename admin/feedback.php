<?php
session_start();
require_once '../includes/db.php';

// Auth check
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit;
}

// Fetch feedback
$feedback = $pdo->query("SELECT * FROM feedback ORDER BY created_at DESC")->fetchAll();
$feedback_count = count($feedback);
$unread_count = count(array_filter($feedback, static fn($entry) => empty($entry['is_read'])));
$average_rating = $feedback_count
    ? array_sum(array_map(static fn($entry) => (int) $entry['stars'], $feedback)) / $feedback_count
    : 0;
$feedback_filter_items = array_map(static function ($entry) {
    return [
        'search' => strtolower(trim(($entry['comment'] ?? '') . ' ' . ($entry['phone'] ?? '') . ' ' . ($entry['email'] ?? ''))),
        'status' => empty($entry['is_read']) ? 'unread' : 'read',
    ];
}, $feedback);

$page_title = "Customer Feedback";
include 'header.php';
?>

<div class="space-y-8"
    x-data="{
        feedbackSearch: '',
        feedbackFilter: 'all',
        feedbackItems: <?= htmlspecialchars(
            json_encode($feedback_filter_items, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT),
            ENT_QUOTES | ENT_SUBSTITUTE,
            'UTF-8'
        ) ?>,
        matchesFeedback(item) {
            return (this.feedbackFilter === 'all' || this.feedbackFilter === item.status)
                && (!this.feedbackSearch.trim() || item.search.includes(this.feedbackSearch.trim().toLowerCase()));
        },
        hasMatchingFeedback() {
            return this.feedbackItems.some(item => this.matchesFeedback(item));
        }
    }">
    <header class="relative isolate overflow-hidden rounded-[2rem] bg-gradient-to-br from-[#1f322e] via-[#174438] to-[#21604a] px-4 py-4 text-white shadow-xl shadow-emerald-950/10 sm:px-9 sm:py-10">
        <div class="pointer-events-none absolute -right-10 -top-20 h-64 w-64 rounded-full border-[30px] border-white/[0.04]"></div>
        <div class="pointer-events-none absolute -bottom-28 right-28 h-56 w-56 rounded-full bg-amber-300/10 blur-2xl"></div>
        <div class="relative flex items-center justify-between gap-2 sm:gap-6">
            <div class="min-w-0">
                <span class="hidden items-center gap-2 rounded-full border border-white/15 bg-white/10 px-3 py-1.5 text-[10px] font-bold uppercase tracking-[0.18em] text-emerald-100 sm:inline-flex">
                    <span class="h-1.5 w-1.5 rounded-full bg-amber-300"></span>
                    Guest voices
                </span>
                <h2 class="whitespace-nowrap text-lg font-bold tracking-tight sm:mt-4 sm:text-4xl">Customer feedback</h2>
                </div>
            <div class="flex shrink-0 items-center gap-2 rounded-xl border border-white/15 bg-white/[0.08] px-2.5 py-2 backdrop-blur-sm sm:gap-3 sm:rounded-2xl sm:px-4 sm:py-3">
                <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-amber-300 text-amber-950 sm:h-11 sm:w-11 sm:rounded-xl">
                    <svg class="h-4 w-4 sm:h-5 sm:w-5" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                        <path d="m9.05 2.93 1.4 2.84 3.14.46a1 1 0 0 1 .55 1.7l-2.27 2.21.54 3.13a1 1 0 0 1-1.45 1.05L8.15 12.84l-2.81 1.48a1 1 0 0 1-1.45-1.05l.54-3.13L2.16 7.93a1 1 0 0 1 .55-1.7l3.14-.46 1.4-2.84a1 1 0 0 1 1.8 0Z" />
                    </svg>
                </span>
                <div>
                    <p class="whitespace-nowrap text-base font-bold leading-none sm:text-2xl"><?= number_format($average_rating, 1) ?><span class="ml-0.5 text-base font-bold text-white/60 sm:ml-1 sm:text-2xl">/ 5</span></p>
                    <p class="mt-1 whitespace-nowrap text-[8px] font-bold uppercase tracking-[0.08em] text-white/60 sm:text-[10px] sm:tracking-[0.14em]">Average rating</p>
                </div>
            </div>
        </div>
    </header>

    <section class="mx-auto grid max-w-4xl grid-cols-3 gap-2 sm:gap-4" aria-label="Feedback summary">
        <article class="glass-card flex min-w-0 flex-col items-center justify-center gap-2 rounded-2xl px-2 py-4 text-center sm:flex-row sm:gap-4 sm:p-5 sm:text-left">
            <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-700">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M7 8h10M7 12h6m-1 8-4-4H5a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2h-5l-4 4Z" />
                </svg>
            </span>
            <div><p class="text-xl font-bold text-[#20332b] sm:text-2xl"><?= $feedback_count ?></p><p class="text-[10px] font-semibold leading-tight text-gray-500 sm:text-xs">Total reviews</p></div>
        </article>
        <article class="glass-card flex min-w-0 flex-col items-center justify-center gap-2 rounded-2xl px-2 py-4 text-center sm:flex-row sm:gap-4 sm:p-5 sm:text-left">
            <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-amber-50 text-amber-700">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 8v4l2.5 2.5M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                </svg>
            </span>
            <div><p class="text-xl font-bold text-[#20332b] sm:text-2xl"><?= $unread_count ?></p><p class="text-[10px] font-semibold leading-tight text-gray-500 sm:text-xs">Awaiting your review</p></div>
        </article>
        <article class="glass-card flex min-w-0 flex-col items-center justify-center gap-2 rounded-2xl px-2 py-4 text-center sm:flex-row sm:gap-4 sm:p-5 sm:text-left">
            <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-orange-50 text-orange-700">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path fill="currentColor" stroke="none" d="m12 2.8 2.7 5.48 6.05.88-4.38 4.27 1.03 6.03L12 16.61l-5.4 2.85 1.03-6.03-4.38-4.27 6.05-.88L12 2.8Z" />
                </svg>
            </span>
            <div><p class="text-xl font-bold text-[#20332b] sm:text-2xl"><?= $feedback_count ? number_format($average_rating, 1) : '—' ?></p><p class="text-[10px] font-semibold leading-tight text-gray-500 sm:text-xs">Guest satisfaction</p></div>
        </article>
    </section>

    <section>
        <div class="mb-5 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h3 class="text-xl font-bold tracking-tight text-[#20332b]">Recent reviews</h3>
                </div>
            <label class="relative block w-full sm:max-w-xs">
                <span class="sr-only">Search feedback</span>
                <svg class="absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="m20 20-4.5-4.5m2-5.5a7.5 7.5 0 1 1-15 0 7.5 7.5 0 0 1 15 0Z" />
                </svg>
                <input type="search" x-model="feedbackSearch" placeholder="Search reviews or contact..."
                    class="w-full rounded-xl border border-gray-200 bg-white py-3 pl-10 pr-4 text-sm text-gray-700 shadow-sm outline-none transition focus:border-emerald-700 focus:ring-4 focus:ring-emerald-700/10">
            </label>
        </div>

        <div class="mb-5 flex flex-nowrap justify-start gap-1.5 sm:gap-2" role="group" aria-label="Filter feedback">
            <button type="button" @click="feedbackFilter = 'all'"
                :class="feedbackFilter === 'all' ? 'bg-[#1f4d3e] text-white shadow-md shadow-emerald-950/10' : 'border border-gray-200 bg-white text-gray-600 hover:bg-gray-50'"
                class="whitespace-nowrap rounded-full px-2.5 py-2 text-[10px] font-bold transition sm:px-4 sm:text-xs">All reviews <span class="ml-1 opacity-70"><?= $feedback_count ?></span></button>
            <button type="button" @click="feedbackFilter = 'unread'"
                :class="feedbackFilter === 'unread' ? 'bg-[#1f4d3e] text-white shadow-md shadow-emerald-950/10' : 'border border-gray-200 bg-white text-gray-600 hover:bg-gray-50'"
                class="whitespace-nowrap rounded-full px-2.5 py-2 text-[10px] font-bold transition sm:px-4 sm:text-xs">Unread <span class="ml-1 opacity-70"><?= $unread_count ?></span></button>
            <button type="button" @click="feedbackFilter = 'read'"
                :class="feedbackFilter === 'read' ? 'bg-[#1f4d3e] text-white shadow-md shadow-emerald-950/10' : 'border border-gray-200 bg-white text-gray-600 hover:bg-gray-50'"
                class="whitespace-nowrap rounded-full px-2.5 py-2 text-[10px] font-bold transition sm:px-4 sm:text-xs">Read</button>
        </div>

        <?php if (empty($feedback)): ?>
            <div class="glass-card flex flex-col items-center rounded-3xl px-6 py-16 text-center">
                <span class="flex h-16 w-16 items-center justify-center rounded-3xl bg-emerald-50 text-emerald-700">
                    <svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M7 8h10M7 12h6m-1 8-4-4H5a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2h-5l-4 4Z" />
                    </svg>
                </span>
                <h4 class="mt-5 text-lg font-bold text-[#20332b]">Your guest feedback will appear here</h4>
                <p class="mt-2 max-w-sm text-sm leading-6 text-gray-500">Once a customer shares a review, you can read and manage it from this page.</p>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 gap-5 lg:grid-cols-2 2xl:grid-cols-3">
                <?php foreach ($feedback as $feedback_index => $fb):
                    $is_unread = empty($fb['is_read']);
                    $rating = max(0, min(5, (int) $fb['stars']));
                    ?>
                    <article
                        x-show="matchesFeedback(feedbackItems[<?= (int) $feedback_index ?>])"
                        x-transition
                        class="group relative flex flex-col overflow-hidden rounded-3xl border bg-white p-5 shadow-[0_8px_28px_rgba(27,52,43,0.06)] transition duration-300 hover:-translate-y-1 hover:shadow-[0_18px_42px_rgba(27,52,43,0.12)] sm:p-6 <?= $is_unread ? 'border-emerald-200/80' : 'border-gray-100' ?>">
                        <?php if ($is_unread): ?>
                            <span class="absolute inset-y-0 left-0 w-1 bg-gradient-to-b from-amber-300 to-orange-500"></span>
                        <?php endif; ?>
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <div class="flex items-center gap-1" aria-label="<?= $rating ?> out of 5 stars">
                                    <?php for ($star = 1; $star <= 5; $star++): ?>
                                        <svg class="h-4 w-4 <?= $star <= $rating ? 'text-amber-400' : 'text-gray-200' ?>" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                                            <path d="m9.05 2.93 1.4 2.84 3.14.46a1 1 0 0 1 .55 1.7l-2.27 2.21.54 3.13a1 1 0 0 1-1.45 1.05L8.15 12.84l-2.81 1.48a1 1 0 0 1-1.45-1.05l.54-3.13L2.16 7.93a1 1 0 0 1 .55-1.7l3.14-.46 1.4-2.84a1 1 0 0 1 1.8 0Z" />
                                        </svg>
                                    <?php endfor; ?>
                                    <span class="ml-1 text-xs font-bold text-gray-500"><?= $rating ?>.0</span>
                                </div>
                                <p class="mt-2 text-[10px] font-semibold uppercase tracking-[0.13em] text-gray-400">
                                    <?= htmlspecialchars(date('M j, Y', strtotime($fb['created_at'])), ENT_QUOTES, 'UTF-8') ?>
                                </p>
                            </div>
                            <?php if ($is_unread): ?>
                                <span class="inline-flex items-center gap-1.5 rounded-full bg-amber-50 px-2.5 py-1 text-[10px] font-bold uppercase tracking-wider text-amber-800">
                                    <span class="h-1.5 w-1.5 rounded-full bg-amber-500"></span> New
                                </span>
                            <?php else: ?>
                                <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2.5 py-1 text-[10px] font-bold uppercase tracking-wider text-emerald-700">
                                    <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="m5 12 4 4L19 6" /></svg>
                                    Read
                                </span>
                            <?php endif; ?>
                        </div>

                        <blockquote class="mt-5 flex-1 text-[15px] leading-7 text-gray-700">
                            <span class="mr-1 font-serif text-2xl leading-none text-emerald-700/50">“</span><?= nl2br(htmlspecialchars(trim($fb['comment'] ?? ''), ENT_QUOTES, 'UTF-8')) ?: '<span class="italic text-gray-400">No written comment.</span>' ?><span class="ml-1 font-serif text-2xl leading-none text-emerald-700/50">”</span>
                        </blockquote>

                        <div class="mt-6 flex items-end justify-between gap-3 border-t border-gray-100 pt-4">
                            <div class="min-w-0 space-y-1.5">
                                <?php if (!empty($fb['phone'])): ?>
                                    <a href="tel:<?= htmlspecialchars(preg_replace('/[^0-9+]/', '', $fb['phone']), ENT_QUOTES, 'UTF-8') ?>"
                                        class="flex items-center gap-2 truncate text-sm font-bold text-[#20332b] transition hover:text-emerald-700">
                                        <svg class="h-4 w-4 shrink-0 text-emerald-700/70" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 5a2 2 0 0 1 2-2h2.3a1 1 0 0 1 .95.68l1.1 3.3a1 1 0 0 1-.5 1.2l-1.65.82a11 11 0 0 0 4.8 4.8l.82-1.65a1 1 0 0 1 1.2-.5l3.3 1.1a1 1 0 0 1 .68.95V16a2 2 0 0 1-2 2h-1C8.4 18 3 12.6 3 6V5Z" />
                                        </svg>
                                        <?= htmlspecialchars($fb['phone'], ENT_QUOTES, 'UTF-8') ?>
                                    </a>
                                <?php endif; ?>
                                <?php if (!empty($fb['email'])): ?>
                                    <p class="flex items-center gap-2 truncate text-xs text-gray-500">
                                        <svg class="h-4 w-4 shrink-0 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 6h18v12H3zM3 7l9 6 9-6" />
                                        </svg>
                                        <span class="truncate"><?= htmlspecialchars($fb['email'], ENT_QUOTES, 'UTF-8') ?></span>
                                    </p>
                                <?php endif; ?>
                                <?php if (empty($fb['phone']) && empty($fb['email'])): ?>
                                    <p class="text-xs text-gray-400">Guest details not provided</p>
                                <?php endif; ?>
                            </div>
                            <div class="flex shrink-0 items-center gap-2">
                                <?php if ($is_unread): ?>
                                    <a href="mark_read.php?id=<?= (int) $fb['id'] ?>" aria-label="Mark feedback as read" title="Mark as read"
                                        class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-emerald-100 bg-emerald-50 text-emerald-700 transition hover:border-emerald-600 hover:bg-emerald-600 hover:text-white">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m5 12 4 4L19 6" />
                                        </svg>
                                    </a>
                                <?php endif; ?>
                                <button type="button" aria-label="Delete feedback" title="Delete feedback"
                                    @click="requestConfirmation('Delete this feedback permanently?', () => window.location.href = 'delete_feedback.php?id=<?= (int) $fb['id'] ?>')"
                                    class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-rose-100 bg-rose-50 text-rose-600 transition hover:border-rose-600 hover:bg-rose-600 hover:text-white">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 7h16m-10 4v6m4-6v6M6 7l1 14h10l1-14M9 7V4h6v3" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
                <div x-show="!hasMatchingFeedback()" x-cloak aria-live="polite"
                    class="col-span-full rounded-2xl border border-dashed border-gray-200 bg-white/70 px-6 py-12 text-center">
                    <p class="font-semibold text-gray-700">No reviews match your filters</p>
                </div>
            </div>
        <?php endif; ?>
    </section>
</div>

<?php include 'footer.php'; ?>