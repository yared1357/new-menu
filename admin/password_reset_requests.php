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
            $expires_at = date('Y-m-d H:i:s', time() + 600);
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
$page_title = 'Password Reset Requests';
include 'header.php';
?>

<div class="mx-auto w-full max-w-5xl">
    <header class="mb-6 rounded-2xl bg-gradient-to-r from-[#1f322e] via-[#17503d] to-[#28704f] p-6 text-white shadow-xl sm:p-8">
        <p class="text-xs font-bold uppercase tracking-[0.2em] text-emerald-100">Super Admin</p>
        <h2 class="mt-2 text-3xl font-bold tracking-tight">Password reset requests</h2>
    </header>

    <?php if ($message !== ''): ?>
        <div role="<?= $success ? 'status' : 'alert' ?>" class="mb-6 rounded-2xl border p-4 text-sm font-semibold <?= $success ? 'border-emerald-100 bg-emerald-50 text-emerald-700' : 'border-red-100 bg-red-50 text-red-700' ?>">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <section class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
        <?php if (!$requests): ?>
            <p class="px-6 py-12 text-center text-sm text-gray-500">There are no active password reset requests.</p>
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
                    $message_template = $visible_otp !== ''
                        ? 'Hello ' . $request['full_name'] . ', your password reset code for ' . get_app_setting('brand_name', 'the menu') . ' is ' . $visible_otp . '. It expires in 10 minutes. If you did not request this, ignore this message.'
                        : '';
                    ?>
                    <li class="flex flex-col gap-4 p-5 sm:p-7 lg:flex-row lg:items-center lg:justify-between">
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <h3 class="font-bold text-gray-900"><?= htmlspecialchars($request['full_name']) ?></h3>
                                <span class="rounded-full px-2.5 py-1 text-[10px] font-bold <?= $request['status'] === 'verified' ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-800' ?>">
                                    <?= htmlspecialchars($status_label) ?>
                                </span>
                            </div>
                            <p class="mt-1 text-sm text-gray-600"><?= htmlspecialchars($request['phone']) ?></p>
                            <p class="mt-1 text-xs text-gray-500">Requested <?= htmlspecialchars($request['created_at']) ?></p>
                            <?php if ($visible_otp !== ''): ?>
                                <div class="mt-4 flex flex-wrap items-center gap-2">
                                    <code class="rounded-lg border border-emerald-100 bg-emerald-50 px-3 py-2 font-mono text-lg font-bold tracking-[0.2em] text-emerald-900"><?= htmlspecialchars($visible_otp) ?></code>
                                    <button type="button" data-copy-text="<?= htmlspecialchars($visible_otp, ENT_QUOTES, 'UTF-8') ?>" @click="navigator.clipboard.writeText($el.dataset.copyText)"
                                        class="rounded-lg border border-emerald-200 bg-white px-3 py-2 text-xs font-bold text-emerald-800 transition hover:bg-emerald-50">Copy code</button>
                                    <button type="button" data-copy-text="<?= htmlspecialchars($message_template, ENT_QUOTES, 'UTF-8') ?>" @click="navigator.clipboard.writeText($el.dataset.copyText)"
                                        class="rounded-lg border border-emerald-200 bg-white px-3 py-2 text-xs font-bold text-emerald-800 transition hover:bg-emerald-50">Copy message</button>
                                </div>
                            <?php endif; ?>
                        </div>
                        <?php if ($request['status'] === 'pending' || $request['status'] === 'otp_sent'): ?>
                            <form method="POST" class="shrink-0">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['password_reset_token']) ?>">
                                <input type="hidden" name="action" value="generate">
                                <input type="hidden" name="request_id" value="<?= (int) $request['id'] ?>">
                                <button type="submit" class="w-full rounded-xl bg-gradient-to-r from-[#1f322e] to-emerald-700 px-4 py-2.5 text-sm font-bold text-white transition hover:to-emerald-800 sm:w-auto">
                                    <?= $request['status'] === 'pending' ? 'Generate 6-digit code' : ($expired ? 'Generate new code' : 'Regenerate code') ?>
                                </button>
                            </form>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>
</div>

<?php include 'footer.php'; ?>
