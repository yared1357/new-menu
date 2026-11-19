<?php
session_start();
require_once '../includes/db.php';
require_once __DIR__ . '/image_upload.php';
require_once __DIR__ . '/notifications.php';

if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit;
}

if (empty($_SESSION['payment_accounts_token'])) {
    $_SESSION['payment_accounts_token'] = bin2hex(random_bytes(32));
}

$message = '';
$success = false;
$editing = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['payment_accounts_token'], $token)) {
        $message = 'Your session expired. Refresh the page and try again.';
    } else {
        $action = $_POST['action'] ?? '';

        if ($action === 'save') {
            $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT) ?: 0;
            $provider = trim($_POST['provider'] ?? '');
            $account_name = trim($_POST['account_name'] ?? '');
            $account_number = trim($_POST['account_number'] ?? '');
            $is_active = isset($_POST['is_active']) ? 1 : 0;
            $new_qr_path = '';

            if ($provider === '' || $account_name === '' || $account_number === '') {
                $message = 'Enter the payment provider, account name, and account number.';
            } else {
                try {
                    if ($id > 0) {
                        $stmt = $pdo->prepare("SELECT * FROM payment_accounts WHERE id = ?");
                        $stmt->execute([$id]);
                        $existing = $stmt->fetch();
                        if (!$existing) {
                            throw new RuntimeException('This payment account no longer exists.');
                        }
                        $qr_path = $existing['qr_image'] ?? '';
                    } else {
                        $existing = null;
                        $qr_path = '';
                    }

                    if (isset($_FILES['qr_image']) && ($_FILES['qr_image']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
                        $new_qr_path = store_menu_image($_FILES['qr_image']);
                        $qr_path = $new_qr_path;
                    }

                    if ($id > 0) {
                        $stmt = $pdo->prepare("UPDATE payment_accounts SET provider = ?, account_name = ?, account_number = ?, qr_image = ?, is_active = ? WHERE id = ?");
                        $stmt->execute([$provider, $account_name, $account_number, $qr_path, $is_active, $id]);
                    } else {
                        $stmt = $pdo->prepare("INSERT INTO payment_accounts (provider, account_name, account_number, qr_image, is_active) VALUES (?, ?, ?, ?, ?)");
                        $stmt->execute([$provider, $account_name, $account_number, $qr_path, $is_active]);
                    }

                    if ($new_qr_path !== '' && !empty($existing['qr_image'])) {
                        remove_menu_image($existing['qr_image']);
                    }

                    set_admin_flash($id > 0 ? 'Payment account updated successfully.' : 'Payment account created successfully.', $id > 0 ? 'Account updated' : 'Account created');
                    header('Location: payment_accounts.php');
                    exit;
                } catch (RuntimeException $e) {
                    $message = $e->getMessage();
                    if ($new_qr_path !== '') {
                        remove_menu_image($new_qr_path);
                    }
                } catch (PDOException $e) {
                    $message = 'Could not save the payment account. Please try again.';
                    if ($new_qr_path !== '') {
                        remove_menu_image($new_qr_path);
                    }
                }
            }
        } elseif ($action === 'delete') {
            $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
            if (!$id) {
                $message = 'Select a valid payment account to remove.';
            } else {
                $stmt = $pdo->prepare("SELECT qr_image FROM payment_accounts WHERE id = ?");
                $stmt->execute([$id]);
                $account = $stmt->fetch();
                if ($account) {
                    $stmt = $pdo->prepare("DELETE FROM payment_accounts WHERE id = ?");
                    $stmt->execute([$id]);
                    if (!empty($account['qr_image'])) {
                        remove_menu_image($account['qr_image']);
                    }
                    set_admin_flash('Payment account deleted successfully.', 'Account deleted');
                    header('Location: payment_accounts.php');
                    exit;
                }
                $message = 'This payment account no longer exists.';
            }
        }
    }
}

if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM payment_accounts WHERE id = ?");
    $stmt->execute([filter_input(INPUT_GET, 'edit', FILTER_VALIDATE_INT)]);
    $editing = $stmt->fetch() ?: null;
}

