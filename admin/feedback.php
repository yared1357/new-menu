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

$page_title = "Customer Feedback";
include 'header.php';
?>

<header class="mb-10">
    <h2 class="text-3xl font-bold text-gray-900">Customer Feedback</h2>
</header>

<div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
    <?php if (empty($feedback)): ?>
        <div class="col-span-full py-20 text-center text-gray-400 italic">No feedback received yet.</div>
    <?php else: ?>
        <?php foreach ($feedback as $fb): ?>
            <div
                class="glass-card p-6 rounded-[2rem] flex flex-col items-start gap-4 transition-all hover:border-orange-200 relative group <?= !$fb['is_read'] ? 'border-l-4 border-l-orange-500 bg-orange-50/30' : '' ?>">
                <div class="flex justify-between w-full">
                    <div class="flex text-amber-400 text-lg">
                        <?php for ($i = 0; $i < $fb['stars']; $i++): ?>⭐<?php endfor; ?>
                    </div>
                    <div class="flex items-center gap-3">
                        <?php if (!$fb['is_read']): ?>
                            <span
                                class="px-2 py-0.5 bg-orange-500 text-white text-[8px] font-bold rounded-full uppercase tracking-tighter">New</span>
                        <?php endif; ?>
                        <span class="text-[10px] font-bold text-gray-300 uppercase tracking-widest">
                            <?= date('M j, Y', strtotime($fb['created_at'])) ?>
                        </span>
                    </div>
                </div>

                <p class="text-gray-700 italic leading-relaxed flex-1">"<?= htmlspecialchars($fb['comment']) ?>"</p>

                <div class="w-full pt-4 border-t border-gray-50 flex justify-between items-end">
                    <div class="flex flex-col gap-1">
                        <p class="text-sm font-bold text-gray-900"><?= htmlspecialchars($fb['phone']) ?></p>
                        <?php if ($fb['email']): ?>
                            <p class="text-xs text-gray-400"><?= htmlspecialchars($fb['email']) ?></p>
                        <?php endif; ?>
                    </div>

                    <div class="flex gap-2">
                        <?php if (!$fb['is_read']): ?>
                            <a href="mark_read.php?id=<?= $fb['id'] ?>"
                                class="p-2 bg-emerald-50 text-emerald-600 rounded-lg hover:bg-emerald-600 hover:text-white transition-all shadow-sm"
                                title="Mark as Read">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                            </a>
                        <?php endif; ?>
                        <a href="delete_feedback.php?id=<?= $fb['id'] ?>"
                            @click.prevent="requestConfirmation('Delete this feedback permanently?', () => window.location.href = 'delete_feedback.php?id=<?= (int) $fb['id'] ?>')"
                            class="p-2 bg-red-50 text-red-600 rounded-lg hover:bg-red-600 hover:text-white transition-all shadow-sm"
                            title="Delete">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16">
                                </path>
                            </svg>
                        </a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php include 'footer.php'; ?>