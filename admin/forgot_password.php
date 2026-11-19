<?php
session_start();
require_once '../includes/db.php';

if (isset($_SESSION['admin_logged_in'])) {
    header('Location: ' . (!empty($_SESSION['admin_must_change_password']) ? 'change_password.php' : 'dashboard.php'));
    exit;
}

if (empty($_SESSION['password_reset_form_token'])) {
    $_SESSION['password_reset_form_token'] = bin2hex(random_bytes(32));
}

$message = '';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['password_reset_form_token'], $_POST['csrf_token'] ?? '')) {
        $error = 'Your session expired. Refresh the page and try again.';
    } elseif (($_POST['action'] ?? '') === 'request') {
        unset($_SESSION['password_reset_request_id'], $_SESSION['password_reset_verified_id']);
        $phone = normalize_ethiopian_phone($_POST['phone'] ?? '');

        if ($phone === null) {
            $error = 'Enter the Ethiopian mobile number registered to the account.';
        } else {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE phone = ? AND role <> 'Super Admin'");
            $stmt->execute([$phone]);
            $matching_users = $stmt->fetchAll();
            $user = count($matching_users) === 1 ? $matching_users[0] : null;

            if ($user) {
                $stmt = $pdo->prepare("SELECT id FROM password_reset_requests WHERE user_id = ? AND status IN ('pending', 'otp_sent') ORDER BY id DESC LIMIT 1");
                $stmt->execute([$user['id']]);
                $request_id = $stmt->fetchColumn();

                if (!$request_id) {
                    $stmt = $pdo->prepare("INSERT INTO password_reset_requests (user_id, phone, status) VALUES (?, ?, 'pending')");
                    $stmt->execute([$user['id'], $phone]);
                    $request_id = $pdo->lastInsertId();
                }
                $_SESSION['password_reset_request_id'] = (int) $request_id;
            }

            $message = 'If those details match an admin account, a password reset request has been sent to the Super Admin.';
        }
    } elseif (($_POST['action'] ?? '') === 'verify') {
        $request_id = (int) ($_SESSION['password_reset_request_id'] ?? 0);
        $otp = trim($_POST['otp'] ?? '');
        $stmt = $pdo->prepare("SELECT r.*, u.phone AS current_phone FROM password_reset_requests r INNER JOIN users u ON u.id = r.user_id WHERE r.id = ? AND r.status = 'otp_sent'");
        $stmt->execute([$request_id]);
        $request = $stmt->fetch();

        if (!$request || $request['phone'] !== $request['current_phone']) {
            $error = 'No active code is available for this request. Submit a new request or contact the Super Admin.';
        } elseif (strtotime($request['otp_expires_at'] ?? '') <= time()) {
            $pdo->prepare("UPDATE password_reset_requests SET status = 'pending', otp_hash = NULL, otp_expires_at = NULL, attempts = 0 WHERE id = ?")
                ->execute([$request_id]);
            $error = 'That code has expired. Ask the Super Admin to generate a new one.';
        } elseif ((int) $request['attempts'] >= 5) {
            $pdo->prepare("UPDATE password_reset_requests SET status = 'pending', otp_hash = NULL, otp_expires_at = NULL WHERE id = ?")
                ->execute([$request_id]);
            $error = 'Too many incorrect attempts. Ask the Super Admin to generate a new code.';
        } elseif (!preg_match('/\A[0-9]{6}\z/', $otp) || !password_verify($otp, $request['otp_hash'] ?? '')) {
            $attempts = (int) $request['attempts'] + 1;
            if ($attempts >= 5) {
                $pdo->prepare("UPDATE password_reset_requests SET status = 'pending', otp_hash = NULL, otp_expires_at = NULL, attempts = 0 WHERE id = ?")
                    ->execute([$request_id]);
                $error = 'Too many incorrect attempts. Ask the Super Admin to generate a new code.';
            } else {
                $pdo->prepare('UPDATE password_reset_requests SET attempts = ? WHERE id = ?')->execute([$attempts, $request_id]);
                $error = 'The code is incorrect. Check it and try again.';
            }
        } else {
            $pdo->prepare("UPDATE password_reset_requests SET status = 'verified', verified_at = CURRENT_TIMESTAMP WHERE id = ?")
                ->execute([$request_id]);
            $_SESSION['password_reset_verified_id'] = $request_id;
            header('Location: reset_password.php');
            exit;
        }
    }
}

