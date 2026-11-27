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
    exit('You do not have permission to reset admin passwords.');
}

$_SESSION['admin_user'] = $current_admin['full_name'] ?: $current_admin['username'];
$_SESSION['admin_role'] = $current_admin['role'];
if (empty($_SESSION['reset_admin_password_token'])) {
    $_SESSION['reset_admin_password_token'] = bin2hex(random_bytes(32));
}

$message = '';
$selected_admin_id = 0;
$selected_admin = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'reset_password') {
    $selected_admin_id = filter_var($_POST['user_id'] ?? null, FILTER_VALIDATE_INT) ?: 0;
    $password = $_POST['password'] ?? '';

    if (!hash_equals($_SESSION['reset_admin_password_token'], $_POST['csrf_token'] ?? '')) {
        $message = 'Your session expired. Refresh the page and try again.';
    } elseif (!$selected_admin_id) {
        $message = 'Choose a valid admin account.';
    } elseif (strlen($password) < 4 || strlen($password) > 128) {
        $message = 'The temporary password must be between 4 and 128 characters.';
    } else {
        $stmt = $pdo->prepare("SELECT id, username, full_name, role, phone FROM users WHERE id = ? AND role <> 'Super Admin'");
        $stmt->execute([$selected_admin_id]);
        $selected_admin = $stmt->fetch() ?: null;

        if (!$selected_admin) {
            $message = 'This admin account could not be found or cannot be reset.';
            $selected_admin_id = 0;
        } else {
            $stmt = $pdo->prepare("UPDATE users SET password = ?, must_change_password = 1 WHERE id = ? AND role <> 'Super Admin'");
            $stmt->execute([password_hash($password, PASSWORD_DEFAULT), $selected_admin_id]);

            if ($stmt->rowCount() !== 1) {
                $message = 'The temporary password could not be saved. Please try again.';
            } else {
                $_SESSION['admin_flash'] = [
                    'title' => 'Temporary password set',
                    'message' => $selected_admin['full_name'] . ' must change this password at their next sign-in.',
                ];
                header('Location: reset_admin_password.php');
                exit;
            }
        }
    }

    if ($selected_admin_id > 0 && !$selected_admin) {
        $stmt = $pdo->prepare("SELECT id, username, full_name, role, phone FROM users WHERE id = ? AND role <> 'Super Admin'");
        $stmt->execute([$selected_admin_id]);
        $selected_admin = $stmt->fetch() ?: null;
    }
}

$admins = $pdo->query("SELECT id, username, full_name, role, phone FROM users WHERE role <> 'Super Admin' ORDER BY full_name ASC, username ASC")->fetchAll();
$page_title = 'Reset Admin Password';
include 'header.php';
?>