$accounts = $pdo->query("SELECT * FROM payment_accounts ORDER BY id DESC")->fetchAll();
$page_title = 'Payment Accounts';
include 'header.php';
?>

<div class="max-w-5xl mx-auto">
    <header class="mb-8">
        <h2 class="text-3xl font-bold text-gray-900">Payment Accounts</h2>
        </header>

    <?php if ($message !== ''): ?>
        <div class="bg-rose-50 border border-rose-100 text-rose-700 p-4 rounded-2xl mb-6 font-semibold">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <section class="glass-card rounded-3xl p-6 lg:p-8 mb-8">
        <h3 class="text-xl font-bold text-gray-900 mb-6">
            <?= $editing ? 'Edit payment account' : 'Add payment account' ?>
        </h3>
        <form method="POST" enctype="multipart/form-data" class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['payment_accounts_token']) ?>">
            <input type="hidden" name="action" value="save">
            <input type="hidden" name="id" value="<?= (int) ($editing['id'] ?? 0) ?>">

            <label class="block text-sm font-semibold text-gray-700">
                Bank or payment provider
                <input name="provider" required maxlength="120" value="<?= htmlspecialchars($editing['provider'] ?? '') ?>"
                    placeholder="e.g. CBE, Telebirr"
                    class="mt-2 w-full px-4 py-3 rounded-xl border border-gray-200 bg-gray-50 focus:outline-none focus:border-orange-500">
            </label>
            <label class="block text-sm font-semibold text-gray-700">
                Account holder name
                <input name="account_name" required maxlength="160" value="<?= htmlspecialchars($editing['account_name'] ?? '') ?>"
                    class="mt-2 w-full px-4 py-3 rounded-xl border border-gray-200 bg-gray-50 focus:outline-none focus:border-orange-500">
            </label>
            <label class="block text-sm font-semibold text-gray-700">
                Account number
                <input name="account_number" required maxlength="120" value="<?= htmlspecialchars($editing['account_number'] ?? '') ?>"
                    class="mt-2 w-full px-4 py-3 rounded-xl border border-gray-200 bg-gray-50 focus:outline-none focus:border-orange-500">
            </label>
            <label class="block text-sm font-semibold text-gray-700">
                QR code <span class="font-normal text-gray-400">(optional JPG, PNG, GIF, or WebP)</span>
                <input type="file" name="qr_image" accept="image/jpeg,image/png,image/gif,image/webp"
                    class="mt-2 w-full px-4 py-3 rounded-xl border border-dashed border-gray-300 bg-gray-50 text-sm">
            </label>

            <?php if (!empty($editing['qr_image'])): ?>
                <div class="flex items-center gap-3 md:col-span-2">
                    <img src="../<?= htmlspecialchars($editing['qr_image']) ?>" alt="Current QR code"
                        class="w-20 h-20 rounded-xl border border-gray-200 object-contain bg-white p-1">
                    <span class="text-sm text-gray-500">Upload a new QR code to replace this one.</span>
                </div>
            <?php endif; ?>

            <label class="flex items-center gap-3 text-sm font-semibold text-gray-700 md:col-span-2">
                <input type="checkbox" name="is_active" value="1" <?= !isset($editing['is_active']) || $editing['is_active'] ? 'checked' : '' ?>
                    class="h-4 w-4 accent-emerald-600">
                Show this account to customers
            </label>

            <div class="flex flex-col items-center gap-3 md:col-span-2">
                <button type="submit"
                    class="w-full rounded-full bg-gradient-to-r from-amber-300 to-orange-300 px-6 py-3.5 font-bold text-[#25342d] shadow-lg shadow-orange-950/10 transition-all hover:-translate-y-0.5 hover:from-amber-200 hover:to-orange-200 focus:outline-none focus:ring-4 focus:ring-amber-400/30 md:w-3/5">
                    <?= $editing ? 'Save changes' : 'Add account' ?>
                </button>
                <?php if ($editing): ?>
                    <a href="payment_accounts.php" class="rounded-full px-5 py-2 text-sm font-semibold text-gray-500 transition-colors hover:bg-gray-100 hover:text-gray-800">Cancel editing</a>
                <?php endif; ?>
            </div>
        </form>
    </section>

    <section class="space-y-4">
        <h3 class="text-xl font-bold text-gray-900">Configured accounts</h3>
        <?php if (!$accounts): ?>
            <div class="glass-card rounded-2xl p-6 text-sm text-gray-500">No payment accounts have been added yet.</div>
        <?php endif; ?>
        <?php foreach ($accounts as $account): ?>
            <article class="glass-card rounded-2xl p-5 flex flex-col sm:flex-row sm:items-center gap-4">
                <?php if (!empty($account['qr_image'])): ?>
                    <img src="../<?= htmlspecialchars($account['qr_image']) ?>" alt="QR code for <?= htmlspecialchars($account['provider']) ?>"
                        class="w-20 h-20 rounded-xl border border-gray-200 object-contain bg-white p-1">
                <?php else: ?>
                    <div class="w-20 h-20 rounded-xl bg-gray-100 flex items-center justify-center text-xs text-gray-400">No QR</div>
                <?php endif; ?>
                <div class="flex-1 min-w-0">
                    <div class="flex flex-wrap items-center gap-2">
                        <h4 class="font-bold text-gray-900"><?= htmlspecialchars($account['provider']) ?></h4>
                        <span class="px-2 py-1 rounded-full text-[10px] font-bold <?= $account['is_active'] ? 'bg-emerald-50 text-emerald-700' : 'bg-gray-100 text-gray-500' ?>">
                            <?= $account['is_active'] ? 'Visible' : 'Hidden' ?>
                        </span>
                    </div>
                    <p class="text-sm text-gray-600"><?= htmlspecialchars($account['account_name']) ?></p>
                    <div x-data="{ copied: false }" class="flex items-center gap-2">
                        <p x-ref="accountNumber" class="text-sm font-semibold text-gray-800"><?= htmlspecialchars($account['account_number']) ?></p>
                        <button type="button" @click="navigator.clipboard.writeText($refs.accountNumber.textContent.trim()).then(() => { copied = true; setTimeout(() => copied = false, 1500) })"
                            :aria-label="copied ? 'Account number copied' : 'Copy account number'" :title="copied ? 'Copied' : 'Copy account number'"
                            class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-gray-500 transition hover:bg-gray-100 hover:text-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-600/50">
                            <svg x-show="!copied" class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><rect x="8" y="8" width="13" height="13" rx="2" stroke-width="1.8"/><path d="M16 8V5a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h3" stroke-width="1.8"/></svg>
                            <svg x-show="copied" x-cloak class="h-4 w-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m5 12 4 4L19 6"/></svg>
                        </button>
                    </div>
                </div>
                <div class="flex gap-2">
                    <a href="payment_accounts.php?edit=<?= (int) $account['id'] ?>"
                        aria-label="Edit <?= htmlspecialchars($account['provider']) ?> account" title="Edit account"
                        class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-amber-300/60 bg-gradient-to-br from-amber-200 to-orange-200 text-[#25342d] transition-all hover:-translate-y-0.5 hover:from-amber-100 hover:to-orange-100 focus:outline-none focus:ring-4 focus:ring-amber-400/30">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                                d="m15.5 5.5 3 3M4 20l4.3-.9L19 8.4a2.1 2.1 0 0 0-3-3L5.3 16.1 4 20Z" />
                        </svg>
                    </a>
                    <form method="POST">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['payment_accounts_token']) ?>">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?= (int) $account['id'] ?>">
                        <button type="button" @click="requestConfirmation('Remove this payment account? This action cannot be undone.', () => $el.closest('form').requestSubmit())" aria-label="Delete <?= htmlspecialchars($account['provider']) ?> account" title="Delete account"
                            class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-rose-200 bg-rose-50 text-rose-700 transition-all hover:-translate-y-0.5 hover:bg-rose-100 focus:outline-none focus:ring-4 focus:ring-rose-500/20">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                                    d="M4 7h16m-10 4v6m4-6v6M6 7l1 14h10l1-14M9 7V4h6v3" />
                            </svg>
                        </button>
                    </form>
                </div>
            </article>
        <?php endforeach; ?>
    </section>
</div>

<?php include 'footer.php'; ?>
