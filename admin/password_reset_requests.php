<?php
session_start();
require_once '../includes/db.php';

if (!isset($_SESSION['admin_logged_in'], $_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

$current_admin_query = $pdo->prepare('SELECT username, role FROM users WHERE id = ?');
$current_admin_query->execute([$_SESSION['admin_id']]);
$current_admin = $current_admin_query->fetch();
$is_super_admin = $current_admin
    && strtolower(trim($current_admin['username'])) === 'yared'
    && $current_admin['role'] === 'Super Admin';

if (!$is_super_admin) {
    http_response_code(403);
    exit('You do not have permission to manage password reset requests.');
}

if (empty($_SESSION['password_reset_token'])) {
    $_SESSION['password_reset_token'] = bin2hex(random_bytes(32));
}

if (($_GET['ajax'] ?? '') === 'request_status') {
    $since_id = filter_var($_GET['since_id'] ?? '0', FILTER_VALIDATE_INT);
    if ($since_id === false || $since_id < 0) {
        http_response_code(400);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['error' => 'Invalid request cursor.']);
        exit;
    }

    $status = $pdo->prepare("SELECT
                                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) AS pending_count,
                                COALESCE(MAX(id), 0) AS latest_id,
                                SUM(CASE WHEN id > ? THEN 1 ELSE 0 END) AS new_request_count
                             FROM password_reset_requests
                             WHERE status IN ('pending', 'otp_sent', 'verified')");
    $status->execute([$since_id]);
    $request_status = $status->fetch();
    $active_ids = $pdo->query("SELECT id
                               FROM password_reset_requests
                               WHERE status IN ('pending', 'otp_sent', 'verified')
                               ORDER BY id DESC")->fetchAll(PDO::FETCH_COLUMN);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store, private');
    echo json_encode([
        'pending_count' => (int) ($request_status['pending_count'] ?? 0),
        'latest_id' => (int) ($request_status['latest_id'] ?? 0),
        'new_request_count' => (int) ($request_status['new_request_count'] ?? 0),
        'active_request_ids' => array_map('intval', $active_ids),
    ]);
    exit;
}

$message = '';
$success = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['password_reset_token'], $_POST['csrf_token'] ?? '')) {
        $message = 'Your session expired. Refresh the page and try again.';
    } elseif (($_POST['action'] ?? '') === 'generate') {
        $request_id = filter_var($_POST['request_id'] ?? null, FILTER_VALIDATE_INT);
        $stmt = $pdo->prepare("SELECT id, status, phone FROM password_reset_requests WHERE id = ? AND status IN ('pending', 'otp_sent')");
        $stmt->execute([$request_id]);
        $request = $stmt->fetch();

        if (!$request) {
            $message = 'This reset request is no longer available.';
        } else {
            $otp = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            $expires_at = date('Y-m-d H:i:s', time() + 10800);
            $stmt = $pdo->prepare("UPDATE password_reset_requests SET status = 'otp_sent', otp_hash = ?, attempts = 0, otp_expires_at = ?, verified_at = NULL WHERE id = ?");
            $stmt->execute([password_hash($otp, PASSWORD_DEFAULT), $expires_at, $request_id]);
            $_SESSION['generated_reset_otp'] = [$request_id => $otp];
            $message = 'A new one-time code was generated. Copy the code or message below and send it to the admin.';
            $success = true;
        }
    }
}

