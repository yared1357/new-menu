<?php
session_start();
require_once '../includes/db.php';

// Auth check
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit;
}

// Stats
$total_items = $pdo->query("SELECT COUNT(*) FROM menu_items")->fetchColumn();
$total_categories = $pdo->query("SELECT COUNT(*) FROM categories")->fetchColumn();
$total_feedback = $pdo->query("SELECT COUNT(*) FROM feedback")->fetchColumn();
$avg_rating = $pdo->query("SELECT AVG(stars) FROM feedback")->fetchColumn() ?: 0;
$total_fasting = $pdo->query("SELECT COUNT(*) FROM menu_items WHERE is_fasting = 1")->fetchColumn();
$total_non_fasting = $pdo->query("SELECT COUNT(*) FROM menu_items WHERE is_fasting = 0")->fetchColumn();

// Recent feedback
$recent_feedback = $pdo->query("SELECT * FROM feedback ORDER BY created_at DESC LIMIT 3")->fetchAll();

// Mock stats for "International Dashboard" look
$total_orders = 1250; // Mock
$revenue = 24500.50; // Mock

$page_title = "Dashboard";
include 'header.php';
?>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<header class="mb-12">
    <h2 class="text-4xl font-bold text-gray-900 leading-tight">TastyPizza <span class="text-orange-500">Dashboard</span>
    </h2>
</header>

