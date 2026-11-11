<?php
session_start();
require_once '../includes/db.php';

// Auth check
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit;
}

$message = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    if ($new_password !== $confirm_password) {
        $message = "Passwords do not match!";
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['admin_id']]);
        $user = $stmt->fetch();

        if ($user && password_verify($current_password, $user['password'])) {
            $hashed = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            if ($stmt->execute([$hashed, $_SESSION['admin_id']])) {
                $message = "Password updated successfully!";
                $success = true;
            } else {
                $message = "Failed to update password.";
            }
        } else {
            $message = "Incorrect current password.";
        }
    }
}

$page_title = "Account Settings";
include 'header.php';
?>

<header class="mb-10">
    <h2 class="text-3xl font-bold text-gray-900">Change PassWord</h2>
</header>

<div class="max-w-xl">
    <div class="glass-card rounded-[2.5rem] p-10">
        <h3 class="text-xl font-bold mb-6 text-gray-900">Change Password</h3>

        <?php if ($message): ?>
            <div
                class="border p-4 rounded-2xl mb-6 text-sm font-semibold text-center <?= $success ? 'bg-emerald-50 text-emerald-600 border-emerald-100' : 'bg-red-50 text-red-600 border-red-100' ?>">
                <?= $message ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="space-y-6" x-data="{ showCurr: false, showNew: false, showConf: false }">
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-2">Current Password</label>
                <div class="relative">
                    <input :type="showCurr ? 'text' : 'password'" name="current_password" required
                        class="w-full px-5 py-4 rounded-2xl bg-gray-50 border border-gray-200 focus:outline-none focus:border-orange-500 transition-all">
                    <button type="button" @click="showCurr = !showCurr"
                        class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-400">
                        <svg x-show="!showCurr" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z">
                            </path>
                        </svg>
                        <svg x-show="showCurr" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.542-7a9.97 9.97 0 011.563-3.076m1.406-1.407A10.015 10.015 0 0112 5c4.478 0 8.268 2.943 9.542 7a10.059 10.059 0 01-4.29 5.764M10.735 10.735L13.265 13.265M15 12a3 3 0 11-6 0 3 3 0 016 0zM3 3l18 18">
                            </path>
                        </svg>
                    </button>
                </div>
            </div>
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-2">New Password</label>
                <div class="relative">
                    <input :type="showNew ? 'text' : 'password'" name="new_password" required
                        class="w-full px-5 py-4 rounded-2xl bg-gray-50 border border-gray-200 focus:outline-none focus:border-orange-500 transition-all">
                    <button type="button" @click="showNew = !showNew"
                        class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-400">
                        <svg x-show="!showNew" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z">
                            </path>
                        </svg>
                        <svg x-show="showNew" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.542-7a9.97 9.97 0 011.563-3.076m1.406-1.407A10.015 10.015 0 0112 5c4.478 0 8.268 2.943 9.542 7a10.059 10.059 0 01-4.29 5.764M10.735 10.735L13.265 13.265M15 12a3 3 0 11-6 0 3 3 0 016 0zM3 3l18 18">
                            </path>
                        </svg>
                    </button>
                </div>
            </div>
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-2">Confirm New Password</label>
                <div class="relative">
                    <input :type="showConf ? 'text' : 'password'" name="confirm_password" required
                        class="w-full px-5 py-4 rounded-2xl bg-gray-50 border border-gray-200 focus:outline-none focus:border-orange-500 transition-all">
                    <button type="button" @click="showConf = !showConf"
                        class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-400">
                        <svg x-show="!showConf" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z">
                            </path>
                        </svg>
                        <svg x-show="showConf" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.542-7a9.97 9.97 0 011.563-3.076m1.406-1.407A10.015 10.015 0 0112 5c4.478 0 8.268 2.943 9.542 7a10.059 10.059 0 01-4.29 5.764M10.735 10.735L13.265 13.265M15 12a3 3 0 11-6 0 3 3 0 016 0zM3 3l18 18">
                            </path>
                        </svg>
                    </button>
                </div>
            </div>
            <button type="submit"
                class="w-full py-4 bg-orange-500 hover:bg-orange-600 text-white font-bold rounded-2xl transition-all shadow-lg shadow-orange-500/20 transform hover:-translate-y-1">
                Update Password
            </button>
        </form>
    </div>
</div>

<?php include 'footer.php'; ?>