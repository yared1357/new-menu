<?php
session_start();
require_once '../includes/db.php';

$request_id = (int) ($_SESSION['password_reset_verified_id'] ?? 0);
if ($request_id < 1) {
    header('Location: forgot_password.php');
    exit;
}

$stmt = $pdo->prepare("SELECT r.user_id, r.verified_at, r.phone, u.phone AS current_phone
                      FROM password_reset_requests r
                      INNER JOIN users u ON u.id = r.user_id
                      WHERE r.id = ? AND r.status = 'verified'");
$stmt->execute([$request_id]);
$request = $stmt->fetch();
if (!$request
    || $request['phone'] !== $request['current_phone']
    || !$request['verified_at']
    || strtotime($request['verified_at']) < time() - 600) {
    unset($_SESSION['password_reset_verified_id'], $_SESSION['password_reset_request_id']);
    header('Location: forgot_password.php');
    exit;
}

if (empty($_SESSION['password_reset_form_token'])) {
    $_SESSION['password_reset_form_token'] = bin2hex(random_bytes(32));
}

$message = '';
$error = '';
$complete = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['password_reset_form_token'], $_POST['csrf_token'] ?? '')) {
        $error = 'Your session expired. Refresh the page and try again.';
    } else {
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        if (strlen($new_password) < 4) {
            $error = 'The new password must be at least 4 characters.';
        } elseif ($new_password !== $confirm_password) {
            $error = 'The passwords do not match.';
        } else {
            try {
                $pdo->beginTransaction();
                $stmt = $pdo->prepare('UPDATE users SET password = ?, must_change_password = 0 WHERE id = ?');
                $stmt->execute([password_hash($new_password, PASSWORD_DEFAULT), $request['user_id']]);
                $stmt = $pdo->prepare("UPDATE password_reset_requests SET status = 'completed', used_at = CURRENT_TIMESTAMP, otp_hash = NULL WHERE user_id = ? AND status IN ('pending', 'otp_sent', 'verified')");
                $stmt->execute([$request['user_id']]);
                $pdo->commit();

                unset($_SESSION['password_reset_request_id'], $_SESSION['password_reset_verified_id']);
                $message = 'Your password has been updated. You can now sign in with your new password.';
                $complete = true;
            } catch (PDOException $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                $error = 'Could not update your password. Please try again.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Choose New Password | <?= htmlspecialchars(get_app_setting('brand_name', 'Menu')) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="../assets/js/alpine.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>body{font-family:'Outfit',sans-serif}</style>
</head>
<body class="flex min-h-screen items-center justify-center bg-[#f4f7f4] px-4 py-8 text-[#20332b]">
    <main class="w-full max-w-md overflow-hidden rounded-3xl border border-white bg-white shadow-2xl shadow-emerald-950/10">
        <div class="bg-gradient-to-br from-[#1f322e] via-[#17503d] to-[#28704f] p-6 text-white sm:p-8">
            <a href="login.php" class="text-xs font-semibold text-emerald-100 hover:text-white">&larr; Back to sign in</a>
            <p class="mt-7 text-xs font-bold uppercase tracking-[0.2em] text-emerald-100">Verified recovery</p>
            <h1 class="mt-2 text-3xl font-bold tracking-tight">Choose a new password</h1>
        </div>
        <div class="p-6 sm:p-8">
            <ol aria-label="Password recovery steps" class="mb-6 grid grid-cols-3 gap-2 text-center text-[10px] font-bold sm:text-xs">
                <li class="flex flex-col items-center gap-1.5 text-emerald-700"><span class="flex h-7 w-7 items-center justify-center rounded-full bg-emerald-100">1</span>Request</li>
                <li class="flex flex-col items-center gap-1.5 text-emerald-700"><span class="flex h-7 w-7 items-center justify-center rounded-full bg-emerald-100">2</span>Verify code</li>
                <li class="flex flex-col items-center gap-1.5 text-emerald-900"><span class="flex h-7 w-7 items-center justify-center rounded-full bg-emerald-800 text-white">3</span>New password</li>
            </ol>
            <?php if ($error !== ''): ?>
                <div role="alert" class="mb-5 rounded-xl border border-red-100 bg-red-50 p-3 text-sm font-semibold text-red-700"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            <?php if ($complete): ?>
                <div role="status" class="rounded-xl border border-emerald-100 bg-emerald-50 p-4 text-sm font-semibold leading-6 text-emerald-800">
                    <?= htmlspecialchars($message) ?>
                    <a href="login.php" class="mt-3 block font-bold underline">Go to sign in</a>
                </div>
            <?php else: ?>
                <p class="mb-5 text-sm leading-6 text-gray-600">Choose a new password with at least 4 characters.</p>
                <form method="POST" class="space-y-4" x-data="{ showNew: false, showConfirm: false }">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['password_reset_form_token']) ?>">
                    <label class="block text-sm font-semibold text-gray-700">New password
                        <span class="relative mt-2 block">
                            <input :type="showNew ? 'text' : 'password'" name="new_password" required minlength="4" autocomplete="new-password" class="w-full rounded-xl border border-gray-300 bg-gray-50 px-4 py-3 pr-12 text-gray-900 outline-none focus:border-emerald-700 focus:ring-4 focus:ring-emerald-700/10">
                            <button type="button" @click="showNew = !showNew" :aria-label="showNew ? 'Hide password' : 'Show password'" class="absolute right-2 top-1/2 -translate-y-1/2 rounded-lg p-2 text-gray-500 hover:bg-gray-200">
                                <svg x-show="!showNew" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M2 12s3.6-6 10-6 10 6 10 6-3.6 6-10 6S2 12 2 12Zm10 3a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z"/></svg>
                                <svg x-show="showNew" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="m3 3 18 18M10.6 6.1A10.7 10.7 0 0 1 12 6c6.4 0 10 6 10 6a17 17 0 0 1-3.1 3.8M6.2 6.2C3.5 7.7 2 12 2 12s3.6 6 10 6a10 10 0 0 0 3-.5M9.9 9.9a3 3 0 0 0 4.2 4.2"/></svg>
                            </button>
                        </span>
                    </label>
                    <label class="block text-sm font-semibold text-gray-700">Confirm new password
                        <span class="relative mt-2 block">
                            <input :type="showConfirm ? 'text' : 'password'" name="confirm_password" required minlength="4" autocomplete="new-password" class="w-full rounded-xl border border-gray-300 bg-gray-50 px-4 py-3 pr-12 text-gray-900 outline-none focus:border-emerald-700 focus:ring-4 focus:ring-emerald-700/10">
                            <button type="button" @click="showConfirm = !showConfirm" :aria-label="showConfirm ? 'Hide confirmation password' : 'Show confirmation password'" class="absolute right-2 top-1/2 -translate-y-1/2 rounded-lg p-2 text-gray-500 hover:bg-gray-200">
                                <svg x-show="!showConfirm" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M2 12s3.6-6 10-6 10 6 10 6-3.6 6-10 6S2 12 2 12Zm10 3a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z"/></svg>
                                <svg x-show="showConfirm" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="m3 3 18 18M10.6 6.1A10.7 10.7 0 0 1 12 6c6.4 0 10 6 10 6a17 17 0 0 1-3.1 3.8M6.2 6.2C3.5 7.7 2 12 2 12s3.6 6 10 6a10 10 0 0 0 3-.5M9.9 9.9a3 3 0 0 0 4.2 4.2"/></svg>
                            </button>
                        </span>
                    </label>
                    <button type="submit" class="w-full rounded-xl bg-emerald-800 px-4 py-3 font-bold text-white transition hover:bg-emerald-900 focus:outline-none focus:ring-4 focus:ring-emerald-700/20">Save new password</button>
                </form>
            <?php endif; ?>
        </div>
    </main>
</body>
</html>