$request = null;
$request_id = (int) ($_SESSION['password_reset_request_id'] ?? 0);
if ($request_id > 0) {
    $stmt = $pdo->prepare('SELECT id, status, otp_expires_at FROM password_reset_requests WHERE id = ?');
    $stmt->execute([$request_id]);
    $request = $stmt->fetch() ?: null;
    if ($request && $request['status'] === 'otp_sent' && strtotime($request['otp_expires_at'] ?? '') <= time()) {
        $pdo->prepare("UPDATE password_reset_requests SET status = 'pending', otp_hash = NULL, otp_expires_at = NULL, attempts = 0 WHERE id = ?")
            ->execute([$request_id]);
        $request['status'] = 'pending';
    }
    if ($request && !in_array($request['status'], ['pending', 'otp_sent'], true)) {
        unset($_SESSION['password_reset_request_id']);
        $request = null;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password | <?= htmlspecialchars(get_app_setting('brand_name', 'Menu')) ?></title>
    <?php if ($request && $request['status'] === 'pending'): ?>
        <meta http-equiv="refresh" content="8">
    <?php endif; ?>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>body{font-family:'Outfit',sans-serif}</style>
</head>
<body class="flex min-h-screen items-center justify-center bg-[#f4f7f4] px-4 py-8 text-[#20332b]">
    <main class="w-full max-w-md overflow-hidden rounded-3xl border border-white bg-white shadow-2xl shadow-emerald-950/10">
        <div class="bg-gradient-to-br from-[#1f322e] via-[#17503d] to-[#28704f] p-6 text-white sm:p-8">
            <a href="login.php" class="text-xs font-semibold text-emerald-100 hover:text-white">&larr; Back to sign in</a>
            <p class="mt-7 text-xs font-bold uppercase tracking-[0.2em] text-emerald-100">Account recovery</p>
            <h1 class="mt-2 text-3xl font-bold tracking-tight">Forgot password?</h1>
        </div>
        <div class="p-6 sm:p-8">
            <?php if ($error !== ''): ?>
                <div role="alert" class="mb-5 rounded-xl border border-red-100 bg-red-50 p-3 text-sm font-semibold text-red-700"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            <?php if ($message !== ''): ?>
                <div role="status" class="mb-5 rounded-xl border border-emerald-100 bg-emerald-50 p-3 text-sm font-semibold text-emerald-800"><?= htmlspecialchars($message) ?></div>
            <?php endif; ?>

            <?php if (!$request): ?>
                <p class="mb-5 text-sm leading-6 text-gray-600">Enter your registered phone number. The Super Admin will verify your request and send a one-time code.</p>
                <form method="POST" class="space-y-4">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['password_reset_form_token']) ?>">
                    <input type="hidden" name="action" value="request">
                    <label class="block text-sm font-semibold text-gray-700">Registered phone number <span class="ml-1 inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2 py-0.5 text-xs font-bold text-emerald-800">🇪🇹 +251</span>
                        <input type="tel" name="phone" required maxlength="20" autocomplete="tel" inputmode="tel" placeholder="09..., 07..., or +251..." class="mt-2 w-full rounded-xl border border-gray-300 bg-gray-50 px-4 py-3 text-gray-900 outline-none focus:border-emerald-700 focus:ring-4 focus:ring-emerald-700/10">
                    </label>
                    <button type="submit" class="w-full rounded-xl bg-emerald-800 px-4 py-3 font-bold text-white transition hover:bg-emerald-900 focus:outline-none focus:ring-4 focus:ring-emerald-700/20">Request reset code</button>
                </form>
            <?php elseif ($request['status'] === 'pending'): ?>
                <div class="rounded-2xl border border-amber-200 bg-amber-50 p-4">
                    <p class="font-bold text-amber-900">Request sent to the Super Admin</p>
                    <p class="mt-1 text-sm leading-5 text-amber-800">This page checks for the code automatically. Enter it here after the Super Admin sends it to your registered phone.</p>
                </div>
                <a href="forgot_password.php" class="mt-5 inline-flex text-sm font-semibold text-emerald-800 hover:text-emerald-950">Check for an updated request</a>
            <?php else: ?>
                <p class="mb-5 text-sm leading-6 text-gray-600">Enter the six-digit code sent to your registered phone. The code expires after 10 minutes.</p>
                <form method="POST" class="space-y-4">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['password_reset_form_token']) ?>">
                    <input type="hidden" name="action" value="verify">
                    <label class="block text-sm font-semibold text-gray-700">6-digit code
                        <input name="otp" required minlength="6" maxlength="6" pattern="[0-9]{6}" inputmode="numeric" autocomplete="one-time-code" class="mt-2 w-full rounded-xl border border-gray-300 bg-gray-50 px-4 py-3 text-center font-mono text-2xl font-bold tracking-[0.35em] text-gray-900 outline-none focus:border-emerald-700 focus:ring-4 focus:ring-emerald-700/10">
                    </label>
                    <button type="submit" class="w-full rounded-xl bg-emerald-800 px-4 py-3 font-bold text-white transition hover:bg-emerald-900 focus:outline-none focus:ring-4 focus:ring-emerald-700/20">Verify code</button>
                </form>
            <?php endif; ?>
        </div>
    </main>
</body>
</html>