<!-- Stats Grid -->
<div class="grid grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-4 md:gap-6 mb-12">
    <a href="categories.php"
        class="glass-card p-4 md:p-6 rounded-[1.5rem] md:rounded-[2rem] border-l-4 border-orange-500 overflow-hidden relative group transition-all hover:scale-[1.02] hover:shadow-xl hover:shadow-orange-500/10 active:scale-95">
        <div class="relative z-10">
            <p class="text-gray-400 text-[8px] md:text-[10px] font-bold uppercase tracking-wider mb-2">All Categories
            </p>
            <p class="text-2xl md:text-3xl font-bold text-gray-900"><?= $total_categories ?></p>
            <p class="text-[9px] md:text-[10px] text-green-500 font-bold mt-2 flex items-center gap-1">
                All Categories
            </p>
        </div>
        <!-- Sparkline SVG -->
        <div class="absolute bottom-0 right-0 w-24 h-16 opacity-10 group-hover:opacity-20 transition-opacity">
            <svg class="w-full h-full" viewBox="0 0 100 40">
                <path d="M0,35 Q20,30 40,32 T80,10 T100,5" fill="none" stroke="#f97316" stroke-width="4" />
            </svg>
        </div>
    </a>
    <a href="index.php"
        class="glass-card p-4 md:p-6 rounded-[1.5rem] md:rounded-[2rem] border-l-4 border-blue-500 overflow-hidden relative group transition-all hover:scale-[1.02] hover:shadow-xl hover:shadow-blue-500/10 active:scale-95">
        <div class="relative z-10">
            <p class="text-gray-400 text-[8px] md:text-[10px] font-bold uppercase tracking-wider mb-2">All Menu Items
            </p>
            <p class="text-2xl md:text-3xl font-bold text-gray-900"><?= $total_items ?></p>
            <p class="text-[9px] md:text-[10px] text-blue-500 font-bold mt-2 flex items-center gap-1">
                All Menu Items
            </p>
        </div>
        <!-- Sparkline SVG -->
        <div class="absolute bottom-0 right-0 w-24 h-16 opacity-10 group-hover:opacity-20 transition-opacity">
            <svg class="w-full h-full" viewBox="0 0 100 40">
                <path d="M0,5 Q20,35 40,10 T80,30 T100,20" fill="none" stroke="#3b82f6" stroke-width="4" />
            </svg>
        </div>
    </a>
    <a href="index.php"
        class="glass-card p-4 md:p-6 rounded-[1.5rem] md:rounded-[2rem] border-l-4 border-emerald-500 overflow-hidden relative group transition-all hover:scale-[1.02] hover:shadow-xl hover:shadow-emerald-500/10 active:scale-95">
        <div class="relative z-10">
            <p class="text-gray-400 text-[8px] md:text-[10px] font-bold uppercase tracking-wider mb-2">Fasting Items</p>
            <p class="text-2xl md:text-3xl font-bold text-gray-900"><?= $total_fasting ?></p>
            <p class="text-[9px] md:text-[10px] text-emerald-500 font-bold mt-2 flex items-center gap-1">
                Fasting Items
            </p>
        </div>
        <div class="absolute bottom-0 right-0 w-24 h-16 opacity-10 group-hover:opacity-20 transition-opacity">
            <svg class="w-full h-full" viewBox="0 0 100 40">
                <path d="M0,35 Q20,10 40,25 T80,5 T100,10" fill="none" stroke="#10b981" stroke-width="4" />
            </svg>
        </div>
    </a>
    <a href="index.php"
        class="glass-card p-4 md:p-6 rounded-[1.5rem] md:rounded-[2rem] border-l-4 border-rose-500 overflow-hidden relative group transition-all hover:scale-[1.02] hover:shadow-xl hover:shadow-rose-500/10 active:scale-95">
        <div class="relative z-10">
            <p class="text-gray-400 text-[8px] md:text-[10px] font-bold uppercase tracking-wider mb-2">Non-Fasting Items
            </p>
            <p class="text-2xl md:text-3xl font-bold text-gray-900"><?= $total_non_fasting ?></p>
            <p class="text-[9px] md:text-[10px] text-rose-500 font-bold mt-2 flex items-center gap-1">
                Non-Fasting Items
            </p>
        </div>
        <div class="absolute bottom-0 right-0 w-24 h-16 opacity-10 group-hover:opacity-20 transition-opacity">
            <svg class="w-full h-full" viewBox="0 0 100 40">
                <path d="M0,20 Q20,35 40,10 T80,30 T100,20" fill="none" stroke="#f43f5e" stroke-width="4" />
            </svg>
        </div>
    </a>
    <a href="feedback.php"
        class="glass-card p-4 md:p-6 rounded-[1.5rem] md:rounded-[2rem] border-l-4 border-emerald-500 overflow-hidden relative group transition-all hover:scale-[1.02] hover:shadow-xl hover:shadow-emerald-500/10 active:scale-95">
        <div class="relative z-10">
            <p class="text-gray-400 text-[8px] md:text-[10px] font-bold uppercase tracking-wider mb-2">Total Feedbacks
            </p>
            <p class="text-2xl md:text-3xl font-bold text-gray-900"><?= $total_feedback ?></p>
            <p class="text-[9px] md:text-[10px] text-emerald-500 font-bold mt-2 flex items-center gap-1">
                Total Feedbacks
            </p>
        </div>
        <!-- Sparkline SVG -->
        <div class="absolute bottom-0 right-0 w-24 h-16 opacity-10 group-hover:opacity-20 transition-opacity">
            <svg class="w-full h-full" viewBox="0 0 100 40">
                <path d="M0,25 Q30,5 60,35 T100,5" fill="none" stroke="#10b981" stroke-width="4" />
            </svg>
        </div>
    </a>
    <a href="feedback.php"
        class="glass-card p-4 md:p-6 rounded-[1.5rem] md:rounded-[2rem] border-l-4 border-amber-500 overflow-hidden relative group transition-all hover:scale-[1.02] hover:shadow-xl hover:shadow-amber-500/10 active:scale-95">
        <div class="relative z-10">
            <p class="text-gray-400 text-[8px] md:text-[10px] font-bold uppercase tracking-wider mb-2">Exp. Rating</p>
            <p class="text-2xl md:text-3xl font-bold text-gray-900"><?= number_format($avg_rating, 1) ?> <span
                    class="text-lg md:text-xl text-amber-500">⭐</span></p>
            <p class="text-[9px] md:text-[10px] text-amber-600 font-bold mt-2 flex items-center gap-1">
                Average Rating
            </p>
        </div>
        <!-- Sparkline SVG -->
        <div class="absolute bottom-0 right-0 w-24 h-16 opacity-10 group-hover:opacity-20 transition-opacity">
            <svg class="w-full h-full" viewBox="0 0 100 40">
                <path d="M0,35 L20,30 L40,35 L60,10 L80,20 L100,5" fill="none" stroke="#f59e0b" stroke-width="4" />
            </svg>
        </div>
    </a>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-10">
    <!-- Recent Feedback -->
    <div class="glass-card rounded-[2.5rem] p-8">
        <div class="flex justify-between items-center mb-8">
            <a href="feedback.php" class="group flex items-center gap-3">
                <h3
                    class="text-xl font-bold text-gray-900 px-4 py-1.5 bg-orange-50 text-orange-600 rounded-full inline-block group-hover:bg-orange-500 group-hover:text-white transition-all">
                    Recent Feedback</h3>
            </a>
            <a href="feedback.php"
                class="text-xs font-bold text-orange-500 hover:bg-orange-50 px-3 py-1.5 rounded-lg transition-all flex items-center gap-1">
                View All
                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                </svg>
            </a>
        </div>
        <div class="space-y-6">
            <?php if (empty($recent_feedback)): ?>
                <p class="text-gray-400 italic">No feedback received yet.</p>
            <?php else: ?>
                <?php foreach ($recent_feedback as $fb): ?>
                    <div class="p-4 rounded-2xl bg-gray-50 border border-gray-100">
                        <div class="flex justify-between items-start mb-2">
                            <div class="flex items-center gap-2">
                                <div class="flex text-amber-400">
                                    <?php for ($i = 0; $i < $fb['stars']; $i++): ?>⭐
                                    <?php endfor; ?>
                                </div>
                                <span class="text-xs font-bold text-gray-400 uppercase">
                                    <?= date('M j', strtotime($fb['created_at'])) ?>
                                </span>
                            </div>
                            <span class="text-xs text-gray-500 italic">
                                <?= htmlspecialchars($fb['phone']) ?>
                            </span>
                        </div>
                        <p class="text-sm text-gray-700 line-clamp-2">
                            <?= htmlspecialchars($fb['comment']) ?>
                        </p>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Real Graphical Insights -->
    <div class="glass-card rounded-[2.5rem] p-8 flex flex-col">
        <h3 class="text-xl font-bold text-gray-900 mb-6">Feedback Distribution</h3>
        <div class="flex-1 min-h-[300px] relative">
            <canvas id="feedbackChart"></canvas>
        </div>

        <?php
        // Fetch real data for the chart: count and givers for each star rating
        // Adjusted for both MySQL and SQLite compatibility
        $gc_sql = ($pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite')
            ? "group_concat(phone, ', ')"
            : "GROUP_CONCAT(phone SEPARATOR ', ')";

        $rating_data = $pdo->query("SELECT stars, COUNT(*) as count, $gc_sql as givers 
                                   FROM feedback GROUP BY stars ORDER BY stars ASC")->fetchAll(PDO::FETCH_ASSOC);

        $chart_data = array_fill(0, 5, 0);
        $chart_givers = array_fill(0, 5, '');

        foreach ($rating_data as $row) {
            $star_idx = (int) $row['stars'] - 1;
            if ($star_idx >= 0 && $star_idx < 5) {
                $chart_data[$star_idx] = (int) $row['count'];
                $chart_givers[$star_idx] = $row['givers'] ?? '';
            }
        }
        ?>

        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const ctx = document.getElementById('feedbackChart').getContext('2d');
                const giversList = <?= json_encode($chart_givers) ?>;

                new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: ['1 ⭐', '2 ⭐', '3 ⭐', '4 ⭐', '5 ⭐'],
                        datasets: [{
                            label: 'Givers',
                            data: <?= json_encode($chart_data) ?>,
                            backgroundColor: [
                                'rgba(239, 68, 68, 0.8)',   // 1 star (Red)
                                'rgba(249, 115, 22, 0.8)',  // 2 stars (Orange)
                                'rgba(234, 179, 8, 0.8)',   // 3 stars (Yellow)
                                'rgba(132, 204, 22, 0.8)',  // 4 stars (Lime)
                                'rgba(16, 185, 129, 0.8)'   // 5 stars (Emerald)
                            ],
                            borderRadius: 12,
                            borderSkipped: false,
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { display: false },
                            tooltip: {
                                callbacks: {
                                    label: function (context) {
                                        const count = context.parsed.y;
                                        return count + (count === 1 ? ' Person' : ' People');
                                    },
                                    afterLabel: function (context) {
                                        const index = context.dataIndex;
                                        const givers = giversList[index];
                                        if (givers) {
                                            const list = givers.split(', ');
                                            if (list.length > 5) {
                                                return 'Numbers: ' + list.slice(0, 5).join(', ') + '...';
                                            }
                                            return 'Numbers: ' + givers;
                                        }
                                        return '';
                                    }
                                }
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                min: 0,
                                suggestedMax: 5,
                                grid: { display: false },
                                ticks: {
                                    stepSize: 1,
                                    font: { family: 'Outfit', weight: 'bold' }
                                }
                            },
                            x: {
                                grid: { display: false },
                                ticks: { font: { family: 'Outfit', weight: 'bold' } }
                            }
                        }
                    }
                });
            });
        </script>
    </div>
</div>

<?php include 'footer.php'; ?>