$visible_otps = $_SESSION['generated_reset_otp'] ?? [];
unset($_SESSION['generated_reset_otp']);
$requests = $pdo->query("SELECT r.id, r.user_id, r.phone, r.status, r.created_at, r.otp_expires_at, u.full_name
                         FROM password_reset_requests r
                         INNER JOIN users u ON u.id = r.user_id
                         WHERE r.status IN ('pending', 'otp_sent', 'verified')
                         ORDER BY r.created_at DESC")->fetchAll();
$request_stats = $pdo->query("SELECT
                                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) AS pending_count,
                                COALESCE(MAX(id), 0) AS latest_id
                             FROM password_reset_requests
                             WHERE status IN ('pending', 'otp_sent', 'verified')")->fetch();
$page_title = 'Password Reset Requests';
include 'header.php';
?>

<div class="mx-auto w-full max-w-5xl"
    x-data="{
        search: '',
        pendingCount: <?= (int) ($request_stats['pending_count'] ?? 0) ?>,
        activeRequestCount: <?= count($requests) ?>,
        activeRequestIds: <?= json_encode(array_map('intval', array_column($requests, 'id'))) ?>,
        latestRequestId: <?= (int) ($request_stats['latest_id'] ?? 0) ?>,
        newRequestCount: 0,
        pollError: false,
        async checkRequests() {
            try {
                const response = await fetch('password_reset_requests.php?ajax=request_status&since_id=' + this.latestRequestId, {
                    headers: { Accept: 'application/json' },
                    cache: 'no-store'
                });
                if (!response.ok) {
                    throw new Error('Request status check failed with HTTP ' + response.status);
                }
                const status = await response.json();
                this.pendingCount = status.pending_count;
                this.newRequestCount = status.new_request_count;
                this.activeRequestIds = status.active_request_ids;
                this.activeRequestCount = status.active_request_ids.length;
                this.pollError = false;
            } catch (error) {
                console.error('Unable to check for new password reset requests.', error);
                this.pollError = true;
            }
        }
    }"
    x-init="setInterval(() => { if (!document.hidden) checkRequests() }, 15000)">
    <header class="mb-6 flex justify-end">
        <span class="inline-flex items-center gap-1.5 rounded-full border border-emerald-100 bg-emerald-50 px-3 py-2 text-xs font-semibold text-emerald-800">
            <span class="relative flex h-2 w-2">
                <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-60"></span>
                <span class="relative inline-flex h-2 w-2 rounded-full bg-emerald-500"></span>
            </span>
            <span><span x-text="pendingCount"><?= (int) ($request_stats['pending_count'] ?? 0) ?></span> pending</span>
        </span>
    </header>

    <div x-show="newRequestCount > 0" x-cloak x-transition role="status" aria-live="polite"
        class="mb-5 flex flex-col gap-3 rounded-2xl border border-amber-200 bg-amber-50 p-4 text-amber-950 shadow-sm sm:flex-row sm:items-center sm:justify-between">
        <div class="flex items-start gap-3">
            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-amber-100 text-amber-800">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9m-8 12a2 2 0 0 0 4 0" />
                </svg>
            </span>
            <div>
                <p class="font-bold"><span x-text="newRequestCount"></span> new password reset request<span x-show="newRequestCount !== 1">s</span></p>
                <p class="mt-1 text-sm text-amber-900/75">Use the page refresh button to review the latest requests.</p>
            </div>
        </div>
    </div>

    <div x-show="pollError" x-cloak role="alert"
        class="mb-5 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-800">
        Live request updates are temporarily unavailable. Reload this page to check for new requests.
    </div>

    <?php if ($message !== ''): ?>
        <div role="<?= $success ? 'status' : 'alert' ?>" class="mb-6 rounded-2xl border p-4 text-sm font-semibold <?= $success ? 'border-emerald-100 bg-emerald-50 text-emerald-700' : 'border-red-100 bg-red-50 text-red-700' ?>">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <section class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
        <div class="flex items-center justify-between gap-2 border-b border-gray-100 px-3 py-3 sm:gap-4 sm:px-6 sm:py-4">
            <div class="min-w-0 shrink-0">
                <h3 class="truncate text-sm font-bold text-[#20332b] sm:text-base">Active requests</h3>
                <p class="mt-0.5 whitespace-nowrap text-[10px] text-gray-500 sm:mt-1 sm:text-xs"><span x-text="activeRequestCount"><?= count($requests) ?></span> request<span x-show="activeRequestCount !== 1">s</span> listed</p>
            </div>
            <label class="relative block min-w-0 flex-1 sm:max-w-xs">
                <span class="sr-only">Search reset requests</span>
                <svg class="pointer-events-none absolute left-2.5 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-gray-500 sm:left-3 sm:h-4 sm:w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="7" stroke-width="1.8"/><path stroke-linecap="round" stroke-width="1.8" d="m16 16 4 4"/></svg>
                <input type="search" x-model="search" placeholder="Search reset requests"
                    class="w-full rounded-lg border border-gray-300 bg-gray-50 py-2 pl-8 pr-2 text-[10px] text-gray-900 outline-none transition placeholder:text-gray-500 focus:border-emerald-700 focus:ring-4 focus:ring-emerald-700/10 sm:rounded-xl sm:py-2.5 sm:pl-10 sm:pr-4 sm:text-sm">
            </label>
        </div>
        <?php if (!$requests): ?>
            <div class="px-6 py-12 text-center">
                <span class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-700">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="m5 12 4 4L19 6" /></svg>
                </span>
                <p class="mt-3 text-sm font-semibold text-gray-800">All caught up</p>
                <p class="mt-1 text-sm text-gray-500">There are no active password reset requests.</p>
            </div>
        <?php else: ?>
            <ul class="divide-y divide-gray-100">
                <?php foreach ($requests as $request): ?>
                    <?php
                    $visible_otp = $visible_otps[$request['id']] ?? '';
                    $expired = $request['status'] === 'otp_sent'
                        && $request['otp_expires_at'] !== null
                        && strtotime($request['otp_expires_at']) <= time();
                    $status_label = $request['status'] === 'pending'
                        ? 'Waiting for code'
                        : ($request['status'] === 'verified' ? 'Code verified' : ($expired ? 'Code expired' : 'Code sent'));
                    $status_class = $request['status'] === 'verified'
                        ? 'bg-emerald-50 text-emerald-700'
                        : ($expired ? 'bg-rose-50 text-rose-700' : ($request['status'] === 'pending' ? 'bg-amber-50 text-amber-800' : 'bg-sky-50 text-sky-700'));
                    $message_template = $visible_otp !== ''
                        ? 'Hello ' . $request['full_name'] . ', your password reset code for ' . get_app_setting('brand_name', 'the menu') . ' is ' . $visible_otp . '. It expires in 3 hours. If you did not request this, ignore this message.'
                        : '';
                    ?>
                    <?php $requested_at = strtotime($request['created_at']); ?>
                    <li data-request-search="<?= htmlspecialchars($request['full_name'] . ' ' . $request['phone'] . ' ' . $status_label . ' ' . $request['created_at'], ENT_QUOTES, 'UTF-8') ?>"
                        x-show="activeRequestIds.includes(<?= (int) $request['id'] ?>) && (!search.trim() || $el.dataset.requestSearch.toLowerCase().includes(search.trim().toLowerCase()))"
                        class="flex min-w-0 items-center gap-2 px-3 py-3 sm:gap-5 sm:px-6 sm:py-4">
                        <div class="min-w-0 flex-1">
                            <div class="flex min-w-0 flex-wrap items-center gap-x-2 gap-y-1">
                                <h3 class="min-w-0 max-w-full truncate text-sm font-bold text-gray-900 sm:text-base"><?= htmlspecialchars($request['full_name']) ?></h3>
                                <span class="shrink-0 rounded-full px-2 py-1 text-[9px] font-bold sm:px-2.5 sm:text-[10px] <?= $status_class ?>">
                                    <?= htmlspecialchars($status_label) ?>
                                </span>
                            </div>
                            <div class="mt-1 flex min-w-0 flex-col gap-0.5 text-[10px] text-gray-500 sm:flex-row sm:flex-wrap sm:items-center sm:gap-x-3 sm:gap-y-1 sm:text-xs">
                                <span class="truncate font-medium text-gray-700"><?= htmlspecialchars($request['phone']) ?></span>
                                <span class="truncate">Requested <?= $requested_at ? htmlspecialchars(date('M j, Y · g:i A', $requested_at)) : htmlspecialchars($request['created_at']) ?></span>
                            </div>
                            <?php if ($visible_otp !== ''): ?>
                                <div class="mt-4 flex flex-wrap items-center gap-2">
                                    <code class="rounded-lg border border-emerald-100 bg-emerald-50 px-3 py-2 font-mono text-lg font-bold tracking-[0.2em] text-emerald-900"><?= htmlspecialchars($visible_otp) ?></code>
                                    <button type="button" x-data="{ copied: false }" data-copy-text="<?= htmlspecialchars($visible_otp, ENT_QUOTES, 'UTF-8') ?>" @click="navigator.clipboard.writeText($el.dataset.copyText).then(() => { copied = true; setTimeout(() => copied = false, 3000) })" :aria-label="copied ? 'Code copied' : 'Copy code'" aria-live="polite"
                                        class="rounded-lg border border-emerald-200 bg-white px-3 py-2 text-xs font-bold text-emerald-800 transition hover:bg-emerald-50"><span x-text="copied ? 'Copied' : 'Copy code'">Copy code</span></button>
                                    <button type="button" x-data="{ copied: false }" data-copy-text="<?= htmlspecialchars($message_template, ENT_QUOTES, 'UTF-8') ?>" @click="navigator.clipboard.writeText($el.dataset.copyText).then(() => { copied = true; setTimeout(() => copied = false, 3000) })" :aria-label="copied ? 'Message copied' : 'Copy message'" aria-live="polite"
                                        class="rounded-lg border border-emerald-200 bg-white px-3 py-2 text-xs font-bold text-emerald-800 transition hover:bg-emerald-50"><span x-text="copied ? 'Copied' : 'Copy message'">Copy message</span></button>
                                </div>
                            <?php endif; ?>
                        </div>
                        <?php if ($request['status'] === 'pending' || $request['status'] === 'otp_sent'): ?>
                            <form method="POST" class="shrink-0">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['password_reset_token']) ?>">
                                <input type="hidden" name="action" value="generate">
                                <input type="hidden" name="request_id" value="<?= (int) $request['id'] ?>">
                                <button type="submit" class="whitespace-nowrap rounded-lg bg-gradient-to-r from-[#1f322e] to-emerald-700 px-2.5 py-2 text-[10px] font-bold leading-tight text-white shadow-sm transition hover:to-emerald-800 focus:outline-none focus:ring-4 focus:ring-emerald-700/20 sm:rounded-xl sm:px-4 sm:py-2.5 sm:text-sm">
                                    <span class="sm:hidden"><?= $request['status'] === 'pending' ? 'Generate' : ($expired ? 'New code' : 'Regenerate') ?></span>
                                    <span class="hidden sm:inline"><?= $request['status'] === 'pending' ? 'Generate 6-digit code' : ($expired ? 'Generate new code' : 'Regenerate code') ?></span>
                                </button>
                            </form>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
                <li x-show="search.trim() && !$el.parentElement.querySelector('[data-request-search]:not([style*=\'display: none\'])')"
                    x-cloak class="px-5 py-10 text-center text-sm text-gray-500 sm:px-6">
                    No reset requests match your search.
                </li>
                <li x-show="activeRequestCount === 0" x-cloak
                    class="px-5 py-10 text-center text-sm text-gray-500 sm:px-6">
                    There are no active password reset requests.
                </li>
            </ul>
        <?php endif; ?>
    </section>
</div>

<?php include 'footer.php'; ?>
