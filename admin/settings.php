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
    $action = $_POST['action'] ?? 'password';

    if ($action === 'branding') {
        $branding = [
            'brand_name' => trim($_POST['brand_name'] ?? ''),
            'brand_short_name' => trim($_POST['brand_short_name'] ?? ''),
            'brand_tagline' => trim($_POST['brand_tagline'] ?? ''),
            'phone_number' => trim($_POST['phone_number'] ?? ''),
            'copyright_name' => trim($_POST['copyright_name'] ?? ''),
        ];
        if ($branding['brand_name'] === '' || $branding['brand_short_name'] === '') {
            $message = 'Business name and short name are required.';
        } else {
            try {
                $stmt = $pdo->prepare("UPDATE app_settings SET setting_value = ? WHERE setting_key = ?");
                foreach ($branding as $key => $value) {
                    $stmt->execute([$value, $key]);
                }
                $message = 'Business branding updated successfully!';
                $success = true;
            } catch (PDOException $e) {
                $message = 'Could not update branding settings.';
            }
        }
    } else {
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

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
}

$page_title = "Settings";
include 'header.php';
?>

<section class="flex flex-1 flex-col items-center py-8 sm:py-12">
    <div class="glass-card mb-8 w-full max-w-3xl rounded-[2rem] border border-white p-6 shadow-[0_20px_60px_rgba(27,52,43,0.10)] sm:p-9">
        <div class="mb-7 flex items-center gap-4 border-b border-gray-100 pb-6">
            <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-emerald-700 to-[#153b34] text-white shadow-md">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 20V8l8-4 8 4v12M8 20v-7h8v7M3 20h18" />
                </svg>
            </div>
            <div>
                <h3 class="text-lg font-bold text-[#20332b]">Business branding</h3>
                <p class="mt-1 text-sm text-gray-500">Customize the name shown across the customer app and admin.</p>
            </div>
        </div>

        <?php if ($message): ?>
            <div role="<?= $success ? 'status' : 'alert' ?>"
                class="mb-6 rounded-2xl border p-4 text-center text-sm font-semibold <?= $success ? 'bg-emerald-50 text-emerald-700 border-emerald-100' : 'bg-red-50 text-red-700 border-red-100' ?>">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="grid grid-cols-1 gap-5 md:grid-cols-2">
            <input type="hidden" name="action" value="branding">
            <label class="text-sm font-semibold text-gray-700">
                Business name
                <input name="brand_name" required maxlength="150" value="<?= htmlspecialchars(get_app_setting('brand_name', 'Tasty Pizza & Burger')) ?>"
                    placeholder="Yisak Fast Food"
                    class="mt-2 w-full rounded-2xl border border-gray-200 bg-[#f8faf8] px-4 py-3.5 outline-none focus:border-emerald-700 focus:ring-4 focus:ring-emerald-700/10">
            </label>
            <label class="text-sm font-semibold text-gray-700">
                Header short name
                <input name="brand_short_name" required maxlength="80" value="<?= htmlspecialchars(get_app_setting('brand_short_name', 'Tasty Pizza')) ?>"
                    placeholder="Yisak"
                    class="mt-2 w-full rounded-2xl border border-gray-200 bg-[#f8faf8] px-4 py-3.5 outline-none focus:border-emerald-700 focus:ring-4 focus:ring-emerald-700/10">
            </label>
            <label class="text-sm font-semibold text-gray-700">
                Tagline
                <input name="brand_tagline" maxlength="100" value="<?= htmlspecialchars(get_app_setting('brand_tagline', 'Digital Menu')) ?>"
                    placeholder="Digital Menu"
                    class="mt-2 w-full rounded-2xl border border-gray-200 bg-[#f8faf8] px-4 py-3.5 outline-none focus:border-emerald-700 focus:ring-4 focus:ring-emerald-700/10">
            </label>
            <label class="text-sm font-semibold text-gray-700">
                Phone number
                <input name="phone_number" maxlength="30" value="<?= htmlspecialchars(get_app_setting('phone_number', '0926862255')) ?>"
                    placeholder="+251..."
                    class="mt-2 w-full rounded-2xl border border-gray-200 bg-[#f8faf8] px-4 py-3.5 outline-none focus:border-emerald-700 focus:ring-4 focus:ring-emerald-700/10">
            </label>
            <label class="text-sm font-semibold text-gray-700 md:col-span-2">
                Copyright name
                <input name="copyright_name" maxlength="150" value="<?= htmlspecialchars(get_app_setting('copyright_name', 'Tasty Pizza & Burger')) ?>"
                    placeholder="Yisak Fast Food"
                    class="mt-2 w-full rounded-2xl border border-gray-200 bg-[#f8faf8] px-4 py-3.5 outline-none focus:border-emerald-700 focus:ring-4 focus:ring-emerald-700/10">
            </label>
            <button type="submit" class="rounded-2xl bg-gradient-to-r from-[#1f322e] via-[#17503d] to-[#246447] px-6 py-4 font-bold text-white shadow-lg md:col-span-2">
                Save branding
            </button>
        </form>
    </div>

    <header class="mb-8 max-w-xl text-center">
        <span class="inline-flex items-center gap-2 rounded-full border border-emerald-800/10 bg-emerald-800/5 px-4 py-2 text-[10px] font-bold uppercase tracking-[0.2em] text-emerald-800">
            Change Password
        </span>
        <h2 class="mt-4 text-3xl font-bold tracking-tight text-[#20332b] sm:text-4xl">Change password</h2>
        </header>

    <div class="glass-card w-full max-w-xl rounded-[2rem] border border-white p-6 shadow-[0_20px_60px_rgba(27,52,43,0.10)] sm:p-9">
        <div class="mb-7 flex items-center gap-4 border-b border-gray-100 pb-6">
            <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-amber-300 to-orange-400 text-[#25342d] shadow-md shadow-orange-900/10">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M7 10V7a5 5 0 0 1 10 0v3m-11 0h12a2 2 0 0 1 2 2v7a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2v-7a2 2 0 0 1 2-2Zm6 4v3" />
                </svg>
            </div>
            <div>
                <h3 class="text-lg font-bold text-[#20332b]">Update your credentials</h3>
                </div>
        </div>

        <?php if ($message): ?>
            <div role="<?= $success ? 'status' : 'alert' ?>"
                class="mb-6 rounded-2xl border p-4 text-center text-sm font-semibold <?= $success ? 'bg-emerald-50 text-emerald-700 border-emerald-100' : 'bg-red-50 text-red-700 border-red-100' ?>">
                <?= $message ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="space-y-5" x-data="{ showCurr: false, showNew: false, showConf: false }">
            <div>
                <label for="current_password" class="mb-2 block text-sm font-semibold text-gray-700">Current password</label>
                <div class="relative">
                    <input id="current_password" :type="showCurr ? 'text' : 'password'" name="current_password" required autocomplete="current-password"
                        class="w-full rounded-2xl border border-gray-200 bg-[#f8faf8] px-5 py-4 pr-14 text-sm outline-none transition focus:border-emerald-700 focus:bg-white focus:ring-4 focus:ring-emerald-700/10">
                    <button type="button" @click="showCurr = !showCurr"
                        :aria-label="showCurr ? 'Hide current password' : 'Show current password'"
                        class="absolute right-3 top-1/2 -translate-y-1/2 rounded-xl p-2 text-gray-400 transition-colors hover:bg-gray-100 hover:text-emerald-800">
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
                <label for="new_password" class="mb-2 block text-sm font-semibold text-gray-700">New password</label>
                <div class="relative">
                    <input id="new_password" :type="showNew ? 'text' : 'password'" name="new_password" required autocomplete="new-password"
                        class="w-full rounded-2xl border border-gray-200 bg-[#f8faf8] px-5 py-4 pr-14 text-sm outline-none transition focus:border-emerald-700 focus:bg-white focus:ring-4 focus:ring-emerald-700/10">
                    <button type="button" @click="showNew = !showNew"
                        :aria-label="showNew ? 'Hide new password' : 'Show new password'"
                        class="absolute right-3 top-1/2 -translate-y-1/2 rounded-xl p-2 text-gray-400 transition-colors hover:bg-gray-100 hover:text-emerald-800">
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
                <label for="confirm_password" class="mb-2 block text-sm font-semibold text-gray-700">Confirm new password</label>
                <div class="relative">
                    <input id="confirm_password" :type="showConf ? 'text' : 'password'" name="confirm_password" required autocomplete="new-password"
                        class="w-full rounded-2xl border border-gray-200 bg-[#f8faf8] px-5 py-4 pr-14 text-sm outline-none transition focus:border-emerald-700 focus:bg-white focus:ring-4 focus:ring-emerald-700/10">
                    <button type="button" @click="showConf = !showConf"
                        :aria-label="showConf ? 'Hide confirmation password' : 'Show confirmation password'"
                        class="absolute right-3 top-1/2 -translate-y-1/2 rounded-xl p-2 text-gray-400 transition-colors hover:bg-gray-100 hover:text-emerald-800">
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
                class="group flex w-full items-center justify-center gap-2 rounded-2xl bg-gradient-to-r from-[#1f322e] via-[#17503d] to-[#246447] py-4 font-bold text-white shadow-lg shadow-emerald-950/20 transition-all hover:-translate-y-0.5 hover:shadow-xl hover:shadow-emerald-950/25 focus:outline-none focus:ring-4 focus:ring-emerald-700/20">
                Update Password
                <svg class="h-4 w-4 text-amber-200 transition-transform group-hover:translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14m-6-6 6 6-6 6" />
                </svg>
            </button>
        </form>
    </div>
</section>

<?php include 'footer.php'; ?>