<section class="mx-auto w-full max-w-4xl" x-data="{
    search: '',
    visibleAdminCount: <?= count($admins) ?>,
    showPassword: false,
    resetOpen: <?= $selected_admin ? 'true' : 'false' ?>,
    selectedAdminId: <?= (int) ($selected_admin['id'] ?? 0) ?>,
    selectedAdminName: <?= htmlspecialchars(json_encode($selected_admin ? ($selected_admin['full_name'] ?: $selected_admin['username']) : '', JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT), ENT_QUOTES, 'UTF-8') ?>,
    copied: false,
    copyMessage: '',
    matchesAdmin(searchable) {
        return searchable.toLocaleLowerCase().includes(this.search.trim().toLocaleLowerCase());
    },
    updateVisibleCount() {
        const rows = this.$refs.adminList ? [...this.$refs.adminList.querySelectorAll('[data-admin-search]')] : [];
        this.visibleAdminCount = rows
            .filter((row) => this.matchesAdmin(row.dataset.adminSearch)).length;
    },
    openReset(adminId, adminName) {
        this.selectedAdminId = adminId;
        this.selectedAdminName = adminName;
        this.resetOpen = true;
        this.copied = false;
        this.copyMessage = '';
        this.$nextTick(() => this.$refs.password.focus());
    },
    async copyPassword() {
        const password = this.$refs.password.value;
        if (!password) {
            this.copyMessage = 'Enter a password before copying it.';
            this.copied = false;
            return;
        }
        try {
            await navigator.clipboard.writeText(password);
            this.copyMessage = 'Temporary password copied.';
            this.copied = true;
        } catch (error) {
            this.copyMessage = 'Could not access the clipboard. Select the password and copy it manually.';
            this.copied = false;
        }
    }
}">
    <header class="mb-6 rounded-2xl bg-gradient-to-r from-[#1f322e] via-[#17503d] to-[#28704f] p-5 text-white shadow-xl sm:p-8">
        <p class="text-xs font-bold uppercase tracking-[0.18em] text-emerald-100">Super Admin</p>
        <h1 class="mt-2 text-2xl font-bold sm:text-3xl">Reset an admin password</h1>
        </header>

    <?php if ($message !== ''): ?>
        <div role="alert" class="mb-5 rounded-xl border border-red-100 bg-red-50 p-4 text-sm font-semibold text-red-700">
            <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>

    <section class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm" aria-labelledby="admin-list-title">
        <div class="flex items-center gap-2 border-b border-gray-100 px-3 py-3 sm:justify-between sm:px-6 sm:py-4">
            <div class="w-[7.25rem] shrink-0 sm:w-auto">
                <h2 id="admin-list-title" class="truncate text-sm font-bold text-[#20332b] sm:text-base">Admin accounts</h2>
                <p class="mt-0.5 whitespace-nowrap text-[10px] text-gray-500 sm:mt-1 sm:text-xs"><span x-text="visibleAdminCount"><?= count($admins) ?></span> account<span x-show="visibleAdminCount !== 1">s</span></p>
            </div>
            <label class="relative block min-w-0 flex-1 sm:max-w-sm" role="search">
                <span class="sr-only">Search admin accounts</span>
                <svg class="pointer-events-none absolute left-2.5 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-gray-500 sm:left-3 sm:h-4 sm:w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <circle cx="11" cy="11" r="7" stroke-width="1.8"/><path stroke-linecap="round" stroke-width="1.8" d="m16 16 4 4"/>
                </svg>
                <input type="search" x-model="search" @input="updateVisibleCount()" autocomplete="off" placeholder="Search admins"
                    class="w-full rounded-lg border border-gray-300 bg-gray-50 py-2 pl-8 pr-2 text-[11px] text-gray-900 outline-none transition placeholder:text-gray-500 focus:border-emerald-700 focus:ring-4 focus:ring-emerald-700/10 sm:rounded-xl sm:py-2.5 sm:pl-10 sm:pr-4 sm:text-sm">
            </label>
        </div>

        <?php if (!$admins): ?>
            <p class="px-6 py-10 text-center text-sm text-gray-500">There are no admin accounts to reset.</p>
        <?php else: ?>
            <ul x-ref="adminList" class="divide-y divide-gray-100">
                <?php foreach ($admins as $admin): ?>
                    <?php $admin_display_name = $admin['full_name'] ?: $admin['username']; ?>
                    <li data-admin-search="<?= htmlspecialchars($admin_display_name . ' ' . $admin['username'] . ' ' . ($admin['phone'] ?? '') . ' ' . $admin['role'], ENT_QUOTES, 'UTF-8') ?>"
                        x-show="matchesAdmin($el.dataset.adminSearch)"
                        class="flex items-center gap-2 px-3 py-3 sm:gap-5 sm:px-6 sm:py-4">
                        <div class="flex min-w-0 flex-1 items-center gap-2 sm:gap-3">
                            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-emerald-50 text-xs font-bold uppercase text-emerald-800 sm:h-10 sm:w-10 sm:text-sm">
                                <?= htmlspecialchars(strtoupper(substr($admin_display_name, 0, 1)), ENT_QUOTES, 'UTF-8') ?>
                            </span>
                            <div class="min-w-0">
                                <p class="truncate text-sm font-semibold text-gray-900 sm:text-base"><?= htmlspecialchars($admin_display_name, ENT_QUOTES, 'UTF-8') ?></p>
                                <p class="mt-0.5 truncate text-[10px] text-gray-500 sm:mt-1 sm:text-xs">
                                    <?= htmlspecialchars($admin['role'], ENT_QUOTES, 'UTF-8') ?>
                                    · <?= !empty($admin['phone']) ? htmlspecialchars($admin['phone'], ENT_QUOTES, 'UTF-8') : 'Phone not set' ?>
                                </p>
                            </div>
                        </div>
                        <button type="button"
                            @click="openReset(<?= (int) $admin['id'] ?>, <?= htmlspecialchars(json_encode($admin_display_name, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT), ENT_QUOTES, 'UTF-8') ?>)"
                            class="inline-flex shrink-0 items-center justify-center whitespace-nowrap rounded-lg bg-amber-500 px-2.5 py-2 text-[10px] leading-tight text-[#20332b] shadow-sm transition hover:bg-amber-400 focus:outline-none focus:ring-4 focus:ring-amber-500/25 sm:rounded-xl sm:px-4 sm:py-2.5 sm:text-sm sm:font-bold">
                            Reset password
                        </button>
                    </li>
                <?php endforeach; ?>
            </ul>
            <p x-show="search.trim() !== '' && visibleAdminCount === 0" x-cloak class="px-6 py-10 text-center text-sm text-gray-500">
                No admin accounts match your search. Try another name, username, or phone number.
            </p>
        <?php endif; ?>
    </section>

    <section x-show="resetOpen" x-cloak x-transition class="mt-6 rounded-2xl border border-emerald-100 bg-white p-5 shadow-sm sm:p-7" aria-labelledby="reset-form-title">
        <div class="mb-5 flex items-start justify-between gap-4">
            <div>
                <h2 id="reset-form-title" class="text-lg font-bold text-[#20332b]">Set a temporary password</h2>
                <p class="mt-1 text-sm text-gray-600">Account: <span class="font-semibold text-gray-900" x-text="selectedAdminName"></span></p>
            </div>
            <button type="button" @click="resetOpen = false" class="rounded-lg px-3 py-1.5 text-sm font-semibold text-gray-600 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-emerald-700/30">
                Cancel
            </button>
        </div>
        <form method="POST" class="space-y-4">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['reset_admin_password_token'], ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="action" value="reset_password">
            <input type="hidden" name="user_id" :value="selectedAdminId">
            <label class="block text-sm font-semibold text-gray-700">
                Temporary password
                <span class="mt-2 flex gap-2">
                    <span class="relative min-w-0 flex-1">
                        <input x-ref="password" :type="showPassword ? 'text' : 'password'" name="password" required minlength="4" maxlength="128" autocomplete="new-password"
                            class="w-full rounded-xl border border-gray-300 bg-gray-50 px-4 py-3 pr-12 text-gray-900 outline-none focus:border-emerald-700 focus:ring-4 focus:ring-emerald-700/10">
                        <button type="button" @click="showPassword = !showPassword" :aria-label="showPassword ? 'Hide temporary password' : 'Show temporary password'" :title="showPassword ? 'Hide password' : 'Show password'"
                            class="absolute right-2 top-1/2 -translate-y-1/2 rounded-lg p-2 text-gray-500 hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-emerald-700/30">
                            <svg x-show="!showPassword" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M2 12s3.6-6 10-6 10 6 10 6-3.6 6-10 6S2 12 2 12Zm10 3a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z"/>
                            </svg>
                            <svg x-show="showPassword" x-cloak class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="m3 3 18 18M10.6 6.1A10.7 10.7 0 0 1 12 6c6.4 0 10 6 10 6a17 17 0 0 1-3.1 3.8M6.2 6.2C3.5 7.7 2 12 2 12s3.6 6 10 6a10 10 0 0 0 3-.5M9.9 9.9a3 3 0 0 0 4.2 4.2"/>
                            </svg>
                        </button>
                    </span>
                    <button type="button" @click="copyPassword()" class="rounded-xl border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm font-bold text-emerald-900 hover:bg-emerald-100 focus:outline-none focus:ring-2 focus:ring-emerald-700/30">
                        Copy
                    </button>
                </span>
                <span class="mt-1 block text-xs font-normal text-gray-500">Use a unique temporary password. The admin will be required to change it after signing in.</span>
            </label>
            <p x-show="copyMessage" x-cloak :role="copied ? 'status' : 'alert'" aria-live="polite" class="text-sm font-semibold" :class="copied ? 'text-emerald-700' : 'text-amber-800'" x-text="copyMessage"></p>
            <button type="submit" class="w-full rounded-xl bg-emerald-800 px-5 py-3.5 font-bold text-white transition hover:bg-emerald-900 focus:outline-none focus:ring-4 focus:ring-emerald-700/20 sm:w-auto">
                Set password and continue
            </button>
        </form>
    </section>
</section>

<?php include 'footer.php'; ?>
