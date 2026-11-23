<?php
session_start();
require_once '../includes/db.php';

if (!isset($_SESSION['admin_logged_in'], $_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

$current_admin_query = $pdo->prepare('SELECT username, full_name, role FROM users WHERE id = ?');
$current_admin_query->execute([$_SESSION['admin_id']]);
$current_admin = $current_admin_query->fetch();
$is_super_admin = $current_admin
    && strtolower(trim($current_admin['username'])) === 'yared'
    && $current_admin['role'] === 'Super Admin';

if (!$is_super_admin) {
    http_response_code(403);
    exit('You do not have permission to manage admin accounts.');
}

$_SESSION['admin_user'] = $current_admin['full_name'] ?: $current_admin['username'];
$_SESSION['admin_role'] = $current_admin['role'];
if (empty($_SESSION['admin_users_token'])) {
    $_SESSION['admin_users_token'] = bin2hex(random_bytes(32));
}

$message = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['admin_users_token'], $_POST['csrf_token'] ?? '')) {
        $message = 'Your session expired. Refresh the page and try again.';
    } elseif (($_POST['action'] ?? '') === 'create') {
        $full_name = trim($_POST['full_name'] ?? '');
        $phone = normalize_ethiopian_phone($_POST['phone'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        if (strlen($full_name) < 2 || strlen($full_name) > 150) {
            $message = 'Enter a full name between 2 and 150 characters.';
        } elseif ($phone === null) {
            $message = 'Enter a valid Ethiopian mobile number.';
        } elseif (strlen($password) < 4) {
            $message = 'The password must be at least 4 characters.';
        } elseif ($password !== $confirm_password) {
            $message = 'The passwords do not match.';
        } else {
            try {
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE phone = ? AND role = 'Super Admin'");
                $stmt->execute([$phone]);
                if ((int) $stmt->fetchColumn() > 0) {
                    $message = 'The Super Admin phone number is reserved and cannot be assigned to another admin.';
                } else {
                    $stmt = $pdo->prepare('SELECT COUNT(*) FROM users WHERE phone = ?');
                    $stmt->execute([$phone]);
                    if ((int) $stmt->fetchColumn() > 0) {
                        $message = 'That phone number is already assigned to an account.';
                    } else {
                        $stmt = $pdo->prepare('INSERT INTO users (username, full_name, password, role, phone, must_change_password) VALUES (?, ?, ?, ?, ?, ?)');
                        $stmt->execute([$phone, $full_name, password_hash($password, PASSWORD_DEFAULT), 'Admin', $phone, 1]);
                        $_SESSION['admin_flash'] = [
                            'title' => 'Admin created',
                            'message' => $full_name . ' can sign in with their phone and temporary password. They will be asked to change it on first login.',
                        ];
                        header('Location: admin_users.php');
                        exit;
                    }
                }
            } catch (PDOException $e) {
                $message = 'That phone number is already assigned or the account could not be created.';
            }
        }
    } elseif (($_POST['action'] ?? '') === 'update_account') {
        $user_id = filter_var($_POST['user_id'] ?? null, FILTER_VALIDATE_INT);
        $full_name = trim($_POST['full_name'] ?? '');
        $phone = normalize_ethiopian_phone($_POST['phone'] ?? '');

        if (!$user_id || (int) $user_id === (int) $_SESSION['admin_id']) {
            $message = 'This account cannot be edited here.';
        } elseif (strlen($full_name) < 2 || strlen($full_name) > 150) {
            $message = 'Enter a full name between 2 and 150 characters.';
        } elseif ($phone === null) {
            $message = 'Enter a valid Ethiopian mobile number.';
        } else {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE phone = ? AND role = 'Super Admin'");
            $stmt->execute([$phone]);
            if ((int) $stmt->fetchColumn() > 0) {
                $message = 'The Super Admin phone number is reserved and cannot be assigned to another admin.';
            } else {
            $stmt = $pdo->prepare('SELECT COUNT(*) FROM users WHERE phone = ? AND id <> ?');
            $stmt->execute([$phone, $user_id]);
            if ((int) $stmt->fetchColumn() > 0) {
                $message = 'That phone number is already assigned to another account.';
            } else {
                $stmt = $pdo->prepare("UPDATE users SET full_name = ?, phone = ? WHERE id = ? AND role <> 'Super Admin'");
                $stmt->execute([$full_name, $phone, $user_id]);
                $message = 'Admin details updated successfully.';
                $success = true;
            }
            }
        }
    } elseif (($_POST['action'] ?? '') === 'delete') {
        $user_id = filter_var($_POST['user_id'] ?? null, FILTER_VALIDATE_INT);
        if (!$user_id || (int) $user_id === (int) $_SESSION['admin_id']) {
            $message = 'This admin account cannot be removed.';
        } else {
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ? AND role <> 'Super Admin'");
            $stmt->execute([$user_id]);
            if ($stmt->rowCount() > 0) {
                $message = 'Admin account removed.';
                $success = true;
            } else {
                $message = 'The account could not be found or cannot be removed.';
            }
        }
    }
}

$admins = $pdo->query('SELECT id, username, full_name, role, phone FROM users ORDER BY full_name ASC')->fetchAll();
$create_panel_open = $_SERVER['REQUEST_METHOD'] === 'POST'
    && ($_POST['action'] ?? '') === 'create'
    && $message !== '';
$page_title = 'Manage Admins';
include 'header.php';
?>

<div class="mx-auto w-full max-w-5xl" x-data="{ search: '', createOpen: <?= $create_panel_open ? 'true' : 'false' ?> }">
    <header class="mb-6 flex items-center justify-between gap-3 rounded-2xl bg-gradient-to-r from-[#1f322e] via-[#17503d] to-[#28704f] p-4 text-white shadow-xl sm:p-8">
        <div class="min-w-0">
            <p class="text-[10px] font-bold uppercase tracking-[0.16em] text-emerald-100 sm:text-xs sm:tracking-[0.2em]">Super Admin</p>
        </div>
        <button type="button" @click="createOpen = true"
            class="inline-flex shrink-0 items-center justify-center gap-1.5 rounded-xl bg-amber-300 px-3 py-2.5 text-sm font-bold text-[#20332b] shadow-lg transition hover:bg-amber-200 focus:outline-none focus:ring-4 focus:ring-white/40 sm:gap-2 sm:px-5 sm:py-3 sm:text-base">
            <svg class="h-4 w-4 sm:h-5 sm:w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M16 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2m6-10a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm10-1v6m-3-3h6" /></svg>
            <span class="sm:hidden">Add</span>
            <span class="hidden sm:inline">Add admin</span>
        </button>
    </header>

    <?php if ($message !== '' && !$create_panel_open): ?>
        <div role="<?= $success ? 'status' : 'alert' ?>"
            class="mb-6 rounded-2xl border p-4 text-sm font-semibold <?= $success ? 'border-emerald-100 bg-emerald-50 text-emerald-700' : 'border-red-100 bg-red-50 text-red-700' ?>">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <section class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
        <div class="flex flex-col gap-4 border-b border-gray-100 px-5 py-5 sm:flex-row sm:items-center sm:justify-between sm:px-7">
            <div>
                <h3 class="font-bold text-[#20332b]">Admin accounts</h3>
                <p class="mt-1 text-xs text-gray-500"><?= count($admins) ?> total</p>
            </div>
            <label class="relative block w-full sm:max-w-xs">
                <span class="sr-only">Search admins</span>
                <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="7" stroke-width="1.8"/><path stroke-linecap="round" stroke-width="1.8" d="m16 16 4 4"/></svg>
                <input type="search" x-model="search" placeholder="Search admins..."
                    class="w-full rounded-xl border border-gray-300 bg-gray-50 py-2.5 pl-10 pr-4 text-sm text-gray-900 outline-none transition placeholder:text-gray-500 focus:border-emerald-700 focus:ring-4 focus:ring-emerald-700/10">
            </label>
        </div>
        <ul class="divide-y divide-gray-100">
            <?php foreach ($admins as $admin): ?>
                <li data-admin-search="<?= htmlspecialchars(($admin['full_name'] ?: $admin['username']) . ' ' . ($admin['phone'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                    x-show="!search || $el.dataset.adminSearch.toLowerCase().includes(search.toLowerCase())"
                    class="flex min-w-0 items-center justify-between gap-3 px-5 py-4 sm:px-7">
                    <div class="min-w-0 flex-1">
                        <div class="flex min-w-0 items-center gap-3">
                            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-emerald-50 text-sm font-bold uppercase text-emerald-800">
                                <?= htmlspecialchars(strtoupper(substr($admin['full_name'] ?: $admin['username'], 0, 1))) ?>
                            </span>
                            <div class="min-w-0">
                                <p class="truncate font-semibold text-gray-900"><?= htmlspecialchars($admin['full_name'] ?: $admin['username']) ?></p>
                                <p class="mt-1 truncate text-xs font-medium text-gray-500"><?= htmlspecialchars($admin['role']) ?> · <?= !empty($admin['phone']) ? htmlspecialchars($admin['phone']) : 'Phone not set' ?></p>
                            </div>
                        </div>
                        <?php if ($admin['role'] !== 'Super Admin'): ?>
                            <details class="mt-2 ml-[3.25rem]">
                                <summary class="w-fit cursor-pointer text-xs font-semibold text-emerald-800 hover:text-emerald-950">Edit name or phone</summary>
                                <form method="POST" class="mt-3 grid grid-cols-1 gap-3 pr-2 sm:grid-cols-[1fr_1fr_auto]">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['admin_users_token']) ?>">
                                    <input type="hidden" name="action" value="update_account">
                                    <input type="hidden" name="user_id" value="<?= (int) $admin['id'] ?>">
                                    <label class="text-xs font-semibold text-gray-600">Full name
                                        <input name="full_name" required maxlength="150" value="<?= htmlspecialchars($admin['full_name'] ?: $admin['username'], ENT_QUOTES, 'UTF-8') ?>" class="mt-1 w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 outline-none focus:border-emerald-700 focus:ring-2 focus:ring-emerald-700/10">
                                    </label>
                                    <label class="text-xs font-semibold text-gray-600">Phone
                                        <input type="tel" name="phone" required maxlength="20" inputmode="tel" value="<?= htmlspecialchars($admin['phone'] ?? '', ENT_QUOTES, 'UTF-8') ?>" class="mt-1 w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 outline-none focus:border-emerald-700 focus:ring-2 focus:ring-emerald-700/10">
                                    </label>
                                    <button type="submit" class="self-end rounded-lg bg-emerald-800 px-3 py-2 text-xs font-bold text-white hover:bg-emerald-900">Save</button>
                                </form>
                            </details>
                        <?php endif; ?>
                    </div>
                    <?php if ($admin['role'] !== 'Super Admin'): ?>
                        <form method="POST" class="shrink-0">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['admin_users_token']) ?>">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="user_id" value="<?= (int) $admin['id'] ?>">
                            <button type="button" @click="requestConfirmation('Are you sure you want to delete this admin account?', () => $el.closest('form').requestSubmit())"
                                aria-label="Delete <?= htmlspecialchars($admin['username'], ENT_QUOTES, 'UTF-8') ?>" title="Delete admin"
                                class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-rose-200 bg-rose-50 text-rose-700 transition hover:bg-rose-100 focus:outline-none focus:ring-4 focus:ring-rose-500/20">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 7h16m-10 4v6m4-6v6M6 7l1 14h10l1-14M9 7V4h6v3"/></svg>
                            </button>
                        </form>
                    <?php else: ?>
                        <span class="shrink-0 rounded-full bg-emerald-50 px-3 py-1.5 text-[10px] font-bold text-emerald-700 sm:text-xs">Protected</span>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
            <li x-show="search && !$el.parentElement.querySelector('[data-admin-search]:not([style*=\'display: none\'])')" x-cloak class="px-5 py-10 text-center text-sm text-gray-500 sm:px-7">
                No admin accounts match your search.
            </li>
        </ul>
    </section>

    <div x-show="createOpen" x-cloak x-transition.opacity @keydown.escape.window="createOpen = false" @click.self="createOpen = false"
        class="fixed inset-0 z-[220] flex justify-end bg-[#102720]/55 backdrop-blur-sm">
        <section role="dialog" aria-modal="true" aria-labelledby="create-admin-title"
            x-transition:enter="transition ease-out duration-300" x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0"
            x-transition:leave="transition ease-in duration-200" x-transition:leave-start="translate-x-0" x-transition:leave-end="translate-x-full"
            class="flex h-full w-full max-w-lg flex-col overflow-y-auto border-l border-white/40 bg-white p-5 shadow-2xl sm:p-8">
            <div class="mb-7 flex items-start justify-between gap-4 border-b border-gray-100 pb-5">
                <div>
                    <p class="text-xs font-bold uppercase tracking-[0.18em] text-emerald-800">New account</p>
                    <h2 id="create-admin-title" class="mt-2 text-2xl font-bold text-[#20332b]">Create admin</h2>
                    <p class="mt-1 text-sm text-gray-500">Set login details for a new admin.</p>
                </div>
                <button type="button" @click="createOpen = false" aria-label="Close create admin panel" title="Close"
                    class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border border-gray-200 text-gray-600 transition hover:bg-gray-100 focus:outline-none focus:ring-4 focus:ring-emerald-700/15">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="m6 6 12 12M18 6 6 18"/></svg>
                </button>
            </div>

            <?php if ($create_panel_open && $message !== ''): ?>
                <div role="alert" class="mb-6 rounded-xl border border-red-100 bg-red-50 p-4 text-sm font-semibold text-red-700">
                    <?= htmlspecialchars($message) ?>
                </div>
            <?php endif; ?>

            <form method="POST" class="flex flex-1 flex-col gap-5" x-data="{ showPassword: false, showConfirmPassword: false }">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['admin_users_token']) ?>">
                <input type="hidden" name="action" value="create">
                <label class="text-sm font-semibold text-gray-700">
                    Full name
                    <input name="full_name" required minlength="2" maxlength="150" autocomplete="name" value="<?= htmlspecialchars($_POST['full_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                        class="mt-2 w-full rounded-xl border border-gray-300 bg-gray-50 px-4 py-3 text-gray-900 outline-none focus:border-emerald-700 focus:ring-4 focus:ring-emerald-700/10">
                </label>
                <label class="text-sm font-semibold text-gray-700">
                    Phone number
                    <input type="tel" name="phone" required maxlength="20" autocomplete="tel" inputmode="tel" placeholder="Enter phone number" value="<?= htmlspecialchars($_POST['phone'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                        class="mt-2 w-full rounded-xl border border-gray-300 bg-gray-50 px-4 py-3 text-gray-900 outline-none focus:border-emerald-700 focus:ring-4 focus:ring-emerald-700/10">
                </label>
                <label class="text-sm font-semibold text-gray-700">
                    Password
                    <span class="relative mt-2 block">
                        <input :type="showPassword ? 'text' : 'password'" name="password" required minlength="4" autocomplete="new-password"
                            class="w-full rounded-xl border border-gray-300 bg-gray-50 px-4 py-3 pr-12 text-gray-900 outline-none focus:border-emerald-700 focus:ring-4 focus:ring-emerald-700/10">
                        <button type="button" @click="showPassword = !showPassword" :aria-label="showPassword ? 'Hide password' : 'Show password'" :title="showPassword ? 'Hide password' : 'Show password'"
                            class="absolute right-2 top-1/2 -translate-y-1/2 rounded-lg p-2 text-gray-500 transition hover:bg-gray-200 hover:text-gray-800 focus:outline-none focus:ring-2 focus:ring-emerald-700/30">
                            <svg x-show="!showPassword" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M2 12s3.6-6 10-6 10 6 10 6-3.6 6-10 6S2 12 2 12Zm10 3a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z"/></svg>
                            <svg x-show="showPassword" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="m3 3 18 18M10.6 6.1A10.7 10.7 0 0 1 12 6c6.4 0 10 6 10 6a17 17 0 0 1-3.1 3.8M6.2 6.2C3.5 7.7 2 12 2 12s3.6 6 10 6a10 10 0 0 0 3-.5M9.9 9.9a3 3 0 0 0 4.2 4.2"/></svg>
                        </button>
                    </span>
                    <span class="mt-1 block text-xs font-normal text-gray-500">At least 4 characters.</span>
                </label>
                <label class="text-sm font-semibold text-gray-700">
                    Confirm password
                    <span class="relative mt-2 block">
                        <input :type="showConfirmPassword ? 'text' : 'password'" name="confirm_password" required minlength="4" autocomplete="new-password"
                            class="w-full rounded-xl border border-gray-300 bg-gray-50 px-4 py-3 pr-12 text-gray-900 outline-none focus:border-emerald-700 focus:ring-4 focus:ring-emerald-700/10">
                        <button type="button" @click="showConfirmPassword = !showConfirmPassword" :aria-label="showConfirmPassword ? 'Hide confirmation password' : 'Show confirmation password'" :title="showConfirmPassword ? 'Hide confirmation password' : 'Show confirmation password'"
                            class="absolute right-2 top-1/2 -translate-y-1/2 rounded-lg p-2 text-gray-500 transition hover:bg-gray-200 hover:text-gray-800 focus:outline-none focus:ring-2 focus:ring-emerald-700/30">
                            <svg x-show="!showConfirmPassword" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M2 12s3.6-6 10-6 10 6 10 6-3.6 6-10 6S2 12 2 12Zm10 3a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z"/></svg>
                            <svg x-show="showConfirmPassword" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="m3 3 18 18M10.6 6.1A10.7 10.7 0 0 1 12 6c6.4 0 10 6 10 6a17 17 0 0 1-3.1 3.8M6.2 6.2C3.5 7.7 2 12 2 12s3.6 6 10 6a10 10 0 0 0 3-.5M9.9 9.9a3 3 0 0 0 4.2 4.2"/></svg>
                        </button>
                    </span>
                </label>
                <div class="mt-auto border-t border-gray-100 pt-5">
                    <button type="submit" class="w-full rounded-xl bg-gradient-to-r from-[#1f322e] to-emerald-700 px-5 py-3.5 font-bold text-white shadow-lg transition hover:from-[#153b34] hover:to-emerald-800 focus:outline-none focus:ring-4 focus:ring-emerald-700/20">
                        Create admin account
                    </button>
                </div>
            </form>
        </section>
    </div>
</div>

<?php include 'footer.php'; ?>
