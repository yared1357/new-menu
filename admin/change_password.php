<?php
session_start();
require_once '../includes/db.php';

if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit;
}

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (strlen($new_password) < 4) {
        $message = 'The new password must be at least 4 characters.';
    } elseif ($new_password !== $confirm_password) {
        $message = 'New passwords do not match.';
    } else {
        $stmt = $pdo->prepare('SELECT password FROM users WHERE id = ?');
        $stmt->execute([$_SESSION['admin_id']]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($current_password, $user['password'])) {
            $message = 'Current password is incorrect.';
        } else {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare('UPDATE users SET password = ?, must_change_password = 0 WHERE id = ?');
            if ($stmt->execute([$hashed_password, $_SESSION['admin_id']])) {
                $_SESSION['admin_must_change_password'] = false;
                $_SESSION['admin_flash'] = [
                    'title' => 'Password updated',
                    'message' => 'Your password was updated successfully.',
                ];
                header('Location: dashboard.php');
                exit;
            } else {
                $message = 'Failed to update password.';
            }
        }
    }
}

$page_title = 'Change Password';
include 'header.php';
?>

<section class="flex flex-1 flex-col items-center py-8 sm:py-12">
    <div class="w-full max-w-xl">
        <header class="mb-8">
            <span class="inline-flex items-center gap-2 rounded-full border border-emerald-800/10 bg-emerald-800/5 px-4 py-2 text-[10px] font-bold uppercase tracking-[0.2em] text-emerald-800">
                Account security
            </span>
             </header>

        <div class="glass-card rounded-[2rem] border border-white p-6 shadow-[0_20px_60px_rgba(27,52,43,0.10)] sm:p-9">
            <?php if ($message !== ''): ?>
                <div role="alert"
                    class="mb-6 rounded-2xl border border-red-100 bg-red-50 p-4 text-center text-sm font-semibold text-red-700">
                    <?= htmlspecialchars($message) ?>
                </div>
            <?php endif; ?>

            <form method="POST" class="space-y-5" x-data="{ showCurrent: false, showNew: false, showConfirm: false }">
                <label class="block text-sm font-semibold text-gray-700">
                    Current password
                    <span class="relative mt-2 block">
                        <input :type="showCurrent ? 'text' : 'password'" name="current_password" required autocomplete="current-password"
                            class="w-full rounded-2xl border border-gray-200 bg-[#f8faf8] px-5 py-4 pr-14 text-sm outline-none transition focus:border-emerald-700 focus:bg-white focus:ring-4 focus:ring-emerald-700/10">
                        <button type="button" @click="showCurrent = !showCurrent" :aria-label="showCurrent ? 'Hide current password' : 'Show current password'" :title="showCurrent ? 'Hide current password' : 'Show current password'"
                            class="absolute right-3 top-1/2 -translate-y-1/2 rounded-xl p-2 text-gray-400 transition-colors hover:bg-gray-100 hover:text-emerald-800 focus:outline-none focus:ring-2 focus:ring-emerald-700/30">
                            <svg x-show="!showCurrent" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M2.5 12s3.4-6.5 9.5-6.5 9.5 6.5 9.5 6.5-3.4 6.5-9.5 6.5S2.5 12 2.5 12Z"/><circle cx="12" cy="12" r="3" stroke-width="1.8"/></svg>
                            <svg x-show="showCurrent" x-cloak class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="m3 3 18 18M10.6 10.6a2 2 0 0 0 2.8 2.8M9.9 5.7A10.8 10.8 0 0 1 12 5.5c6.1 0 9.5 6.5 9.5 6.5a16.5 16.5 0 0 1-3.2 4.1M6.2 6.2C3.8 7.8 2.5 12 2.5 12S5.9 18.5 12 18.5a10 10 0 0 0 3-.5"/></svg>
                        </button>
                    </span>
                </label>
                <label class="block text-sm font-semibold text-gray-700">
                    New password
                    <span class="relative mt-2 block">
                        <input :type="showNew ? 'text' : 'password'" name="new_password" required minlength="4" autocomplete="new-password"
                            class="w-full rounded-2xl border border-gray-200 bg-[#f8faf8] px-5 py-4 pr-14 text-sm outline-none transition focus:border-emerald-700 focus:bg-white focus:ring-4 focus:ring-emerald-700/10">
                        <button type="button" @click="showNew = !showNew" :aria-label="showNew ? 'Hide new password' : 'Show new password'" :title="showNew ? 'Hide new password' : 'Show new password'"
                            class="absolute right-3 top-1/2 -translate-y-1/2 rounded-xl p-2 text-gray-400 transition-colors hover:bg-gray-100 hover:text-emerald-800 focus:outline-none focus:ring-2 focus:ring-emerald-700/30">
                            <svg x-show="!showNew" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M2.5 12s3.4-6.5 9.5-6.5 9.5 6.5 9.5 6.5-3.4 6.5-9.5 6.5S2.5 12 2.5 12Z"/><circle cx="12" cy="12" r="3" stroke-width="1.8"/></svg>
                            <svg x-show="showNew" x-cloak class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="m3 3 18 18M10.6 10.6a2 2 0 0 0 2.8 2.8M9.9 5.7A10.8 10.8 0 0 1 12 5.5c6.1 0 9.5 6.5 9.5 6.5a16.5 16.5 0 0 1-3.2 4.1M6.2 6.2C3.8 7.8 2.5 12 2.5 12S5.9 18.5 12 18.5a10 10 0 0 0 3-.5"/></svg>
                        </button>
                    </span>
                </label>
                <label class="block text-sm font-semibold text-gray-700">
                    Confirm new password
                    <span class="relative mt-2 block">
                        <input :type="showConfirm ? 'text' : 'password'" name="confirm_password" required minlength="4" autocomplete="new-password"
                            class="w-full rounded-2xl border border-gray-200 bg-[#f8faf8] px-5 py-4 pr-14 text-sm outline-none transition focus:border-emerald-700 focus:bg-white focus:ring-4 focus:ring-emerald-700/10">
                        <button type="button" @click="showConfirm = !showConfirm" :aria-label="showConfirm ? 'Hide confirmation password' : 'Show confirmation password'" :title="showConfirm ? 'Hide confirmation password' : 'Show confirmation password'"
                            class="absolute right-3 top-1/2 -translate-y-1/2 rounded-xl p-2 text-gray-400 transition-colors hover:bg-gray-100 hover:text-emerald-800 focus:outline-none focus:ring-2 focus:ring-emerald-700/30">
                            <svg x-show="!showConfirm" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M2.5 12s3.4-6.5 9.5-6.5 9.5 6.5 9.5 6.5-3.4 6.5-9.5 6.5S2.5 12 2.5 12Z"/><circle cx="12" cy="12" r="3" stroke-width="1.8"/></svg>
                            <svg x-show="showConfirm" x-cloak class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="m3 3 18 18M10.6 10.6a2 2 0 0 0 2.8 2.8M9.9 5.7A10.8 10.8 0 0 1 12 5.5c6.1 0 9.5 6.5 9.5 6.5s-3.2 4.1-3.2 4.1M6.2 6.2C3.8 7.8 2.5 12 2.5 12S5.9 18.5 12 18.5a10 10 0 0 0 3-.5"/></svg>
                        </button>
                    </span>
                </label>
                <button type="submit"
                    class="w-full rounded-2xl bg-gradient-to-r from-[#1f322e] via-[#17503d] to-[#246447] py-4 font-bold text-white shadow-lg transition hover:-translate-y-0.5 hover:shadow-xl focus:outline-none focus:ring-4 focus:ring-emerald-700/20">
                    Update password
                </button>
            </form>
        </div>
    </div>
</section>

<?php include 'footer.php'; ?>
