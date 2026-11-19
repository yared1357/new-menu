<?php
session_start();
require_once '../includes/db.php';

if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit;
}

$message = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if ($new_password !== $confirm_password) {
        $message = 'New passwords do not match.';
    } else {
        $stmt = $pdo->prepare('SELECT password FROM users WHERE id = ?');
        $stmt->execute([$_SESSION['admin_id']]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($current_password, $user['password'])) {
            $message = 'Current password is incorrect.';
        } else {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare('UPDATE users SET password = ? WHERE id = ?');
            if ($stmt->execute([$hashed_password, $_SESSION['admin_id']])) {
                $message = 'Password updated successfully.';
                $success = true;
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
            <h2 class="mt-4 text-3xl font-bold tracking-tight text-[#20332b] sm:text-4xl">Change password</h2>
            <p class="mt-2 text-sm text-gray-500">Update the password you use to access the admin panel.</p>
        </header>

        <div class="glass-card rounded-[2rem] border border-white p-6 shadow-[0_20px_60px_rgba(27,52,43,0.10)] sm:p-9">
            <?php if ($message !== ''): ?>
                <div role="<?= $success ? 'status' : 'alert' ?>"
                    class="mb-6 rounded-2xl border p-4 text-center text-sm font-semibold <?= $success ? 'border-emerald-100 bg-emerald-50 text-emerald-700' : 'border-red-100 bg-red-50 text-red-700' ?>">
                    <?= htmlspecialchars($message) ?>
                </div>
            <?php endif; ?>

            <form method="POST" class="space-y-5">
                <label class="block text-sm font-semibold text-gray-700">
                    Current password
                    <input type="password" name="current_password" required autocomplete="current-password"
                        class="mt-2 w-full rounded-2xl border border-gray-200 bg-[#f8faf8] px-5 py-4 text-sm outline-none transition focus:border-emerald-700 focus:bg-white focus:ring-4 focus:ring-emerald-700/10">
                </label>
                <label class="block text-sm font-semibold text-gray-700">
                    New password
                    <input type="password" name="new_password" required autocomplete="new-password"
                        class="mt-2 w-full rounded-2xl border border-gray-200 bg-[#f8faf8] px-5 py-4 text-sm outline-none transition focus:border-emerald-700 focus:bg-white focus:ring-4 focus:ring-emerald-700/10">
                </label>
                <label class="block text-sm font-semibold text-gray-700">
                    Confirm new password
                    <input type="password" name="confirm_password" required autocomplete="new-password"
                        class="mt-2 w-full rounded-2xl border border-gray-200 bg-[#f8faf8] px-5 py-4 text-sm outline-none transition focus:border-emerald-700 focus:bg-white focus:ring-4 focus:ring-emerald-700/10">
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
